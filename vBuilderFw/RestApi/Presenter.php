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

namespace vBuilder\RestApi;

use vBuilder,
	vBuilder\Security\User,
	vBuilder\RestApi\OAuth2\OAuth2ResourceProvider,
	Nette,
	Nette\Application\AbortException;

/**
 * Base implementation of REST API Presenter
 *
 * @todo JSON formated error responses on Exception
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 12, 2014
 */
class Presenter extends Nette\Object implements Nette\Application\IPresenter {

	/**
	 * Error responses
	 */
	const ERROR_INVALID_REQUEST = 'invalid_request';
	const ERROR_INTERNAL = 'internal_error';
	/**/

	/** @internal parameter keys */
	const PARAM_KEY_PATH = 'path';
	/**/

	/**/ /** Events */
	const ATTEMPT_IP_TOKEN   = 'ipAuthTokenAttempt';
	/**/

	/** @var vBuilder\RestApi\OAuth2\ITokenManager @inject */
	public $tokenManager;

	/** @var vBuilder\Security\User @inject */
	public $user;

	/** @var vBuilder\Security\DatabaseAttemptLogger @inject */
	public $attemptLogger;

	/** @var Nette\Http\IRequest @inject */
	public $httpRequest;

	/** @var Nette\Http\IResponse @inject */
	public $httpResponse;

	/** @var vBuilder\RestApi\RequestRouter @inject */
	public $requestRouter;

	/** @var Nette\Application\Application @inject */
	public $application;

	/** @var Nette\DI\Container @inject */
	public $systemContainer;

	/** @var mixed|NULL */
	protected $postData;

	/** @var string */
	protected $outputContentType;

	/** @var vBuilder\RestApi\OAuth2\Client|NULL */
	protected $client;

	/** @var Nette\Application\IResponse */
	protected $response;

	/** @var Nette\Application\Request */
	protected $appRequest;


	/**
	 * @return Nette\Application\IResponse
	 */
	public function run(Nette\Application\Request $request) {

		// Save the application request
		$this->appRequest = $request;

		// Setup authorization attempt logger
		$this->attemptLogger->setEvent(self::ATTEMPT_IP_TOKEN, 200, '1 hour', FALSE);

		// Process the request with option to abort with another response
		try {
			$this->response = $this->process($request);

		} catch(AbortException $e) {
			// Do nothing

		} catch(\Exception $e) {

			// If there is no need to reformat error to the payload
			// I will just throw it right back
			if($this->outputContentType == 'text/html' && !$this->systemContainer->parameters['productionMode'])
				throw $e;

			Nette\Diagnostics\Debugger::log($e);

			$payload = new \StdClass;
			$payload->error = self::ERROR_INTERNAL;
			$payload->error_description = 'Error occured while processing your request. Error has been automatically reported. Please try again later.';
			$payload->error_report_id = md5(preg_replace('~(Resource id #)\d+~', '$1', $e));

			$this->httpResponse->setCode(Nette\Http\IResponse::S500_INTERNAL_SERVER_ERROR);
			$this->response = $this->createResponse($payload);
		}

		return $this->response;
	}

