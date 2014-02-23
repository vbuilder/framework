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

namespace vBuilder\Security;

use vBuilder,
	Nette;

/**
 * User login attempt counter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 23, 2014
 */
class LoginAttemptLogger extends Nette\Object {

	/**/ /** Events */
	const EVENT_IP_LOGIN_ATTEMPT   = 'ipLoginAttempt';
	const EVENT_USER_LOGIN_ATTEMPT = 'userLoginAttempt';
	/**/

	/** @var vBuilder\Security\DatabaseAttemptLogger */
	private $logger;

	/** @var Nette\Http\IRequest */
	private $httpRequest;

	public function __construct(DatabaseAttemptLogger $logger, Nette\Http\IRequest $httpRequest) {
		$this->logger = $logger;
		$this->httpRequest = $httpRequest;

		/// @todo configurable events

		$logger->setEvent(self::EVENT_IP_LOGIN_ATTEMPT,   80, '2 hours', FALSE);
		$logger->setEvent(self::EVENT_USER_LOGIN_ATTEMPT, 80, '1 hour',  TRUE);
	}

	/**
	 * Listener for {@link User::EVENT_ON_LOGIN_ATTEMPT}
	 *
	 * Checks if number of attempts wasn't exceeded.
	 *
	 * @param vBuilder\Security\User user service
	 * @param string authentication method - see {@link User::AUTHN_METHOD_INVALID}
	 * @param string user id (depending on authentication method)
	 *
	 * @return void
	 */
	public function onLoginAttempt(vBuilder\Security\User $userService, $authMethod, $uid) {

		// IPs
		if(!$this->logger->getRemainingAttempts(self::EVENT_IP_LOGIN_ATTEMPT, $this->httpRequest->getRemoteAddress()))
			throw new AuthenticationException("Maximum number of attempts exceeded for host " . $this->httpRequest->getRemoteAddress(), BaseAuthenticator::MAXIMUM_ATTEMPTS_EXCEEDED);

		// Users
		if($authMethod == User::AUTHN_METHOD_PASSWORD) {
			if(!$this->logger->getRemainingAttempts(self::EVENT_USER_LOGIN_ATTEMPT, $uid))
				throw new AuthenticationException("Maximum number of attempts exceeded for user '$uid'", BaseAuthenticator::MAXIMUM_ATTEMPTS_EXCEEDED);
		}
	}

	/**
	 * Listener for {@link User::EVENT_ON_FAILED_LOGIN_ATTEMPT}
	 *
	 * Increments attempt counters.
	 *
	 * @param vBuilder\Security\User user service
	 * @param string authentication method - see {@link User::AUTHN_METHOD_INVALID}
	 * @param string user id (depending on authentication method)
	 *
	 * @return void
	 */
	public function onFailedLoginAttempt(vBuilder\Security\User $userService, $authMethod, $uid) {

		// IPs
		$this->logger->logFail(self::EVENT_IP_LOGIN_ATTEMPT, $this->httpRequest->getRemoteAddress());

		// Users
		if($authMethod == User::AUTHN_METHOD_PASSWORD)
			$this->logger->logFail(self::EVENT_USER_LOGIN_ATTEMPT, $uid);
	}

	/**
	 * Listener for {@link User::EVENT_ON_LOGGED_IN}
	 *
	 * Resets the counters.
	 *
	 * @param vBuilder\Security\User user service
	 * @param string user id (depending on authentication method)
	 *
	 * @return void
	 */
	public function onLoggedIn(vBuilder\Security\User $userService, $uid) {

		$this->logger->logSuccess(self::EVENT_IP_LOGIN_ATTEMPT, $this->httpRequest->getRemoteAddress());
		$this->logger->logSuccess(self::EVENT_USER_LOGIN_ATTEMPT, $uid);
	}

}
