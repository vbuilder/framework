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
 * Saves last login time / IP to database
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 3, 2014
 */
class DatabaseLogger extends Nette\Object {

	/**/ /** Keys */
	const TIME = 'time';
	const IP_ADDRESS = 'ipAddress';
	/**/

	/** @var DibiConnection */
	protected $db;

	/** @var Nette\Http\Request */
	protected $httpRequest;

	/** @var string Table name */
	protected $tableName = 'security_lastLoginInfo';

	public function __construct(\DibiConnection $dbConnection, Nette\Http\Request $httpRequest) {
		$this->db = $dbConnection;
		$this->httpRequest = $httpRequest;
	}

	/**
	 * Returns table name
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * Sets table name
	 *
	 * @param string table name
	 * @return self
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
		return $this;
	}

	/**
	 * Returns last login info for given user
	 *
	 * @param integer user id
	 * @return NULL|array with keys DatabaseLogger::TIME, DatabaseLogger::IP_ADDRESS
	 */
	public function getLastLoginInfo($userId) {
		$lastLoginInfo = $this->db->query(
			"SELECT [time2], [ip2] FROM %n", $this->tableName,
			"WHERE [userId] = %i", $userId
		)->fetch();

		if($lastLoginInfo === FALSE) return NULL;

		return array(
			self::TIME => $lastLoginInfo->time2,
			self::IP_ADDRESS => $lastLoginInfo->ip2
		);
	}

	public function onLoggedIn(User $service) {

		// Only for real users (no PSK, etc...s)
		try {
			if(!$service->isInRole('user'))
				return ;

		// If role does not exist
		} catch(Nette\InvalidStateException $e) {
			return ;
		}

		$remoteAddr = $this->httpRequest->getRemoteAddress();
		$uid 		= $service->getId();

		// Sanity check
		if($uid == NULL)
			return;

		$this->db->query(
				'INSERT INTO %n', $this->tableName, '([userId], [time], [ip], [time2], [ip2]) VALUES(' .
				'%i', $uid, ', NOW(), %s', $remoteAddr, ', NULL, NULL) ' .
				'ON DUPLICATE KEY UPDATE [time2] = [time], [ip2] = [ip], [time] = NOW(), [ip] = %s', $remoteAddr
		);

	}

}