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

 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Security;

use vBuilder,
	Nette,
	Nette\Security\IIdentity,
	Nette\Security\AuthenticationException;


/**
 * LDAP authenticator
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 21, 2012
 */
class LdapAuthenticator extends Nette\Object implements Nette\Security\IAuthenticator {
	
	const I_DN = 'dn';
	const I_NAME = 'name';
	const I_SURNAME = 'surname';
	const I_DISPLAYNAME = 'displayName';
	
	/** @var Nette\DI\IContainer DI */
	protected $context;
	
	/** @var string server URL **/
	protected $_serverUrl;
	
	/** @var string query prefix */
	protected $_queryPrefix = 'uid=';
	
	/** @var string query suffix */
	protected $_querySuffix = ',ou=users,ou=people,dc=v3net,dc=cz';
	
	
	/**
	 * Constructor
	 *
	 * @param Nette\DI\IContainer DI context container
	 */
	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;
		
		if(!function_exists('ldap_connect'))
			throw new Nette\NotSupportedException("LDAP library is not installed on the server");
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
	 * Identity factory. Creates Identity class from LDAP data
	 *
	 * @param string user id
	 * @param array of user profile data
	 *
	 * @return IIdentity
	 */
	 protected function createIdentity($id,  array $data) {
		$roles = array();
	
		$i = new Nette\Security\Identity($id, $roles, $data);		
		return $i;
	}
	

	
	/**
	 * Performs an authentication against LDAP server
	 * and returns IIdentity on success or throws AuthenticationException
	 * 
	 * @param array credentials
	 * 
	 * @return IIdentity
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials) {
		$res = ldap_connect($this->serverUrl);
		if($res) {
			ldap_set_option($res, LDAP_OPT_PROTOCOL_VERSION, 3);
			
			// DN
			$query = $this->queryPrefix . $credentials[self::USERNAME] . $this->querySuffix;
			$bind = @ldap_bind($res, $query, $credentials[self::PASSWORD]);

			if($bind) {
				
				$search = ldap_search($res, $query, '(objectclass=*)', array('displayname', 'sn', 'givenname'));
				$info = ldap_get_entries($res, $search);
				
				$userInfo = array(
					self::I_DN => $info[0]['dn'],
					self::I_NAME => $info[0]['givenname'][0],
					self::I_SURNAME => $info[0]['sn'][0],
					self::I_DISPLAYNAME => $info[0]['displayname'][0]
				);
				
				$identity = $this->createIdentity($credentials[self::USERNAME], $userInfo);
				ldap_close($res);
				return $identity;
				
			} else {
				$errNo = ldap_errno($res);
				$errMsg = ldap_error($res);
				ldap_close($res);
				
				if($errNo == 49)
					throw new AuthenticationException("Invalid credentials", self::INVALID_CREDENTIAL);
				else
					throw new AuthenticationException("LDAP server returned error: $errMsg ($errNo)", self::FAILURE);
			}
			
		} else {
			ldap_close($res);
			throw new AuthenticationException("Cannot connect to authentication server", self::FAILURE);
		}
	}

}
