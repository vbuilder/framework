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
	Nette;

/**
 * Client authenticator against data in DB
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 27, 2014
 */
class DatabaseClientAuthenticator extends Nette\Object implements IClientAuthenticator {

	/** @var DibiConnection @inject */
	public $dbConnection;

	/** @var vBuilder\Security\IPasswordHasher @inject */
	public $hasher;

	/** @var string table name */
	protected $tableName = 'oauth2_clients';

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
	 * @inheritDoc
  	 */
	public function authenticate($clientId, $clientSecret = NULL) {
		$clientData = $this->dbConnection->query(
			"SELECT * FROM %n",	$this->tableName,
			"WHERE [clientId] = %s", $clientId
		)->fetch();

		if($clientData === FALSE) return FALSE;

		// Check client secret
		if($clientData->secret !== NULL || $clientSecret !== NULL) {
			if(!$this->hasher->checkPassword($clientSecret, $clientData->secret))
				return FALSE;
		}

		return new Client($clientData->clientId);
	}

}