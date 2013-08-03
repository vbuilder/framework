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
	Nette\Security\AuthenticationException;

/**
 * Handler for authentication against LDAP (bindpw)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class LdapBindAuthenticator extends BaseAuthenticator {

	/** @var string server URL **/
	protected $_serverUrl;
	
	/** @var string query prefix */
	protected $_queryPrefix = 'uid=';
	
	/** @var string query suffix */
	protected $_querySuffix = ',ou=users,ou=people,dc=v3net,dc=cz';

	public function __construct(IIdentityFactory $identityFactory, Nette\DI\IContainer $context) {
		parent::__construct($identityFactory, $context);
		
		if(!function_exists('ldap_connect'))
			throw new Nette\NotSupportedException("LDAP library is not installed on the server");
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
	 * Sets URL of the LDAP server
	 * Example: ldaps://ldap.v3net.cz
	 *
	 * @param string url
	 */
	public function setServerUrl($url) {
		$this->_serverUrl = $url;
	}
	
	/**
	 * Returns URL of the LDAP server
	 *
	 * @return string
	 */
	public function getServerUrl() {
		return $this->_serverUrl;
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

		// Initializes resource (does not perfom connect)
		$res = ldap_connect($this->serverUrl);
		if($res) {
			ldap_set_option($res, LDAP_OPT_PROTOCOL_VERSION, 3);
			
			// DN
			$query = $this->queryPrefix . $credentials[self::USERNAME] . $this->querySuffix;
			$bind = @ldap_bind($res, $query, $credentials[self::PASSWORD]);

			if($bind) {
				
				$search = ldap_search($res, $query, '(objectclass=*)', array('displayname', 'sn', 'givenname'));
				$info = ldap_get_entries($res, $search);
				
				$identity = $this->identityFactory->createIdentity(
					$info,
					$this
				);

				ldap_close($res);
				return $identity;
				
			} else {
				$errNo = ldap_errno($res);
				$errMsg = ldap_error($res);
				ldap_close($res);
				
				if($errNo == 49)
					throw new AuthenticationException("Invalid credentials", self::INVALID_CREDENTIAL);
				else
					throw new AuthenticationException("LDAP returned error: $errMsg ($errNo)", self::FAILURE);
			}
			
		} else {
			ldap_close($res);
			throw new AuthenticationException("Error while creating LDAP resource", self::FAILURE);
		}

		return NULL;
	}

}