	/**
	 * @return Nette\Application\IResponse
	 */
	protected function process(Nette\Application\Request $request) {

		// Query output content type -------------------------------------------

		// Accept header is comma separated fallback sequence
		$cTypes = preg_split('/,/', $this->httpRequest->getHeader('Accept'), 0, PREG_SPLIT_NO_EMPTY);
		foreach($cTypes as $cType) {
			// We ignore all the options
			$cType = preg_replace('/;.*/', '', $cType);

			if(strcasecmp($cType,  'text/html') === 0 || strcmp($cType,  '*/*') === 0) {
				$this->outputContentType = 'text/html';
				$this->httpResponse->setContentType('text/html', 'utf-8');
				break;

			} elseif(strcasecmp($cType,  'application/json') === 0) {
				$this->outputContentType = 'application/json';
				$this->httpResponse->setContentType('application/json', 'utf-8');
				break;
			}
		}

		if($this->outputContentType === NULL)
			$this->terminateWithError(self::ERROR_INVALID_REQUEST, "Accept header is missing or not satisfiable.", 406 /* Not Acceptable */);

		// Find request handler ------------------------------------------------

		// Gather resource path
		$parameters = $request->getParameters();
		$resourcePath = isset($parameters[self::PARAM_KEY_PATH]) ? trim($parameters[self::PARAM_KEY_PATH]) : NULL;
		if(!$resourcePath)
			$this->terminateWithError(self::ERROR_INVALID_REQUEST, "No resource path given.", 400 /* Bad request */);

		// Request router expects leading slash
		if($resourcePath[0] != '/') $resourcePath = "/$resourcePath";

		// Request router: find resource handler
		/** @var vBuilder\RestApi\Request|NULL */
		$handlerRequest = $this->requestRouter->createRequest(
			$this->httpRequest->getMethod(),
			$resourcePath
		);

		if($handlerRequest === NULL)
			$this->terminateWithError(self::ERROR_INVALID_REQUEST, "There is no resource handler for given URL / HTTP method.", 400 /* Bad request */);

		// Process Authorization header ----------------------------------------
		if(($authHeader = $this->httpRequest->getHeader('Authorization')) !== NULL) {
			if(preg_match('/^Bearer\\s([^\\s,;]+)/i', $authHeader, $matches)) {
				if(!$this->attemptLogger->getRemainingAttempts(self::ATTEMPT_IP_TOKEN, $this->httpRequest->getRemoteAddress())) {
					$this->terminateWithError(OAuth2ResourceProvider::ERROR_MAXIMUM_ATTEMPTS_EXCEEDED, 'Maximum number of authorization attempts exceeded.', 403 /* Forbidden */);
				}

				$token = $this->tokenManager->getToken($matches[1]);
				if(!$token) {
					$this->attemptLogger->logFail(self::ATTEMPT_IP_TOKEN, $this->httpRequest->getRemoteAddress());
					$this->terminateWithError(OAuth2ResourceProvider::ERROR_INVALID_GRANT, 'Given authorization token is not valid.', 401 /* Unauthorized */);
				}

				$this->attemptLogger->logSuccess(self::ATTEMPT_IP_TOKEN, $this->httpRequest->getRemoteAddress());
				if(isset($token->parameters->userIdentity)) {
					$this->user->login(User::AUTHN_METHOD_INVALID, User::AUTHN_SOURCE_ALL, $token->parameters->userIdentity);
				}

				if(isset($token->parameters->client)) {
					$this->client = $token->parameters->client;
				}
			}
		}

		// Decode POST data ----------------------------------------------------
		if($this->httpRequest->isPost()) {
			$cType = $this->httpRequest->getHeader('Content-Type');
			if(strcasecmp($cType,  'application/json') === 0) {
				$this->postData = Nette\Utils\Json::decode(file_get_contents('php://input'), Nette\Utils\Json::FORCE_ARRAY);
			}

			elseif(strcasecmp($cType,  'application/x-www-form-urlencoded') === 0) {
				$this->postData = $this->httpRequest->getPost();
			}

			elseif($cType === NULL)
				$this->terminateWithError(self::ERROR_INVALID_REQUEST, "Missing Content-Type header, which is mandatory for POST requests.", 400 /* Bad request */);
			else
				$this->terminateWithError(self::ERROR_INVALID_REQUEST, "Request content type of POST data is not supported.", 415 /* Unsupported Media Type */);
		}

		// Create resource instance and prepare all dependencies ---------------
		$class = $handlerRequest->getResourceClassName();
		$resource = new $class;
		$resource->presenter = $this;
		$this->systemContainer->callInjects($resource);

		// Prepare and order invoke parameters ---------------------------------
		$mReflection = $handlerRequest->getMethodReflection();
		$invokeParams = array();
		$requestParams = $handlerRequest->getParameters();
		$definedParams = $mReflection->getParameters();

		$index = 0;
		foreach($definedParams as $pReflection) {
			$index++;

			// Parameter not given in URL?
			if(!isset($requestParams[$pReflection->getName()])) {
				// Default value where available
				if($pReflection->isDefaultValueAvailable()) {
					$invokeParams[$pReflection->getName()] = $pReflection->getDefaultValue();
					continue;
				}

				$this->terminateWithError(self::ERROR_INVALID_REQUEST, "Missing #$index parameter for resource handler $class::" . $mReflection->getName() . '().', 400 /* Bad request */);
			}

			$invokeParams[$pReflection->getName()] = $requestParams[$pReflection->getName()];
		}

		// Perform startup
		$resource->startup();

		// Invoke handler method on resource instance
		$responsePayload = $mReflection->invokeArgs($resource, $invokeParams);
		return $this->createResponse($responsePayload);
	}

