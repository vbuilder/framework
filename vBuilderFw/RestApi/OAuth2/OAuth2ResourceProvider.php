<?php

/**
 * This file is part of vBuilder Framework (vBuilder FW).
 *
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 *
 * For more information visit http://www.vbuilder.cz
 *
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\RestApi\OAuth2;

use vBuilder,
	vBuilder\RestApi\Presenter as RestPresenter,
	vBuilder\Security\User,
	vBuilder\Security\Authenticators\BaseAuthenticator,
	Nette,
	Nette\Security\AuthenticationException;


/**
 * OAuth2 resource provider
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-15
 */
class OAuth2ResourceProvider extends vBuilder\RestApi\ResourceProvider {

	/** @var vBuilder\RestApi\OAuth2\ITokenManager @inject */
	public $tokenManager;

	/** @var vBuilder\RestApi\OAuth2\IClientAuthenticator @inject */
	public $clientAuthenticator;

	/** @var vBuilder\Security\User @inject */
	public $user;

	/** @var vBuilder\Security\DatabaseAttemptLogger @inject */
	public $attemptLogger;

	/** @var StdClass|NULL */
	protected $tokenParameters = NULL;

	/**
	 * Error responses based on RFC 6749 - 5.2. Error Response
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-15#section-5.2
	 */
	const ERROR_INVALID_REQUEST = 'invalid_request';
	const ERROR_INVALID_CLIENT = 'invalid_client';
	const ERROR_INVALID_GRANT = 'invalid_grant';
	const ERROR_UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';
	const ERROR_MAXIMUM_ATTEMPTS_EXCEEDED = 'maximum_attempts_exceeded';
	/**/

	/**
	 * @Url(/oauth2/token)
	 */
	function getToken() {
		// HTTP 405 Method Not Allowed
		$this->presenter->terminateWithCode(405);
	}

	/**
	 * @Url(/oauth2/token)
	 */
	function postToken() {

		if(!isset($this->postData['grant_type']))
			$this->terminateWithError(self::ERROR_INVALID_REQUEST, 'grant_type is a required parameter');

		switch($this->postData['grant_type']) {

			// RFC 6749 - 4.3. Resource Owner Password Credentials
			// @see http://tools.ietf.org/html/draft-ietf-oauth-v2-15#section-4.3
			//
			// Used for user owned resources (both user and client application authorization)
			case 'password':
				list($clientId, $clientSecret) = $this->parseClientAuthInfo();

				if(!isset($this->postData['username']))
					$this->terminateWithError(self::ERROR_INVALID_REQUEST, 'username is a required parameter');

				if(!isset($this->postData['password']))
					$this->terminateWithError(self::ERROR_INVALID_REQUEST, 'password is a required parameter');

				$this->processClientAuth($clientId, $clientSecret);
				$this->processPasswordAuth($this->postData['username'], $this->postData['password']);
				$token = $this->tokenManager->createToken($this->tokenParameters);
				break;

			// RFC 6749 - 4.4. Client Credentials
			// @see http://tools.ietf.org/html/draft-ietf-oauth-v2-15#section-4.4
			//
			// Used for resources owned solely by client application owned (no user authorization)
			// Example: B2B services
			case 'client_credentials':
				list($clientId, $clientSecret) = $this->parseClientAuthInfo();
				$this->processClientAuth($clientId, $clientSecret);
				$token = $this->tokenManager->createToken($this->tokenParameters);
				break;

			// RFC 6749 - 6. Refreshing and Access Token
			// @see http://tools.ietf.org/html/draft-ietf-oauth-v2-15#section-6
			//
			// Used for extending time of life of expired token
			case 'refresh_token':
				list($clientId, $clientSecret) = $this->parseClientAuthInfo();

				if(!isset($this->postData['refresh_token']))
					$this->terminateWithError(self::ERROR_INVALID_REQUEST, 'refresh_token is a required parameter');

				if(!$this->attemptLogger->getRemainingAttempts(RestPresenter::ATTEMPT_IP_TOKEN, $this->httpRequest->getRemoteAddress()))
					$this->terminateWithError(self::ERROR_MAXIMUM_ATTEMPTS_EXCEEDED, 'Maximum number of attempts exceeded');

				$this->processClientAuth($clientId, $clientSecret);
				$token = $this->tokenManager->refreshToken($this->postData['refresh_token']);
				if(!$token) {
					$this->attemptLogger->logFail(RestPresenter::ATTEMPT_IP_TOKEN, $this->httpRequest->getRemoteAddress());
					$this->terminateWithError(self::ERROR_INVALID_GRANT, 'Invalid refresh token');
				}

				$this->attemptLogger->logSuccess(RestPresenter::ATTEMPT_IP_TOKEN, $this->httpRequest->getRemoteAddress());

				break;

			// Unsupported grant_type
			default:
				$this->terminateWithError(self::ERROR_UNSUPPORTED_GRANT_TYPE, 'Unsupported grant_type');
		}

		// ----------------------------

		// Sanity check
		if(!$token) throw new Nette\InvalidStateException("Excpected token");

		$this->httpResponse->setHeader('Cache-Control', 'no-store');

		$payload = new \StdClass;
		$payload->access_token = $token->getToken();
		$payload->token_type = 'Bearer';
		$payload->expires_in = $token->getTtl();
		if($token->getRefreshToken()) $payload->refresh_token = $token->getRefreshToken();

		return $payload;
	}

