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
	vBuilder\Utils\Strings,
	vBuilder\Utils\Ldap as LdapUtils;

/**
 * Basic identity factory
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class IdentityFactory extends Nette\Object implements IIdentityFactory {

	const TABLE_ROLES = 'roles';

	/** @var Nette\DI\IContainer */
	protected $context;

	/** @var array of string */
	protected $tableName = array(
		self::TABLE_ROLES => 'security_userRoles'
	);

	/** Constructor */
	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;
	}

	/**
	 * Creates IIdentity object from obtained user data
	 *
	 * @param mixed user data
	 * @param IAuthenticator authenticator
	 *
	 * @return IIdentity
	 */
	public function createIdentity($userData, $authenticator) {

		$uid = NULL;
		$roles = array();
		$profile = array();

		// ---------------------------------------------------------------------

		// DB Password
		if($authenticator instanceof Authenticators\DatabasePasswordAuthenticator) {
			$uid = $userData->{$authenticator->getColumn($authenticator::ID)};
			$roles[] = "user:$uid";
			$profile = $userData;
		}

		// LDAP
		elseif($authenticator instanceof Authenticators\LdapBindAuthenticator) {
			$ldapData = LdapUtils::entriesToStructure($userData);

			$uid = $ldapData['dn'];
			$roles[] = "user:$uid";
			$profile = $ldapData;
		}

		// Preshared secret
		elseif($authenticator instanceof Authenticators\PresharedSecretAuthenticator) {
			$uid = Strings::intoParameterizedString('psk', array($userData->key));
			$roles[] = $uid;
			$profile = $userData;

		// Other authenticators
		} else {
			throw new Nette\NotSupportedException("Authenticator " . get_class($authenticator) . " not supported yet");
		}

		// ---------------------------------------------------------------------
		
		// Remove duplicit roles
		$roles = array_unique($roles);

		// Sanity check
		if(!is_scalar($uid) || $uid == "")
			throw new Nette\InvalidStateException("User ID has to be non-empty string or number");

		// ---------------------------------------------------------------------

		$db = $this->context->database->connection;
		$dbRoles = $db->query("SELECT [role] FROM %n", $this->tableName[self::TABLE_ROLES], 'WHERE [user] = %s', $uid)->fetchAll();
		foreach($dbRoles as $curr) {
			if(!in_array($curr->role, $roles))
				$roles[] = $curr->role;
		}

		// ---------------------------------------------------------------------

		// Identity
		$identity = new Nette\Security\Identity(
			$uid,
			$roles,
			$profile
		);


		return $identity;
	}

}