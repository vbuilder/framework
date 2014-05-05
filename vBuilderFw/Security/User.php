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
	Nette,
	Nette\Security\IIdentity,
	Nette\Security\IAuthorizator,
	Nette\Security\AuthenticationException;

/**
 * User authorization / authentication abstraction layer
 *
 * TODO: Lazy service creation for authenticators (same as authorizator)
 * 
 * TODO: Support for concurrent logins by storage
 *	(to allow PSK usage while maintaining existing logged session)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class User extends Nette\Object {

	/**/ /** Authorization methods */
	const AUTHN_METHOD_INVALID = NULL;
	const AUTHN_METHOD_PASSWORD = 'password';
	const AUTHN_METHOD_PSK = 'psk';
	/**/

	/**/ /** Authorization sources */
	const AUTHN_SOURCE_ALL = NULL;
	const AUTHN_SOURCE_DATABASE = 'db';
	const AUTHN_SOURCE_LDAP = 'ldap';
	/**/

	/** @var string  default role for unauthenticated user */
	public $guestRole = 'guest';

	/** @var array of function(User $sender); Occurs when the user is successfully logged in */
	public $onLoggedIn;

	/** @var array of function(User $sender); Occurs when the user is logged out */
	public $onLoggedOut;

	/** @var IUserStorage Session storage for current user */
	private $storage;

	/** @var Nette\DI\IContainer */
	protected $context;

	/** @var array of IAuthenticator (associative by AUTHN_METHOD_*) */
	protected $availableAuthenticators = array();

	/** @var Nette\DI\NestedAccessor */
	public $authenticator;

	/** @var IAuthorizator */
	protected $_authorizator;

	/** @var Nette\DI\NestedAccessor */
	public $passwordHasher;

	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;

		// DI nested accessors
		$this->authenticator = new Nette\DI\NestedAccessor($context, 'user.authenticator');
		$this->passwordHasher = new Nette\DI\NestedAccessor($context, 'user.passwordHasher');

		// Update last login time
		$this->onLoggedIn[] = function ($userService) use ($context) {

			// Only for real users (no PSK, etc...s)
			try {
				if(!$userService->isInRole('user'))
					return ;

			// If role does not exist
			} catch(Nette\InvalidStateException $e) {
				return ;
			} 

			// TODO: Parametrized table name
			$tableName 	= 'security_lastLoginInfo';
			$db 		= $context->database->connection;
			$remoteAddr = $context->httpRequest->getRemoteAddress();
			$uid 		= $userService->getId();

			// Sanity check
			if($uid == NULL)
				return;

			$db->query(
					'INSERT INTO %n', $tableName, '([userId], [time], [ip], [time2], [ip2]) VALUES(' .
					'%i', $uid, ', NOW(), %s', $remoteAddr, ', NULL, NULL) ' .
					'ON DUPLICATE KEY UPDATE [time2] = [time], [ip2] = [ip], [time] = NOW(), [ip] = %s', $remoteAddr
			);
		};

		// TODO
		$this->storage = $this->context->nette->userStorage;
	}

	/**
	 * Returns user identity storage
	 * @return Nette\Security\IUserStorage
	 */
	public function getStorage() {
		return $this->storage;
	}

	/** Authentication queries ************************************************/

	/**
	 * Is this user authenticated?
	 * @return bool
	 */
	public function isLoggedIn() {
		return $this->storage->isAuthenticated();
	}

	/**
	 * Returns current user ID, if any.
	 * @return mixed
	 */
	public function getId() {
		$identity = $this->getIdentity();
		return $identity ? $identity->getId() : NULL;
	}

	/**
	 * Returns current user identity, if any.
	 * @return IIdentity|NULL
	 */
	public function getIdentity() {
		return $this->storage->getIdentity();
	}

	/** Authentication control ************************************************/

	/**
	 * Conducts the authentication process.
	 * All parameters are passed to matching authenticator.
	 * If the third parameter is an IIdentity,
	 *  it is saved without any authenticator involvement.
	 *
	 * @param authentication method
	 * @param authentication source
	 * @param mixed user id
	 * @return IIdentity
	 * @throws AuthenticationException from authenticator if authentication was not successful
	 */
	public function login($method, $source, $id) {
		$this->logout(TRUE);
		if (!$id instanceof IIdentity) {
			$handlers = $this->getAuthenticator($method, $source);
			if(!is_array($handlers)) $handlers = array($handlers);

			// Iterate through all the matching handlers
			$e = NULL;
			foreach($handlers as $handler) {
				try {
					$id = $handler->authenticate(array_slice(func_get_args(), 2));
					$e = NULL;
					break;
				} catch(AuthenticationException $e) { }

				if($e !== NULL)
					throw $e;
			}

			if(!($id instanceof IIdentity))
				throw new Nette\InvalidStateException("Authenticator returned value not implementing IIdentity");
		}

		$this->storage->setIdentity($id);
		$this->storage->setAuthenticated(TRUE);
		$this->onLoggedIn($this);

		return $id;
	}

	/**
	 * Logs out the user from the current session.
	 * @param  bool  clear the identity from persistent storage?
	 * @return void
	 */
	public function logout($clearIdentity = FALSE) {
		if ($this->isLoggedIn()) {
			$this->onLoggedOut($this);
			$this->storage->setAuthenticated(FALSE);
		}
		if ($clearIdentity) {
			$this->storage->setIdentity(NULL);
		}

		return NULL;
	}

	/** Authentication handlers ***********************************************/

	/**
	 * Registers authentication handler it's method / source.
	 *
	 * @param IAuthenticator handler
	 * @return self
	 */
	public function addAuthenticator(IAuthenticator $handler) {

		if($handler->getMethod() == self::AUTHN_METHOD_INVALID)
			throw new Nette\InvalidStateException("Authentication handler returned invalid method name");

		if(!isset($this->availableAuthenticators[$handler->getMethod()]))
			$this->availableAuthenticators[$handler->getMethod()] = array();

		if(isset($this->availableAuthenticators[$handler->getMethod()][$handler->getSource()])) {
			if(is_array($this->availableAuthenticators[$handler->getMethod()][$handler->getSource()]))
				$this->availableAuthenticators[$handler->getMethod()][$handler->getSource()][] = $handler;
			else {
				$this->availableAuthenticators[$handler->getMethod()][$handler->getSource()] = array(
						$this->availableAuthenticators[$handler->getMethod()][$handler->getSource()],
						$handler
					);
			}
		} else {
			$this->availableAuthenticators[$handler->getMethod()][$handler->getSource()] = $handler;
		}

		return $this;
	}

	/**
	 * Returns authentication handler for it's method / source.
	 * If source is not specified, array of all handlers
	 *  for given method is returned.
	 *
	 * @param string method (AUTHN_METHOD_*)
	 * @param string source (AUTHN_SOURCE_*)
	 * @param bool throw exceptions?
	 * 
	 * @return IAuthenticator|array of IAuthenticator
	 * @throws AuthenticationException if there is no handler for this type
	 */
	public function getAuthenticator($method, $source = self::AUTHN_SOURCE_ALL, $throw = TRUE) {

		if(!isset($this->availableAuthenticators[$method])) {
			if($throw) throw new Nette\InvalidArgumentException("There is no authentication handler for method " . var_export($method, TRUE));
		
			return NULL;
		}

		if($source == self::AUTHN_SOURCE_ALL)
			return $this->availableAuthenticators[$method];

		if(!isset($this->availableAuthenticators[$method][$source])) {
			if($throw) throw new Nette\InvalidArgumentException("There is no source " . var_export($source, TRUE) . " for authentication method " . var_export($method, TRUE) . " ");
		
			return NULL;
		}

		return $this->availableAuthenticators[$method][$source];
	}

	/** Authorization queries *************************************************/

	/**
	 * Returns a list of effective roles that a user has been granted.
	 * @return array
	 */
	public function getRoles() {
		if(!$this->isLoggedIn()) {
			return array($this->guestRole);
		}

		$identity = $this->getIdentity();
		return $identity && $identity->getRoles() ? $identity->getRoles() : array();
	}

	/**
	 * Is a user in the specified effective role?
	 *
	 * @param string role
	 * @param bool true, if allow deep look
	 * @return bool
	 */
	public function isInRole($role, $deepLook = TRUE) {
		$roles = $this->getRoles();
		if(in_array($role, $roles))
			return true;

		if(!$deepLook)
			return false;

		$acl = $this->getAuthorizator();
		if($acl instanceof vBuilder\Security\Authorizators\AclAuthorizator) {
			foreach($roles as $childRole) {
				if($acl->roleInheritsFrom($childRole, $role))
					return true;
			}
		}

		return false;
	}

	/**
	 * Has a user effective access to the Resource?
	 * If $resource is NULL, then the query applies to all resources.
	 * @param  string  resource
	 * @param  string  privilege
	 * @return bool
	 */
	public function isAllowed($resource = IAuthorizator::ALL, $privilege = IAuthorizator::ALL)
	{
		$authorizator = $this->getAuthorizator();
		foreach ($this->getRoles() as $role) {

			// We need to skip undefined roles (ie. LDAP users)
			if(!$authorizator->hasRole($role))
				continue;

			if ($authorizator->isAllowed($role, $resource, $privilege)) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/** Authorization handler *************************************************/

	/**
	 * Sets authorization handler.
	 * 
	 * @param  string|IAuthorizator authorizator instance or name of service
	 * @return self
	 * @throws  Nette\InvalidArgumentException if argument is invalid
	 */
	public function setAuthorizator($handler) {
		if(!($handler instanceof IAuthorizator)) {
			if(!is_string($handler))
				throw new Nette\InvalidArgumentException("Expected name of service or instance of class implementing IAuthorizator");
			/* elseif($this->context->hasService($handler))
				throw new Nette\InvalidArgumentException("Invalid authorizator given. No such service '$handler'."); */
		}

		$this->_authorizator = $handler;
		return $this;
	}


	/**
	 * Returns current authorization handler.
	 * @return IAuthorizator
	 * @throws Nette\InvalidStateException if no authorizator has been set
	 */
	public function getAuthorizator() {
		if(!isset($this->_authorizator))
			throw new Nette\InvalidStateException("No authorizator has been set");

		if(is_string($this->_authorizator)) {
			$authz = $this->context->getService($this->_authorizator);

			if(!($authz instanceof IAuthorizator))
				throw new Nette\InvalidStateException("Service $this->_authorizator returned instance of class which does not implement IAuthorizator");

			$this->_authorizator = $authz;
		}

		return $this->_authorizator;
	}

}