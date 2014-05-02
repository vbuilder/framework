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

use Nette,
	Nette\Security\IIdentity;


/**
 * Dummy user storage
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 11, 2014
 */
class DummyUserStorage extends Nette\Object implements Nette\Security\IUserStorage {

	/** @var bool */
	private $_authState = FALSE;

	/** @var IIdentity|NULL */
	private $_identity = NULL;

	/**
	 * Sets the authenticated status of this user.
	 * @param  bool
	 * @return void
	 */
	function setAuthenticated($state) {
		$this->_authState = (bool) $state;
	}

	/**
	 * Is this user authenticated?
	 * @return bool
	 */
	function isAuthenticated() {
		return $this->_authState;
	}

	/**
	 * Sets the user identity.
	 * @return void
	 */
	function setIdentity(IIdentity $identity = NULL) {
		$this->_identity = $identity;
	}

	/**
	 * Returns current user identity, if any.
	 * @return Nette\Security\IIdentity|NULL
	 */
	function getIdentity() {
		return $this->_identity;
	}

	/**
	 * Enables log out from the persistent storage after inactivity.
	 * @param  string|int|DateTime number of seconds or timestamp
	 * @param  int Log out when the browser is closed | Clear the identity from persistent storage?
	 * @return void
	 */
	function setExpiration($time, $flags = 0) {

	}

	/**
	 * Why was user logged out?
	 * @return int
	 */
	function getLogoutReason() {
		return self::BROWSER_CLOSED;
	}

}