	/**
	 * Performs client authorization
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-15#section-4.4
	 *
	 * @param string client id
	 * @param string client secret
	 * @throws Nette\Application\AbortException on failed authorization
	 */
	protected function processClientAuth($clientId, $clientSecret) {

		$client = $this->clientAuthenticator->authenticate($clientId, $clientSecret);
		if(!$client)
			$this->terminateWithError(self::ERROR_INVALID_CLIENT, 'The client credentials are invalid');;

		// Set up token parameters
		if(!isset($this->tokenParameters)) $this->tokenParameters = new \StdClass;
		$this->tokenParameters->client = $client;
	}

	/**
	 * Performs user authorization
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-15#section-4.3
	 *
	 * @param string username
	 * @param string password
	 * @throws Nette\Application\AbortException on failed authorization
	 */
	protected function processPasswordAuth($username, $password) {

		// Set NULL storage for user (no session storing)
		$this->user->setStorage();

		try {
			$this->user->login(User::AUTHN_METHOD_PASSWORD, User::AUTHN_SOURCE_ALL, $username, $password);

			// Set up token parameters
			if(!isset($this->tokenParameters)) $this->tokenParameters = new \StdClass;
			$this->tokenParameters->userIdentity = $this->user->identity;

		} catch(AuthenticationException $e) {
			if($e->getCode() == BaseAuthenticator::MAXIMUM_ATTEMPTS_EXCEEDED)
				$this->terminateWithError(self::ERROR_MAXIMUM_ATTEMPTS_EXCEEDED, 'Maximum number of attempts exceeded');
			else
				$this->terminateWithError(self::ERROR_INVALID_CLIENT, 'The user credentials are invalid');
		}
	}

	/**
	 * Parses client_id and client_secret parameters
	 * and also looks up for the HTTP Authorization header which
	 * can be optionally used to pass those parameters
	 *
	 * @return array (string clientId, string|NULL clientSecret)
	 * @throws Nette\Application\AbortException if invalid request
	 */
	protected function parseClientAuthInfo() {
		$authHeader = $this->httpRequest->getHeader('Authorization');
		if($authHeader !== NULL) {
			if(!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']))
				$this->terminateWithError(self::ERROR_INVALID_REQUEST, 'Unsupported Authorization header');

			if(isset($this->postData['client_id']) || isset($this->postData['client_secret']))
				$this->terminateWithError(self::ERROR_INVALID_REQUEST, 'Multiple authorization data');

			return array(
				$_SERVER['PHP_AUTH_USER'],
				$_SERVER['PHP_AUTH_PW']
			);

		} elseif(isset($this->postData['client_id'])) {
			return array($this->postData['client_id'], isset($this->postData['client_secret']) ? $this->postData['client_secret'] : NULL);

		} else
			$this->terminateWithError(self::ERROR_INVALID_REQUEST, 'Missing client authorization. Add client_id,client_secret params or use HTTP Basic Authorization.');
	}

	/**
	 * Creates error response payload and sends it to the client
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-15#section-5.2
	 * @throws Nette\Application\AbortException
	 */
	protected function terminateWithError($error, $description = NULL) {
		$payload = new \StdClass;
		$payload->error = $error;
		if($description) $payload->error_description = $description;

		$httpCode = ($error == self::ERROR_INVALID_CLIENT)
			? 401 // HTTP 401 Unauthorized
			: 400; // HTTP 400 Bad Request

		$this->presenter->terminateWithCode($httpCode, $payload);
	}

}