	/**
	 * Creates and returns response from given payload formatted for selected
	 * output content type.
	 *
	 * @return Nette\Application\IResponse
	 */
	protected function createResponse($payload) {
		if($this->outputContentType == 'application/json')
			// Don't use JsonResponse because it manages the caching headers
			return new Nette\Application\Responses\TextResponse(
				Nette\Utils\Json::encode($payload)
			);
		else {

			$text = (string) Nette\Utils\Html::el('pre', Nette\Utils\Json::encode($payload, Nette\Utils\Json::PRETTY));
			$text = '<h1>HTTP ' . $this->httpResponse->getCode() . '</h1>' . $text;

			return new Nette\Application\Responses\TextResponse(
				$text
			);
		}
	}

	/**
	 * Returns absolute URL for given resource path
	 *
	 * @todo there should be also implementation which transforms resource provider method
	 *	 into resource path
	 *
	 * @param string resource path
	 * @return string absolute url
	 */
	public function link($path = NULL) {
		$r = new Nette\Application\Request($this->appRequest->getPresenterName(), $this->appRequest->getMethod(), array('action' => 'default', self::PARAM_KEY_PATH => $path));
		return $this->application->getRouter()->constructUrl($r, $this->httpRequest->getUrl());
	}

	/**
	 * Returns POST data
	 *
	 * @return mixed
	 */
	public function getPostData() {
		return $this->postData;
	}

	/**
	 * Retruns client information
	 *
	 * @return vBuilder\RestApi\OAuth2\Client|NULL
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * Terminates current request and sends HTTP response with
	 * code and optionaly payload formatted for requested output content type
	 *
	 * @throws AbortException
	 */
	public function terminateWithCode($code, $payload = NULL) {

		$this->httpResponse->setCode($code);

		if(func_num_args() == 1)
			$this->response = new Nette\Application\Responses\TextResponse(NULL);
		else
			$this->response = $this->createResponse($payload);

		throw new AbortException();
	}

	/**
	 * Terminates current request and sends formated payload with error
	 * class and description
	 *
	 * @param string error name
	 * @param string|NULL error description
	 * @param int HTTP code
	 *
	 * @throws AbortException
	 */
	public function terminateWithError($error, $description = NULL, $httpCode = 500) {
		if(!is_scalar($error) || $error == "")
			throw new Nette\InvalidArgumentException("Error name has to be non-empty string");

		if(!is_scalar($description))
			throw new Nette\InvalidArgumentException("Error description cannot be an object");

		$payload = new \StdClass;
		$payload->error = $error;
		if($description != "")
			$payload->error_description = $description;

		$this->terminateWithCode($httpCode, $payload);
	}

	/**
	 * Terminates request and sends empty HTTP 304 response if
	 * given date is less or equal to date send with If-Modified-Since header.
	 *
	 * Method takes date as a parameter. Callable returning DateTime is also
	 * acceptable and recommended in cases when gathering of last modification date
	 * is difficult. This way the callback will be invoked only if If-Modified-Since
	 * is present.
	 *
	 * @param DateTime|callable
	 * @throws AbortException
	 */
	public function terminateIfNoOlderThan($lastModificationDate) {
		if(!($lastModificationDate instanceof \DateTime) && !is_callable($lastModificationDate))
			throw new Nette\InvalidArgumentException("Function accepts only DateTime or callable returning DateTime");

		$ifModifiedSince = $this->httpRequest->getHeader('If-Modified-Since');
		if($ifModifiedSince === NULL) return ;

		$ifDate = vBuilder\Utils\Http::parseDateTime($ifModifiedSince);

		// RFC tell us to ignore invalid dates silently
		if(!$ifDate) return ;
		$ifDate->setTimeZone(new \DateTimeZone(date_default_timezone_get()));

		if(is_callable($lastModificationDate)) {
			$lastModificationDate = $lastModificationDate();
			if(!($lastModificationDate instanceof \DateTime))
				throw new Nette\InvalidArgumentException("Callback has to return DateTime");
		}

		if($ifDate >= $lastModificationDate)
			$this->terminateWithCode(Nette\Http\IResponse::S304_NOT_MODIFIED);
	}

}