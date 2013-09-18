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
	vBuilder\Ldap\LdapConnection,
	vBuilder\Ldap\LdapException,
	Nette,
	Nette\Security\AuthenticationException;

/**
 * Handler for authentication against LDAP (bindpw)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class LdapBindAuthenticator extends BaseAuthenticator {

	protected $ldapConnection;
	
	/** @var string query prefix */
	protected $_queryPrefix = 'uid=';
	
	/** @var string query suffix */
	protected $_querySuffix = ',ou=users,ou=people,dc=v3net,dc=cz';

	public function __construct(IIdentityFactory $identityFactory, LdapConnection $ldapConnection, Nette\DI\IContainer $context) {
		$this->ldapConnection = $ldapConnection;

		parent::__construct($identityFactory, $context);
	}

	/**
     * Returns name of authentication method for this handler.
     * {@link User::AUTHN_METHOD_INVALID} 
     *
     * @return string
     */
	public function getMethod() {
		return User::AUTHN_METHOD_PASSWORD;
	}

	/**
     * Returns name of authentication source for this handler.
     * {@link vBuilder\Security\User::AUTHN_SOURCE_ALL} 
     *
     * @return string
     */
	public function getSource() {
		return User::AUTHN_SOURCE_LDAP;
	}

	/**
	 * Returns LDAP connection
	 * @return LdapConnection
	 */
	public function getLdapConnection() {
		return $this->ldapConnection;
	}
	
	/**
	 * Sets prefix for query string
	 *
	 * @param string
	 */
	public function setQueryPrefix($prefix) {
		$this->_queryPrefix = $prefix;
	}
	
	/**
	 * Returns query prefix
	 *
	 * @return string
	 */
	public function getQueryPrefix() {
		return $this->_queryPrefix;	
	}
	
	/**
	 * Sets suffix of query string
	 *
	 * @param string
	 */
	public function setQuerySuffix($suffix) {
		$this->_querySuffix = $suffix;
	}
	
	/**
	 * Returns query suffix
	 *
	 * @return string
	 */
	public function getQuerySuffix() {
		return $this->_querySuffix;	
	}

	/**
	 * Performs an authentication against LDAP.
	 * and returns IIdentity on success or throws AuthenticationException
	 *
	 * @note If you are using SSL, valid certificate is required for LDAP server.
	 *   otherwise you will keep getting errors like "Can't contact LDAP server (-1)".
	 *	 You can't bypass check by adding following line into /etc/ldap.conf:
	 *		TLS_REQCERT never
	 *   (apache restart might be required)
	 * 
	 * @param array credentials
	 * 
	 * @return IIdentity
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials) {

		$bindDn = $this->queryPrefix . $credentials[self::USERNAME] . $this->querySuffix;
		$this->ldapConnection->setParameters(array(
			LdapConnection::BIND_DN => $bindDn,
			LdapConnection::BIND_PASSWORD => $credentials[self::PASSWORD]
		));

		try {
			$result = $this->ldapConnection->search($bindDn, '(objectclass=*)', array('givenname', 'sn', 'displayName', 'gidNumber', 'uidNumber'));
			$info = $result->fetch();
			
			$identity = $this->identityFactory->createIdentity(
				$info,
				$this
			);

			unset($result);
			return $identity;

		} catch(LdapException $e) {
			if($e->getCode() == 49)
				throw new AuthenticationException("Invalid credentials", self::INVALID_CREDENTIAL);
			else
				throw new AuthenticationException("LDAP returned error: $errMsg ($errNo)", self::FAILURE);
		}

		return NULL;
	}

}