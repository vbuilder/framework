<?php

namespace vBuilder\Security;

use Nette;


/**
 * Legacy layer for Nette\Security\User exchangeability
 */
class NetteUserCompatibilityLayer extends Nette\Security\User
{
	protected $_user;

	public function __construct(User $user) {
		$this->_user = $user;
	}

	/**
	 * @return IUserStorage
	 */
	public function getStorage()
	{
		return $this->_user->getStorage();
	}


	/********************* Authentication ****************d*g**/


	/**
	 * Conducts the authentication process. Parameters are optional.
	 * @param  mixed optional parameter (e.g. username or IIdentity)
	 * @param  mixed optional parameter (e.g. password)
	 * @return void
	 * @throws AuthenticationException if authentication was not successful
	 */
	public function login($id = NULL, $password = NULL)
	{
		$c = $this->_user;
		return $this->_user->login($c::AUTHN_METHOD_PASSWORD, $id, $password);
	}


	/**
	 * Logs out the user from the current session.
	 * @param  bool  clear the identity from persistent storage?
	 * @return void
	 */
	public function logout($clearIdentity = FALSE)
	{
		return $this->_user->logout($clearIdentity);
	}


	/**
	 * Is this user authenticated?
	 * @return bool
	 */
	public function isLoggedIn()
	{
		return $this->_user->isLoggedIn();
	}


	/**
	 * Returns current user identity, if any.
	 * @return IIdentity|NULL
	 */
	public function getIdentity()
	{
		return $this->_user->getIdentity();
	}


	/**
	 * Returns current user ID, if any.
	 * @return mixed
	 */
	public function getId()
	{
		return $this->_user->getId();
	}


	/**
	 * Sets authentication handler.
	 * @return self
	 */
	public function setAuthenticator(Nette\Security\IAuthenticator $handler)
	{
		throw new Nette\NotSupportedException("Calling " . __CLASS__ . "::setAuthenticator() is not supported");
	}


	/**
	 * Returns authentication handler.
	 * @return Nette\Security\IAuthenticator
	 */
	public function getAuthenticator($need = TRUE)
	{
		throw new Nette\NotSupportedException("Calling " . __CLASS__ . "::getAuthenticator() is not supported");
	}


	/**
	 * Enables log out after inactivity.
	 * @param  string|int|DateTime number of seconds or timestamp
	 * @param  bool  log out when the browser is closed?
	 * @param  bool  clear the identity from persistent storage?
	 * @return self
	 */
	public function setExpiration($time, $whenBrowserIsClosed = TRUE, $clearIdentity = FALSE)
	{
		throw new Nette\NotSupportedException("Calling " . __CLASS__ . "::setExpiration() is not supported");
	}


	/**
	 * Why was user logged out?
	 * @return int
	 */
	public function getLogoutReason()
	{
		throw new Nette\NotSupportedException("Calling " . __CLASS__ . "::getLogoutReason() is not supported");
	}


	/********************* Authorization ****************d*g**/


	/**
	 * Returns a list of effective roles that a user has been granted.
	 * @return array
	 */
	public function getRoles()
	{
		return $this->_user->getRoles();
	}


	/**
	 * Is a user in the specified effective role?
	 * @param  string
	 * @return bool
	 */
	public function isInRole($role)
	{
		return $this->_user->isInRole($role);
	}


	/**
	 * Has a user effective access to the Resource?
	 * If $resource is NULL, then the query applies to all resources.
	 * @param  string  resource
	 * @param  string  privilege
	 * @return bool
	 */
	public function isAllowed($resource = Nette\Security\IAuthorizator::ALL, $privilege = Nette\Security\IAuthorizator::ALL)
	{
		return $this->_user->isAllowed($resource, $privilege);
	}


	/**
	 * Sets authorization handler.
	 * @return self
	 */
	public function setAuthorizator(Nette\Security\IAuthorizator $handler)
	{
		return $this->_user->setAuthorizator($handler);
	}


	/**
	 * Returns current authorization handler.
	 * @return Nette\Security\IAuthorizator
	 */
	public function getAuthorizator($need = TRUE)
	{
		return $this->_user->getAuthorizator();
	}

}
