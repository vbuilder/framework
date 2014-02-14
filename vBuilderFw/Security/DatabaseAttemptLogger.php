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
	vBuilder\Security\Authenticators\BaseAuthenticator,
	Nette,
	Nette\Security\AuthenticationException,
	DibiConnection;

/**
 * Basic logger for counting failed login attempts.
 *
 * Number of attempts is stored in database and is reset after
 * some time or successful login.
 *
 * @note Logging is fully atomic.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 14, 2014
 */
class DatabaseAttemptLogger extends Nette\Object {

	/**/ /** Events */
	const EVENT_IP_LOGIN_ATTEMPT   = 'ipLoginAttempt';
	const EVENT_USER_LOGIN_ATTEMPT = 'userLoginAttempt';
	/**/

	/** @var DibiConnection */
	protected $dbConnection;

	/** @var Nette\Http\IRequest */
	protected $httpRequest;

	/** @var string table name */
	protected $tableName = 'security_log';

	/** @var events */
	protected $events = array();

	public function __construct(DibiConnection $dbConnection, Nette\Http\IRequest $httpRequest) {
		$this->dbConnection = $dbConnection;
		$this->httpRequest = $httpRequest;

		/// @todo configurable events

		// For IPs
		$this->events[self::EVENT_IP_LOGIN_ATTEMPT] = new \StdClass;
		$this->events[self::EVENT_IP_LOGIN_ATTEMPT]->maxAttempts = 80;
		$this->events[self::EVENT_IP_LOGIN_ATTEMPT]->timeWindow = '2 hours';
		$this->events[self::EVENT_IP_LOGIN_ATTEMPT]->resetOnSuccess = FALSE;

		// For user names
		//  -> successful login resets the counter
		$this->events[self::EVENT_USER_LOGIN_ATTEMPT] = new \StdClass;
		$this->events[self::EVENT_USER_LOGIN_ATTEMPT]->maxAttempts = 8;
		$this->events[self::EVENT_USER_LOGIN_ATTEMPT]->timeWindow = '1 hour';
		$this->events[self::EVENT_USER_LOGIN_ATTEMPT]->resetOnSuccess = TRUE;
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
		$n = $this->getNumOfAttempts(self::EVENT_IP_LOGIN_ATTEMPT, $this->httpRequest->getRemoteAddress());
		if(($max = $this->getMaxNumOfAttempts(self::EVENT_IP_LOGIN_ATTEMPT)) !== NULL && $n > $max)
			throw new AuthenticationException("Maximum number of attempts exceeded for host " . $this->httpRequest->getRemoteAddress(), BaseAuthenticator::MAXIMUM_ATTEMPTS_EXCEEDED);

		// Users
		if($authMethod == User::AUTHN_METHOD_PASSWORD) {
			$n = $this->getNumOfAttempts(self::EVENT_USER_LOGIN_ATTEMPT, $uid);
			if(($max = $this->getMaxNumOfAttempts(self::EVENT_USER_LOGIN_ATTEMPT)) !== NULL && $n > $max)
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
		$this->getNumOfAttempts(self::EVENT_IP_LOGIN_ATTEMPT, $this->httpRequest->getRemoteAddress(), 1);

		// Users
		if($authMethod == User::AUTHN_METHOD_PASSWORD)
			$this->getNumOfAttempts(self::EVENT_USER_LOGIN_ATTEMPT, $uid, 1);
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
		$minInt = 255<<(PHP_INT_SIZE*8)-1;

		// IPs
		if($this->getResetOnSuccess(self::EVENT_IP_LOGIN_ATTEMPT))
			$this->getNumOfAttempts(self::EVENT_IP_LOGIN_ATTEMPT, $this->httpRequest->getRemoteAddress(), $minInt);

		// Users
		if($this->getResetOnSuccess(self::EVENT_USER_LOGIN_ATTEMPT))
			$this->getNumOfAttempts(self::EVENT_USER_LOGIN_ATTEMPT, $uid, $minInt);
	}

	/**
	 * Returns TRUE if counter should be reset on successful login
	 *
	 * @param string event
	 * @return bool
	 * @throws Nette\InvalidArgumentException if event was not defined
	 */
	protected function getResetOnSuccess($event) {
		if(!isset($this->events[$event]))
			throw new Nette\InvalidArgumentException("Event '$event' not set");

		return $this->events[$event]->resetOnSuccess ?: FALSE;
	}

	/**
	 * Returns maximum number of attempts for given event
	 *
	 * @param string event
	 * @return int
	 * @throws Nette\InvalidArgumentException if event was not defined
	 */
	protected function getMaxNumOfAttempts($event) {
		if(!isset($this->events[$event]))
			throw new Nette\InvalidArgumentException("Event '$event' not set");

		return $this->events[$event]->maxAttempts ?: NULL;
	}

	/**
	 * Returns time window for given event
	 *
	 * @param string event
	 * @return DateTime time since when we should count attempts
	 * @throws Nette\InvalidArgumentException if event was not defined
	 */
	protected function getTimeWindow($event) {
		if(!isset($this->events[$event]))
			throw new Nette\InvalidArgumentException("Event '$event' not set");

		$timeWindow = new \DateTime;
		$timeWindow->modify('-' . ($this->events[$event]->timeWindow ?: '-1 hour'));
		return $timeWindow;
	}

	/**
	 * Returns number of attempts for given $event and $uid
	 *
	 * @param string event (max. 64 chars long)
	 * @param string user identifier (max. 128 chars long)
	 * @param int number to add to the counter (0 for no modification)
	 *
	 * @return int
	 */
	protected function getNumOfAttempts($event, $uid, $increment = 0) {

		$since = $this->getTimeWindow($event);
		$this->dbConnection->begin();

		// Get last log and lock it's row for any updates outside of this transactions
		//	 -> UPDATE / DELETE statments for existing row matching this select will wait
		//   -> INSERT statments for rows that WILL match this select will wait
		$lastLog = $this->dbConnection->query(
			'SELECT [count], [lastTime] FROM %n', $this->tableName,
			'WHERE [event] = %s', $event,
			'AND [uid] = %s', $uid,
			'FOR UPDATE'
		)->fetch();

		// Insert new log
		if($lastLog === FALSE) {
			$count = 1;
			if($increment > 0) $this->dbConnection->query(
				'INSERT INTO %n', $this->tableName, '([event], [uid], [lastTime])',
				'VALUES (%s, %s, %t)', $event, $uid, new \DateTime
			);

		// Update existing log
		} else {
			// Update or reset counter if time has come
			$count = $lastLog->lastTime < $since ? 1 : max(intval($lastLog->count) + $increment, 0);
			if($increment != 0) {
				if($count > 0) {
					$this->dbConnection->query(
						'UPDATE %n', $this->tableName,
						'SET [count] = %i,', $count,
						'[lastTime] = %t', new \DateTime,
						'WHERE [event] = %s', $event,
						'AND [uid] = %s', $uid
					);
				} else {
					$this->dbConnection->query(
						'DELETE FROM %n', $this->tableName,
						'WHERE [event] = %s', $event,
						'AND [uid] = %s', $uid
					);
				}
			}
		}

		$this->dbConnection->commit();
		return $count;
	}

}
