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

namespace vBuilder\Security\Authenticators;

use vBuilder,
	vBuilder\Security\User,
	vBuilder\Security\IPasswordHasher,
	vBuilder\Security\IAuthenticator,
	vBuilder\Security\IIdentityFactory,
	vBuilder\Utils\Strings,
	Nette,
	Nette\Security\AuthenticationException;

/**
 * Handler for authentication against DB
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class DatabasePasswordAuthenticator extends BasePasswordAuthenticator {

	/** @var DibiConnection */
	protected $db;

	/** @var string Table name */
	protected $tableName = 'security_users';

	/** @var array translation table for table columns */
	protected $fieldName = array(
		self::ID => 'id',
		self::USERNAME => 'username',
		self::PASSWORD => 'password',
		self::EMAIL => 'email'
	);

	public function __construct(\DibiConnection $dbConnection, IPasswordHasher $hasher, IIdentityFactory $identityFactory) {
		parent::__construct($hasher, $identityFactory);
		$this->db = $dbConnection;
	}

	/**
     * Returns name of authentication source for this handler.
     * {@link vBuilder\Security\User::AUTHN_SOURCE_ALL} 
     *
     * @return string
     */
	public function getSource() {
		return User::AUTHN_SOURCE_DATABASE;
	}

	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * Returns name of the column for given field.
	 *
	 * @param field
	 * @return string
	 */
	public function getColumn($field) {
		return isset($this->fieldName[$field])
			? $this->fieldName[$field] : NULL;
	}

	/**
	 * Sets column name for a field
	 * @param string field name
	 * @param string column name
	 * @return  self
	 */
	public function setColumn($field, $column) {
		$this->fieldName[$field] = $column;
		return $this;
	}

	/**
	 * Helper function for easy overriding of DB query
	 *
	 * @return DibiRow|FALSE
	 */
	protected function fetchUserData(array $credentials) {
		$ds = $this->db->select('*')->from($this->tableName);

		if(Strings::contains($credentials[self::USERNAME], '@'))
			$ds->where('%n = %s', $this->fieldName[self::EMAIL], $credentials[self::USERNAME]);
		else
			$ds->where('%n = %s', $this->fieldName[self::USERNAME], $credentials[self::USERNAME]);

		return $ds->fetch();
	}

}