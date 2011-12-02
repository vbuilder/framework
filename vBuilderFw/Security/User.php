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
	 dibi;

/**
 * User data class
 *
 * @Table(name="security_users")
 *
 * @Behavior(Secure)
 * 
 * @Column(id, pk, type="integer", generatedValue)
 * @Column(username, type="string")
 * @Column(password, type="string")
 * @Column(email, type="string")
 * @Column(registrationTime, type="DateTime")
 * @Column(roles, type="OneToMany", table="security_userRoles", joinOn="id=user")
 * @Column(lastLoginInfo, type="OneToOne", entity="vBuilder\Security\LastLoginInfo", mappedBy="vBuilder\Security\User", joinOn="id=userId")
 *
 * @author Adam Staněk (V3lbloud)
 * @since Mar 4, 2011
 */
class User extends vBuilder\Orm\ActiveEntity implements Nette\Security\IIdentity {

	public function __construct() {
		$this->onCreate[] = \callback('vBuilder\Security', 'onUserCreated');
		$this->onPostDelete[] = \callback('vBuilder\Security', 'onUserDeleted');

		call_user_func_array(array('parent', '__construct'), func_get_args());
	}

	protected static function & getMetadataInternal() {
		$m1 = parent::getMetadataInternal();
		
		// NESMIM POUZIT NAS CONFIG, PROTOZE JE ZAVISLY NA UZIVATELI!
		//	=> infinite loop
		//$config = Nette\Environment::getContext()->config->getGlobalScope();
		
		$config = Nette\Environment::getConfig();
		if(isset($config["security"]["user"])) {
			$m2 = new vBuilder\Orm\ConfigMetadata((array) $config["security"]["user"]);
			
			$metadata = new vBuilder\Orm\MergedMetadata($m1, $m2);
			return $metadata;
		}
		
		return $m1;
	}

	/**
	 * Returns user display name. It can be overloaded to contain real user name and
	 * surname or for example nick name.
	 * 
	 * @return string 
	 */
	public function getDisplayName() {
				
		if($this->metadata->hasField('name') && $this->metadata->hasField('surname')) {
			return $this->name . ' ' . $this->surname;
		}
		
		return $this->username;
	}
	
	/**
	 * Returns user id. Required implementation because of IIdentity.
	 * 
	 * @return int UID
	 */
	public function getId() {
		return $this->defaultGetter('id');
	}

	/**
	 * Returns true, if user is in effective role. In difference from 
	 * Nette\Http\User::isInRole it can make a deep look (even inheriented roles
	 * will be tolerated)
	 * 
	 * @param string role
	 * @param bool true, if allow deep look
	 * @return bool
	 */
	public function isInRole($role, $deepLook = true) {
		$roles = $this->getRoles();
		if(in_array($role, $roles))
			return true;

		if(!$deepLook)
			return false;

		$acl = Nette\Environment::getUser()->getAuthorizator();
		if($acl instanceof Nette\Security\Permission) {
			foreach($roles as $childRole) {
				if($acl->roleInheritsFrom($childRole, $role))
					return true;
			}
		}

		return false;
	}

	/**
	 * Returns array of defined roles (non-recursive)
	 * 
	 * @return array roles
	 */
	public function getRoles() {
		return array_merge($this->defaultGetter('roles')->toArray(), array("User"));
	}

	/**
	 * Sets user's roles
	 *
	 * @param array of role names
	 */
	public function setRoles(array $roles) {
		// Vyzaduju ACL system pro kontrolu roli
		$acl = Nette\Environment::getUser()->getAuthorizator();
		if($acl instanceof Nette\Security\Permission) {
			$newRoles = array();

			// Zjednodusim seznam roli podle dedicnosti
			// Zaroven zkontroluju, jestli mam opravneni danou roli priradit
			//  a jestli dana role vubec existuje
			foreach($roles as $curr) {
				if(!$acl->hasRole($curr))
					throw new \InvalidArgumentException("Role '$curr' does not exist");

				$skip = false;
				foreach($roles as $curr2) {
					if($acl->roleInheritsFrom($curr2, $curr)) {
						$skip = true;
						break;
					}
				}

				if(!$skip) {
					$violation = true;
					foreach(Nette\Environment::getUser()->getRoles() as $curr2) {
						// roleInheritsFrom($role, $inherits)
						// Bacha na to, ze Administrator musi opravdu dedit z tech roli
						// allow('Administrator', ALL, ALL) samozrejme neni reseni
						if($curr2 == $curr || $acl->roleInheritsFrom($curr2, $curr)) {
							$violation = false;
							break;
						}
					}

					if($violation) {
						throw new SecurityException("Cannot raise user '".$this->getId()."' rights to '".$curr."', which is more than I have (".implode(",", Nette\Environment::getUser()->getRoles()).")", SecurityException::OPERATION_NOT_PERMITTED);
					}

					$newRoles[] = $curr;
				}
			}

			$this->data->roles = $newRoles;
		} else
			throw new \LogicException('Authorization handlers not derived from Nette\\Security\\Permission are not supported');
	}

	/**
	 * Checks password against it's stored hash
	 *
	 * @param string test password
	 *
	 * @return boolean true if password matches
	 */
	public function checkPassword($password) {
		$hasher = $this->context->passwordHashProvider;
		return $hasher->checkPassword($password, $this->data->password);
	}

	/**
	 * Sets user's password
	 *
	 * @param string password in plain text
	 */
	public function setPassword($newPassword) {
		$hasher = $this->context->passwordHashProvider;
		$this->data->password = $hasher->hashPassword($newPassword);
	}

	/**
	 * ACL permission assertion for allowing editing own user profile
	 * 
	 * @param Nette\Security\Permission $acl
	 * @param string $role
	 * @param string $resource
	 * @param string $privilege
	 * @return bool 
	 */
	public static function permissionOwnProfileAssert(Nette\Security\Permission $acl, $role, $resource, $privilege) {
		if(!is_object($acl->getQueriedResource()) || !$acl->getQueriedResource() instanceof self)
			return false;
		$user = $acl->getQueriedResource();

		// Pokud edituju sam sebe, je vse v poradku
		if($user->getId() == Nette\Environment::getUser()->getId())
			return true;
	}

	/**
	 * ACL permission assertion for checking if user manager is not trying to update user with 
	 *  higher privileges.
	 * 
	 * @param Nette\Security\Permission $acl
	 * @param string $role
	 * @param string $resource
	 * @param string $privilege
	 * @return bool 
	 */
	public static function permissionNoHigherUserAssert(Nette\Security\Permission $acl, $role, $resource, $privilege) {
		if(!is_object($acl->getQueriedResource()) || !$acl->getQueriedResource() instanceof self)
			return false;
		$user = $acl->getQueriedResource();

		$myRoles = Nette\Environment::getUser()->getRoles();

		foreach($user->getRoles() as $curr) {
			$violation = true;
			foreach($myRoles as $curr2) {
				if($curr2 == $curr || $acl->roleInheritsFrom($curr2, $curr)) {
					$violation = false;
					break;
				}
			}

			if($violation) {
				return false;
			}
		}

		return true;
	}

}
