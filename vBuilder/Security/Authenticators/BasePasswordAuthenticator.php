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
	vBuilder\Security\IPasswordHasher,
	vBuilder\Security\IAuthenticator,
	vBuilder\Utils\Strings,
	Nette,
	Nette\Security\AuthenticationException;

/**
 * Base authentication handler for password based methods
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
abstract class BasePasswordAuthenticator extends BaseAuthenticator {

	/** @var IPasswordHasher */
	protected $passwordHasher;

	public function __construct(IPasswordHasher $hasher, IIdentityFactory $identityFactory) {
		parent::__construct($identityFactory);
		$this->passwordHasher = $hasher;
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
	 * Returns password hasher.
	 *
	 * @return IPasswordHasher
	 */
	public function getPasswordHasher() {
		return $this->passwordHasher;
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
		if(($userData = $this->fetchUserData($credentials)) !== FALSE) {

			// Password hashing
			$ok = $this->passwordHasher->checkPassword(
				$credentials[self::PASSWORD],
				$userData->{$this->fieldName[self::PASSWORD]}
			);

			if(!$ok)
				throw new AuthenticationException("Invalid password for user " . var_export($credentials[self::USERNAME], TRUE) . ".", self::INVALID_CREDENTIAL);

			return $this->identityFactory->createIdentity(
				$userData,
				$this
			);

		} else
			throw new AuthenticationException("User " . var_export($credentials[self::USERNAME], TRUE) . " not found.", self::IDENTITY_NOT_FOUND);
	}

	/**
	 * Helper function for easy overriding of DB query
	 *
	 * @return DibiRow|FALSE
	 */
	abstract protected function fetchUserData(array $credentials);

}