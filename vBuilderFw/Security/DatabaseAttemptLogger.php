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

	/** @var DibiConnection */
	protected $dbConnection;

	/** @var string table name */
	protected $tableName = 'security_log';

	/** @var events */
	protected $events = array();

	/**
	 * Constructor
	 *
	 * @param DibiConnection database connection
	 */
	public function __construct(DibiConnection $dbConnection) {
		$this->dbConnection = $dbConnection;
	}

	/**
	 * Sets event properties
	 *
	 * @param string event name
	 * @param integer maximum number of attempts allowed
	 * @param string time window formatted for DateTime::modify()
	 * @param bool should counter be reset on success?
	 * @return self
	 * @throws Nette\InvalidArgumentException if event name is invalid
	 */
	public function setEvent($name, $maxAttempts, $timeWindow, $resetOnSuccess = TRUE) {

		if(!is_scalar($name) || $name == "")
			throw new Nette\InvalidArgumentException("Event name has to be non-empty string");

		$this->events[$name] = new \StdClass;
		$this->events[$name]->maxAttempts = $maxAttempts;
		$this->events[$name]->timeWindow = $timeWindow;
		$this->events[$name]->resetOnSuccess = $resetOnSuccess;

		return $this;
	}

	/**
	 * Returns number of remaining attempts for given event and user
	 *
	 * @return integer
	 * @throws Nette\InvalidArgumentException if event name is invalid
	 * @throws Nette\InvalidStateException if given event was not set
	 */
	public function getRemainingAttempts($event, $uid) {
		$this->checkEvent($event);

		$n = $this->getNumOfAttempts($event, $uid);
		if(($max = $this->getMaxNumOfAttempts($event)) !== NULL)
			return max($max - $n, 0);

		return 1;
	}

	/**
	 * Logs failed attempt for given event and user
	 *
	 * @param string event name
	 * @param mixed uid
	 * @return self
	 * @throws Nette\InvalidArgumentException if event name is invalid
	 * @throws Nette\InvalidStateException if given event was not set
	 */
	public function logFail($event, $uid) {
		$this->checkEvent($event);

		$this->getNumOfAttempts($event, $uid, 1);
		return $this;
	}

	/**
	 * Logs successful attempt for given event and user
	 *
	 * @param string event name
	 * @param mixed uid
	 * @return self
	 * @throws Nette\InvalidArgumentException if event name is invalid
	 * @throws Nette\InvalidStateException if given event was not set
	 */
	public function logSuccess($event, $uid) {
		$this->checkEvent($event);

		$minInt = 255<<(PHP_INT_SIZE*8)-1;

		if($this->getResetOnSuccess($event))
			$this->getNumOfAttempts($event, $uid, $minInt);

		return $this;
	}

	/**
	 * Checks event
	 *
	 * @param string event name
	 * @return void
	 * @throws Nette\InvalidArgumentException if event name is invalid
	 * @throws Nette\InvalidStateException if given event was not set
	 */
	protected function checkEvent($event) {
		if(!is_scalar($event) || $event == "")
			throw new Nette\InvalidArgumentException("Event name has to be non-empty string");

		if(!isset($this->events[$event]))
			throw new Nette\InvalidStateException("Event '$event' not set");
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
