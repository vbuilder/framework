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
	vBuilder\Security\IIdentityFactory,
	vBuilder\Security\IAuthenticator,
	vBuilder\Utils\Strings,
	Nette,
	Nette\Security\AuthenticationException,
	DateTime;

/**
 * Handler for authentication with preshared secret key (PSK)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class DatabasePskAuthenticator extends BaseAuthenticator {

	const PSK = 0;
	const EXPIRATION = 5;
	const NOTE = 7;

	const PSK_NOT_FOUND = 5;
	const INVALID_PSK = 6;

	/** @var DibiConnection */
	protected $db;

	/** @var string Table name */
	protected $tableName = 'security_psk';

	/** @var array translation table for table columns */
	protected $fieldName = array(
		self::PSK => 'key',
		self::EXPIRATION => 'expiration',
		self::NOTE => 'note'
	);

	/** @var StdClass */
	protected $sessionCache;

	public function __construct(\DibiConnection $dbConnection, IIdentityFactory $identityFactory, Nette\DI\Container $context) {
		parent::__construct($identityFactory);
		$this->db = $dbConnection;

		$sessionSection = $context->session->getSection(strtr(__CLASS__, '\\', '.'));
		$this->sessionCache = &$sessionSection->sessionCache;
		if($this->sessionCache == NULL) $this->sessionCache = new \StdClass;
	}

	/**
     * Returns name of authentication method for this handler.
     * {@link User::AUTHN_METHOD_INVALID}
     *
     * @return string
     */
	public function getMethod() {
		return User::AUTHN_METHOD_PSK;
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
	 * Performs an authentication against DB.
	 * and returns IIdentity on success or throws AuthenticationException
	 *
	 * @param array credentials
	 *
	 * @return IIdentity
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials) {
		if(($record = $this->fetchRecord($credentials)) !== FALSE) {

			// Is PSK still valid?
			$now = new DateTime;
			$ok = $record->{$this->fieldName[self::EXPIRATION]} === NULL || $record->{$this->fieldName[self::EXPIRATION]} >= $now;

			if(!$ok)
				throw new AuthenticationException("Invalid PSK " . var_export($credentials[self::PSK], TRUE) . ".", self::INVALID_PSK);

			return $this->identityFactory->createIdentity(
				$record,
				$this
			);

		} else
			throw new AuthenticationException("PSK " . var_export($credentials[self::PSK], TRUE) . " not found.", self::PSK_NOT_FOUND);
	}

	/**
	 * Helper function for easy overriding of DB query
	 *
	 * @return DibiRow|FALSE
	 */
	protected function fetchRecord(array $credentials) {
		if(!isset($this->sessionCache->{$credentials[self::PSK]})) {

			$ds = $this->db->select('*')->from($this->tableName);
			$ds->where('%n = %s', $this->fieldName[self::PSK], $credentials[self::PSK]);

			$record = $ds->fetch();
			if($record !== FALSE)
				$this->sessionCache->{$credentials[self::PSK]} = $record;
			else
				return FALSE;
		}

		return $this->sessionCache->{$credentials[self::PSK]};
	}

	/**
	 * Creates new preshared secret
	 *
	 * @param string|NULL note
	 * @param DateTime|NULL expiration date
	 * @return string[16]
	 * @throws Nette\InvalidArgumentException if invalid expiration date
	 */
	public function createPsk($expiration = NULL, $note = NULL) {

		if($expiration !== NULL && !($expiration instanceof \DateTime))
			throw new Nette\InvalidArgumentException("Expected NULL or DateTime instance");

		while(true) {
			$psk = Strings::randomHumanToken(16);

			$values = array(
				$this->fieldName[self::PSK] => $psk,
				$this->fieldName[self::EXPIRATION] => $expiration,
				$this->fieldName[self::NOTE] => $note
			);

			try {
				$this->db->insert($this->tableName, $values)->execute();
				break;

			} catch(\DibiException $e) {
				if($e->getCode() != 1062)
					throw $e;
			}
		}

		return $psk;
	}

}