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

namespace vBuilder\Security\Authorizators;

use vBuilder,
	vBuilder\Utils\Strings,
	Nette,
	Nette\Security\IResource,
	Nette\Security\IRole;

/**
 * Basic ACL authorization layer.
 * Mostly based on Nette\Security\Permission,
 *  but it cannot be extended because of final/private keywording.
 *
 * As opposed to Nette Permission class, the role / resource checking has been removed
 * when adding objects (which was pain in the ass with circual dependencies of different app modules).
 * The check is done lazily on first call any of the query methods.
 *
 * You can also use compound names for roles and resources. Compound names
 * have implicit parents and allows you to use parametrized resources / roles even
 * without the need to explicitly define them.
 *
 * For example let's say we have created resource 'file' but we need to specify permissions
 * to each individual file and we don't know anything about them (how many, etc...).
 * Usually you would need to create ACL resources for each file.
 * With compound names you can just query 'file:452' which will check for
 * all rules to this individual file with maintaining the implicit ownership of parent resource
 * 'file' to simplify definition of administrative roles.
 * See: vBuilder\Utils\Strings::intoParameterizedString() for creating compound names.
 *
 * @copyright  Copyright (c) 2005, 2007 Zend Technologies USA Inc.
 * @author Adam Staněk (V3lbloud), David Grudl
 * @since Aug 16, 2013
 *
 * @property-read array $roles
 * @property-read array $resources
 * @property-read mixed $queriedRole
 * @property-read mixed $queriedResource
 */
class AclAuthorizator extends Nette\Object implements Nette\Security\IAuthorizator {

	/** @var bool should I check the roles / resources? */
	protected $dirty = false;

	/** @var bool has been the setup already called? */
	protected $setup = false;

	/** @var array of function(AclAuthorizator $authorizator); Occurs when first query is requested (on lazy initialization)
	 * We don't want it to be public, because we need to check if the object wasn't initialized yet.
	 * Use addSetupCallback() instead.
	 */
	protected $onSetup = array();

	// *************************************************************************

	/** @var array  Role storage */
	protected $roles = array();

	/** @var array  Resource storage */
	protected $resources = array();

	/** @var array  Access Control List rules; whitelist (deny everything to all) by default */
	protected $rules = array(
		'allResources' => array(
			'allRoles' => array(
				'allPrivileges' => array(
					'type' => self::DENY,
					'assert' => NULL,
				),
				'byPrivilege' => array(),
			),
			'byRole' => array(),
		),
		'byResource' => array(),
	);

	/** @var mixed */
	protected $queriedRole, $queriedResource;

	// *************************************************************************

	/**
	 * Returns array of roles which have permission to use resource.
	 *
	 * @warning Please note that function assumes 'deny all' policy
	 *    so if the allow rule does not explicitly exist it assumes
	 *	  that role is not allowed. All ['allRoles']['allResources'] rules
	 *	  are ignored.
	 *
	 * @param string|array|AclAuthorizator::ALL resource name
	 * @param string|AclAuthorizator::ALL privilege name
	 * @return array of role names
	 */
	public function getRolesAllowedFor($resource = self::ALL, $privilege = self::ALL) {
		// !!!
		$this->querying();

		$allowedRoles = array();
		$byRole = array();

		if($resource !== self::ALL) {
			$resourcesToProcess = is_array($resource) ? $resource : array($resource);

			if(count($resourcesToProcess) == 0)
				throw new Nette\InvalidArgumentException("No resources given");

			foreach($resourcesToProcess as $resource)
				$this->checkResource($resource, FALSE);

			$resource = array_shift($resourcesToProcess);
			$visited = array();

			while($resource !== NULL) {
				if(isset($this->rules['byResource'][$resource]) && !isset($visited[$resource])) {
					$visited[$resource] = TRUE;
					$byRole[] = &$this->rules['byResource'][$resource]['byRole'];
				}

				if(isset($this->resources[$resource])) {
					$resource = $this->resources[$resource]['parent'];

					if($resource === NULL && count($resourcesToProcess) > 0)
						$resource = array_shift($resourcesToProcess);

				} elseif($this->isCompoundName($resource)) {
					list($name, $params) = Strings::parseParametrizedString($resource);
					$resource = $name;

				} else {
					throw new Nette\InvalidStateException("Resource '$resource' does not exist.");
				}
			}
		}

		// At last I will be matching 'all resources' rules
		$byRole[] = &$this->rules['allResources']['byRole'];

		// Check all mentioned roles for valid rules
		$allowedRoles = array();
		$deniedRoles = array();
		foreach($byRole as $curr) {
			foreach($curr as $role => $rules) {
				if(in_array($role, $allowedRoles)) continue;

				$rulesToCheck = array();
				if($privilege !== NULL && isset($rules['byPrivilege'][$privilege]))
					$rulesToCheck[] = &$rules['byPrivilege'][$privilege];
				if(isset($rules['allPrivileges']))
					$rulesToCheck[] = &$rules['allPrivileges'];

				foreach($rulesToCheck as $rule) {
					if ($rule['assert'] !== NULL && $rule['assert']->__invoke($this, $role, $resource, $privilege)) {

						// ALLOW
						if($rule['type'] === self::ALLOW)
							if(!in_array($role, $deniedRoles)) $allowedRoles[] = $role;

						// DENY -> only suppress future rules
						elseif($rule['type'] === self::DENY)
							$deniedRoles[] = $role;

						break;

					} elseif (self::DENY === $rule['type']) {
						// DENY -> only suppress future rules
						$deniedRoles[] = $role;
						break;

					} else {

						// ALLOW
						if(!in_array($role, $deniedRoles)) $allowedRoles[] = $role;
						break;
					}
				}
			}
		}

		return $allowedRoles;
	}

	/**
	 * Helper function which is called before any query method
	 *
	 * @return void
	 */
	protected function querying() {
		// First time setup
		if(!$this->setup) {
			$this->setup = true;
			$this->setup();
		}

		// Check dirty roles / resources
		// Note: we actually don't have to check the rules, because rules for non-existing roles / resources
		// won't hurt anything and the query itself is already checked by isAllowed().
		// This also allows to be little bit more tolerant and allow rules to stay in DB
		// even if they might be removed from the code in distant future.
		if($this->dirty) {
			$this->dirty = false;

			// Check role hierarchy
			// Note: we don't have to check childrens because we are maintaing them by this method.
			foreach($this->roles as $name => $role) {
				foreach($role['parents'] as $parentRoleName => $foo) {
					// Check if parent exists
					if(!isset($this->roles[$parentRoleName]) )
						throw new Nette\InvalidStateException("Role '$parentRoleName' does not exist.");

					// Maintaining the reverse relation
					if(!in_array($name, $this->roles[$parentRoleName]['children']))
						$this->roles[$parentRoleName]['children'][] = $parentRoleName;
				}
			}

			// Check resource hierarchy
			// Note: we don't have to check children because we are maintaing them by this method.
			foreach($this->resources as $name => $res) {
				if($res['parent'] != '') {
					// Check if parent exists
					if(!isset($this->resources[$res['parent']]) )
						throw new Nette\InvalidStateException("Resource '" . $res['parent'] . "' does not exist.");

					// Maintaining the reverse relation
					if(!in_array($name, $this->resources[$res['parent']]['children']))
						$this->resources[$res['parent']]['children'][] = $name;
				}
			}
		}
	}

	/**
	 * Helper function which is called before any role / resource is altered
	 *
	 * @return void
	 */
	protected function updating() {
		$this->dirty = true;
	}

	/**
	 * Helper function which is called before the first query
	 *
	 * @return void
	 */
	protected function setup() {

		// Basic roles
		$this->addRole('guest');
		$this->addRole('user', 'guest');
		$this->addRole('psk', 'guest');

		// Trigger registered initializators
		foreach($this->onSetup as $cb)
			Nette\Callback::create($cb)->invokeArgs(array($this));
	}

	/**
	 * Adds new initialization callback.
	 * This callback will be called only once, before the first query.
	 *
	 * @param callable
	 * @return self
	 * @throws Nette\InvalidStateException if class has been already initialized
	 */
	public function addSetupCallback($cb) {
		if(is_array($cb) && count($cb) == 2)
			$cb = callback($cb[0], $cb[1]);
		else if(!is_callable($cb))
			throw new Nette\InvalidArgumentException("Expected callable");

		if($this->setup)
			throw new Nette\InvalidStateException(get_called_class() . ' already initialized');

		$this->onSetup[] = $cb;
	}

	// *************************************************************************

	/********************* roles ****************d*g**/



	/**
	 * Adds a Role to the list. The most recently added parent
	 * takes precedence over parents that were previously added.
	 * @param  string
	 * @param  string|array
	 * @throws Nette\InvalidArgumentException
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	public function addRole($role, $parents = NULL)
	{
		// !!!
		$this->updating();

		$this->checkRole($role, FALSE);
		if (isset($this->roles[$role])) {
			throw new Nette\InvalidStateException("Role '$role' already exists in the list.");
		}

		$roleParents = array();

		if ($parents !== NULL) {
			if (!is_array($parents)) {
				$parents = array($parents);
			}

			foreach ($parents as $parent) {
				// !!!
				$this->checkRole($parent, FALSE);
				$roleParents[$parent] = TRUE;
				// $this->roles[$parent]['children'][$role] = TRUE;
			}
		}

		$this->roles[$role] = array(
			'parents'  => $roleParents,
			'children' => array(),
		);

		return $this;
	}



	/**
	 * Returns TRUE if the Role exists in the list.
	 * @param  string
	 * @return bool
	 */
	public function hasRole($role)
	{
		// !!!
		$this->querying();

		$this->checkRole($role, FALSE);

		if($this->isCompoundName($role))
			list($role, $params) = Strings::parseParametrizedString($role);

		return isset($this->roles[$role]);
	}



	/**
	 * Checks whether Role is valid and exists in the list.
	 * @param  string
	 * @param  bool
	 * @throws Nette\InvalidStateException
	 * @return void
	 */
	private function checkRole($role, $need = TRUE)
	{
		if (!is_string($role) || $role === '') {
			throw new Nette\InvalidArgumentException("Role must be a non-empty string.");

		} elseif ($need && !isset($this->roles[$role])) {
			throw new Nette\InvalidStateException("Role '$role' does not exist.");
		}
	}



	/**
	 * Returns all Roles.
	 * @return array
	 */
	public function getRoles()
	{
		// !!!
		$this->querying();

		return array_keys($this->roles);
	}



	/**
	 * Returns existing Role's parents ordered by ascending priority.
	 * @param  string
	 * @return array
	 */
	public function getRoleParents($role)
	{
		// !!!
		$this->querying();

		$this->checkRole($role);
		return array_keys($this->roles[$role]['parents']);
	}



	/**
	 * Returns TRUE if $role inherits from $inherit. If $onlyParents is TRUE,
	 * then $role must inherit directly from $inherit.
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @return bool
	 */
	public function roleInheritsFrom($role, $inherit, $onlyParents = FALSE)
	{
		// !!!
		$this->querying();

		$this->checkRole($role, FALSE);
		$this->checkRole($inherit, FALSE);

		if(!isset($this->roles[$inherit]))
			return FALSE;

		if(!isset($this->roles[$role]) && $this->isCompoundName($role)) {
			list($role, $params) = Strings::parseParametrizedString($role);

			if($role == $inherit) return TRUE;
			elseif($onlyParents) return FALSE;
		}

		$inherits = isset($this->roles[$role]['parents'][$inherit]);

		if ($inherits || $onlyParents) {
			return $inherits;
		}

		foreach ($this->roles[$role]['parents'] as $parent => $foo) {
			if ($this->roleInheritsFrom($parent, $inherit)) {
				return TRUE;
			}
		}

		return FALSE;
	}



	/**
	 * Removes the Role from the list.
	 *
	 * @param  string
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	/* public function removeRole($role)
	{
		$this->checkRole($role);

		foreach ($this->roles[$role]['children'] as $child => $foo) {
			unset($this->roles[$child]['parents'][$role]);
		}

		foreach ($this->roles[$role]['parents'] as $parent => $foo) {
			unset($this->roles[$parent]['children'][$role]);
		}

		unset($this->roles[$role]);

		foreach ($this->rules['allResources']['byRole'] as $roleCurrent => $rules) {
			if ($role === $roleCurrent) {
				unset($this->rules['allResources']['byRole'][$roleCurrent]);
			}
		}

		foreach ($this->rules['byResource'] as $resourceCurrent => $visitor) {
			if (isset($visitor['byRole'])) {
				foreach ($visitor['byRole'] as $roleCurrent => $rules) {
					if ($role === $roleCurrent) {
						unset($this->rules['byResource'][$resourceCurrent]['byRole'][$roleCurrent]);
					}
				}
			}
		}

		return $this;
	} */



	/**
	 * Removes all Roles from the list.
	 *
	 * @return Permission  provides a fluent interface
	 */
	/* public function removeAllRoles()
	{
		$this->roles = array();

		foreach ($this->rules['allResources']['byRole'] as $roleCurrent => $rules) {
			unset($this->rules['allResources']['byRole'][$roleCurrent]);
		}

		foreach ($this->rules['byResource'] as $resourceCurrent => $visitor) {
			foreach ($visitor['byRole'] as $roleCurrent => $rules) {
				unset($this->rules['byResource'][$resourceCurrent]['byRole'][$roleCurrent]);
			}
		}

		return $this;
	} */



	/********************* resources ****************d*g**/



	/**
	 * Adds a Resource having an identifier unique to the list.
	 *
	 * @param  string
	 * @param  string
	 * @throws Nette\InvalidArgumentException
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	public function addResource($resource, $parent = NULL)
	{
		// !!!
		$this->updating();

		$this->checkResource($resource, FALSE);

		if (isset($this->resources[$resource])) {
			throw new Nette\InvalidStateException("Resource '$resource' already exists in the list.");
		}

		if ($parent !== NULL) {
			// !!!
			$this->checkResource($parent, FALSE);
			// $this->resources[$parent]['children'][$resource] = TRUE;
		}

		$this->resources[$resource] = array(
			'parent'   => $parent,
			'children' => array()
		);

		return $this;
	}



	/**
	 * Returns TRUE if the Resource exists in the list.
	 * @param  string
	 * @return bool
	 */
	public function hasResource($resource)
	{
		// !!!
		$this->querying();

		$this->checkResource($resource, FALSE);

		if($this->isCompoundName($resource))
			list($resource, $params) = Strings::parseParametrizedString($resource);

		return isset($this->resources[$resource]);
	}



	/**
	 * Checks whether Resource is valid and exists in the list.
	 * @param  string
	 * @param  bool
	 * @throws Nette\InvalidStateException
	 * @return void
	 */
	private function checkResource($resource, $need = TRUE)
	{
		if (!is_string($resource) || $resource === '') {
			throw new Nette\InvalidArgumentException("Resource must be a non-empty string.");

		} elseif ($need && !isset($this->resources[$resource])) {
			throw new Nette\InvalidStateException("Resource '$resource' does not exist.");
		}
	}



	/**
	 * Returns all Resources.
	 * @return array
	 */
	public function getResources()
	{
		return array_keys($this->resources);
	}



	/**
	 * Returns TRUE if $resource inherits from $inherit. If $onlyParents is TRUE,
	 * then $resource must inherit directly from $inherit.
	 *
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @return bool
	 */
	public function resourceInheritsFrom($resource, $inherit, $onlyParent = FALSE)
	{
		// !!!
		$this->querying();

		$this->checkResource($resource, FALSE);
		$this->checkResource($inherit, FALSE);

		if(!isset($this->resources[$inherit]))
			return FALSE;

		if(!isset($this->resources[$resource]) && $this->isCompoundName($resource)) {
			list($resource, $params) = Strings::parseParametrizedString($resource);

			if($resource == $inherit) return TRUE;
			elseif($onlyParents) return FALSE;
		}

		if ($this->resources[$resource]['parent'] === NULL) {
			return FALSE;
		}

		$parent = $this->resources[$resource]['parent'];
		if ($inherit === $parent) {
			return TRUE;

		} elseif ($onlyParent) {
			return FALSE;
		}

		while ($this->resources[$parent]['parent'] !== NULL) {
			$parent = $this->resources[$parent]['parent'];
			if ($inherit === $parent) {
				return TRUE;
			}
		}

		return FALSE;
	}



	/**
	 * Removes a Resource and all of its children.
	 *
	 * @param  string
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	/* public function removeResource($resource)
	{
		// !!!
		$this->updating();

		$this->checkResource($resource);

		$parent = $this->resources[$resource]['parent'];
		if ($parent !== NULL) {
			unset($this->resources[$parent]['children'][$resource]);
		}

		$removed = array($resource);
		foreach ($this->resources[$resource]['children'] as $child => $foo) {
			$this->removeResource($child);
			$removed[] = $child;
		}

		foreach ($removed as $resourceRemoved) {
			foreach ($this->rules['byResource'] as $resourceCurrent => $rules) {
				if ($resourceRemoved === $resourceCurrent) {
					unset($this->rules['byResource'][$resourceCurrent]);
				}
			}
		}

		unset($this->resources[$resource]);
		return $this;
	} */



	/**
	 * Removes all Resources.
	 * @return Permission  provides a fluent interface
	 */
	/* public function removeAllResources()
	{
		foreach ($this->resources as $resource => $foo) {
			foreach ($this->rules['byResource'] as $resourceCurrent => $rules) {
				if ($resource === $resourceCurrent) {
					unset($this->rules['byResource'][$resourceCurrent]);
				}
			}
		}

		$this->resources = array();
		return $this;
	} */



	/********************* defining rules ****************d*g**/



	/**
	 * Allows one or more Roles access to [certain $privileges upon] the specified Resource(s).
	 * If $assertion is provided, then it must return TRUE in order for rule to apply.
	 *
	 * @param  string|array|AclAuthorizator::ALL  roles
	 * @param  string|array|AclAuthorizator::ALL  resources
	 * @param  string|array|AclAuthorizator::ALL  privileges
	 * @param  callable    assertion
	 * @return Permission  provides a fluent interface
	 */
	public function allow($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL, $assertion = NULL)
	{
		$this->setRule(TRUE, self::ALLOW, $roles, $resources, $privileges, $assertion);
		return $this;
	}



	/**
	 * Denies one or more Roles access to [certain $privileges upon] the specified Resource(s).
	 * If $assertion is provided, then it must return TRUE in order for rule to apply.
	 *
	 * @param  string|array|AclAuthorizator::ALL  roles
	 * @param  string|array|AclAuthorizator::ALL  resources
	 * @param  string|array|AclAuthorizator::ALL  privileges
	 * @param  callable    assertion
	 * @return Permission  provides a fluent interface
	 */
	public function deny($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL, $assertion = NULL)
	{
		$this->setRule(TRUE, self::DENY, $roles, $resources, $privileges, $assertion);
		return $this;
	}



	/**
	 * Removes "allow" permissions from the list in the context of the given Roles, Resources, and privileges.
	 *
	 * @param  string|array|AclAuthorizator::ALL  roles
	 * @param  string|array|AclAuthorizator::ALL  resources
	 * @param  string|array|AclAuthorizator::ALL  privileges
	 * @return Permission  provides a fluent interface
	 */
	public function removeAllow($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL)
	{
		$this->setRule(FALSE, self::ALLOW, $roles, $resources, $privileges);
		return $this;
	}



	/**
	 * Removes "deny" restrictions from the list in the context of the given Roles, Resources, and privileges.
	 *
	 * @param  string|array|AclAuthorizator::ALL  roles
	 * @param  string|array|AclAuthorizator::ALL  resources
	 * @param  string|array|AclAuthorizator::ALL  privileges
	 * @return Permission  provides a fluent interface
	 */
	public function removeDeny($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL)
	{
		$this->setRule(FALSE, self::DENY, $roles, $resources, $privileges);
		return $this;
	}



	/**
	 * Performs operations on Access Control List rules.
	 * @param  bool  operation add?
	 * @param  bool  type
	 * @param  string|array|AclAuthorizator::ALL  roles
	 * @param  string|array|AclAuthorizator::ALL  resources
	 * @param  string|array|AclAuthorizator::ALL  privileges
	 * @param  callable    assertion
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	protected function setRule($toAdd, $type, $roles, $resources, $privileges, $assertion = NULL)
	{
		// !!!
		$this->updating();

		// ensure that all specified Roles exist; normalize input to array of Roles or NULL
		if ($roles === self::ALL) {
			$roles = array(self::ALL);

		} else {
			if (!is_array($roles)) {
				$roles = array($roles);
			}

			// !!!
			/* foreach ($roles as $role) {
				$this->checkRole($role);
			} */
		}

		// ensure that all specified Resources exist; normalize input to array of Resources or NULL
		if ($resources === self::ALL) {
			$resources = array(self::ALL);

		} else {
			if (!is_array($resources)) {
				$resources = array($resources);
			}

			// !!!
			/* foreach ($resources as $resource) {
				$this->checkResource($resource);
			} */
		}

		// normalize privileges to array
		if ($privileges === self::ALL) {
			$privileges = array();

		} elseif (!is_array($privileges)) {
			$privileges = array($privileges);
		}

		$assertion = $assertion ? new Nette\Callback($assertion) : NULL;

		if ($toAdd) { // add to the rules
			foreach ($resources as $resource) {
				foreach ($roles as $role) {
					$rules = & $this->getRules($resource, $role, TRUE);
					if (count($privileges) === 0) {
						$rules['allPrivileges']['type'] = $type;
						$rules['allPrivileges']['assert'] = $assertion;
						if (!isset($rules['byPrivilege'])) {
							$rules['byPrivilege'] = array();
						}
					} else {
						foreach ($privileges as $privilege) {
							$rules['byPrivilege'][$privilege]['type'] = $type;
							$rules['byPrivilege'][$privilege]['assert'] = $assertion;
						}
					}
				}
			}

		} else { // remove from the rules
			foreach ($resources as $resource) {
				foreach ($roles as $role) {
					$rules = & $this->getRules($resource, $role);
					if ($rules === NULL) {
						continue;
					}
					if (count($privileges) === 0) {
						if ($resource === self::ALL && $role === self::ALL) {
							if ($type === $rules['allPrivileges']['type']) {
								$rules = array(
									'allPrivileges' => array(
										'type' => self::DENY,
										'assert' => NULL
										),
									'byPrivilege' => array()
									);
							}
							continue;
						}
						if ($type === $rules['allPrivileges']['type']) {
							unset($rules['allPrivileges']);
						}
					} else {
						foreach ($privileges as $privilege) {
							if (isset($rules['byPrivilege'][$privilege]) &&
								$type === $rules['byPrivilege'][$privilege]['type']) {
								unset($rules['byPrivilege'][$privilege]);
							}
						}
					}
				}
			}
		}
		return $this;
	}



	/********************* querying the ACL ****************d*g**/



	/**
	 * Returns TRUE if and only if the Role has access to [certain $privileges upon] the Resource.
	 *
	 * This method checks Role inheritance using a depth-first traversal of the Role list.
	 * The highest priority parent (i.e., the parent most recently added) is checked first,
	 * and its respective parents are checked similarly before the lower-priority parents of
	 * the Role are checked.
	 *
	 * @param  string|AclAuthorizator::ALL|IRole  role
	 * @param  string|AclAuthorizator::ALL|IResource  resource
	 * @param  string|AclAuthorizator::ALL  privilege
	 * @throws Nette\InvalidStateException
	 * @return bool
	 */
	public function isAllowed($role = self::ALL, $resource = self::ALL, $privilege = self::ALL)
	{
		// !!!
		$this->querying();

		/* d10($this->rules);
		exit; */

		$this->queriedRole = $role;
		if ($role !== self::ALL) {
			if ($role instanceof IRole) {
				$role = $role->getRoleId();
			}
			$this->checkRole($role, FALSE);
		}

		$this->queriedResource = $resource;
		if ($resource !== self::ALL) {
			$resourcesToProcess = is_array($resource) ? $resource : array($resource);

			foreach($resourcesToProcess as $resource) {
				if ($resource instanceof IResource) {
					$resource = $resource->getResourceId();
				}
				$this->checkResource($resource, FALSE);
			}

			if(count($resourcesToProcess) == 0)
				throw new Nette\InvalidArgumentException("No resources given");

			$resource = array_shift($resourcesToProcess);
		}

		do {
			// depth-first search on $role if it is not 'allRoles' pseudo-parent
			if ($role !== NULL && NULL !== ($result = $this->searchRolePrivileges($privilege === self::ALL, $role, $resource, $privilege))) {
				break;
			}

			if ($privilege === self::ALL) {
				if ($rules = $this->getRules($resource, self::ALL)) { // look for rule on 'allRoles' psuedo-parent
					foreach ($rules['byPrivilege'] as $privilege => $rule) {
						if (self::DENY === ($result = $this->getRuleType($resource, NULL, $privilege))) {
							break 2;
						}
					}
					if (NULL !== ($result = $this->getRuleType($resource, NULL, NULL))) {
						break;
					}
				}
			} else {
				if (NULL !== ($result = $this->getRuleType($resource, NULL, $privilege))) { // look for rule on 'allRoles' pseudo-parent
					break;

				} elseif (NULL !== ($result = $this->getRuleType($resource, NULL, NULL))) {
					break;
				}
			}


			if(isset($this->resources[$resource])) {
				$resource = $this->resources[$resource]['parent'];

				if($resource === NULL && count($resourcesToProcess) > 0)
					$resource = array_shift($resourcesToProcess);

			} elseif($this->isCompoundName($resource)) {
				list($name, $params) = Strings::parseParametrizedString($resource);
				$resource = $name;

			} else {
				throw new Nette\InvalidStateException("Resource '$resource' does not exist.");
			}

		} while (TRUE);

		$this->queriedRole = $this->queriedResource = NULL;

		return $result;
	}



	/**
	 * Returns real currently queried Role. Use by assertion.
	 * @return mixed
	 */
	public function getQueriedRole()
	{
		return $this->queriedRole;
	}



	/**
	 * Returns real currently queried Resource. Use by assertion.
	 * @return mixed
	 */
	public function getQueriedResource()
	{
		return $this->queriedResource;
	}



	/********************* internals ****************d*g**/



	/**
	 * Performs a depth-first search of the Role DAG, starting at $role, in order to find a rule
	 * allowing/denying $role access to a/all $privilege upon $resource.
	 * @param  bool  all (true) or one?
	 * @param  string
	 * @param  string
	 * @param  string  only for one
	 * @return mixed  NULL if no applicable rule is found, otherwise returns ALLOW or DENY
	 */
	private function searchRolePrivileges($all, $role, $resource, $privilege)
	{
		$dfs = array(
			'visited' => array(),
			'stack' => array($role),
		);

		while (NULL !== ($role = array_pop($dfs['stack']))) {
			if (isset($dfs['visited'][$role])) {
				continue;
			}
			if ($all) {
				if ($rules = $this->getRules($resource, $role)) {
					foreach ($rules['byPrivilege'] as $privilege2 => $rule) {
						if (self::DENY === $this->getRuleType($resource, $role, $privilege2)) {
							return self::DENY;
						}
					}
					if (NULL !== ($type = $this->getRuleType($resource, $role, NULL))) {
						return $type;
					}
				}
			} else {
				if (NULL !== ($type = $this->getRuleType($resource, $role, $privilege))) {
					return $type;

				} elseif (NULL !== ($type = $this->getRuleType($resource, $role, NULL))) {
					return $type;
				}
			}

			$dfs['visited'][$role] = TRUE;
			if(isset($this->roles[$role])) {
				foreach ($this->roles[$role]['parents'] as $roleParent => $foo) {
					$dfs['stack'][] = $roleParent;
				}
			} elseif($this->isCompoundName($role)) {
				list($name, $params) = Strings::parseParametrizedString($role);
				$dfs['stack'][] = $name;
			} else {
				throw new Nette\InvalidStateException("Role '$role' does not exist.");
			}
		}
		return NULL;
	}



	/**
	 * Returns the rule type associated with the specified Resource, Role, and privilege.
	 * @param  string|AclAuthorizator::ALL
	 * @param  string|AclAuthorizator::ALL
	 * @param  string|AclAuthorizator::ALL
	 * @return mixed  NULL if a rule does not exist or assertion fails, otherwise returns ALLOW or DENY
	 */
	private function getRuleType($resource, $role, $privilege)
	{
		if (!$rules = $this->getRules($resource, $role)) {
			return NULL;
		}

		if ($privilege === self::ALL) {
			if (isset($rules['allPrivileges'])) {
				$rule = $rules['allPrivileges'];
			} else {
				return NULL;
			}
		} elseif (!isset($rules['byPrivilege'][$privilege])) {
			return NULL;

		} else {
			$rule = $rules['byPrivilege'][$privilege];
		}

		if ($rule['assert'] === NULL || $rule['assert']->__invoke($this, $role, $resource, $privilege)) {
			return $rule['type'];

		} elseif ($resource !== self::ALL || $role !== self::ALL || $privilege !== self::ALL) {
			return NULL;

		} elseif (self::ALLOW === $rule['type']) {
			return self::DENY;

		} else {
			return self::ALLOW;
		}
	}



	/**
	 * Returns the rules associated with a Resource and a Role, or NULL if no such rules exist.
	 * If the $create parameter is TRUE, then a rule set is first created and then returned to the caller.
	 * @param  string|AclAuthorizator::ALL
	 * @param  string|AclAuthorizator::ALL
	 * @param  bool
	 * @return array|NULL
	 */
	private function & getRules($resource, $role, $create = FALSE)
	{
		$null = NULL;
		if ($resource === self::ALL) {
			$visitor = & $this->rules['allResources'];
		} else {
			if (!isset($this->rules['byResource'][$resource])) {
				if (!$create) {
					return $null;
				}
				$this->rules['byResource'][$resource] = array();
			}
			$visitor = & $this->rules['byResource'][$resource];
		}

		if ($role === self::ALL) {
			if (!isset($visitor['allRoles'])) {
				if (!$create) {
					return $null;
				}
				$visitor['allRoles']['byPrivilege'] = array();
			}
			return $visitor['allRoles'];
		}

		if (!isset($visitor['byRole'][$role])) {
			if (!$create) {
				return $null;
			}
			$visitor['byRole'][$role]['byPrivilege'] = array();
		}

		return $visitor['byRole'][$role];
	}

	// *************************************************************************

	/**
	 * Returns TRUE if given name (role, resource, ...) is a compound name
	 *
	 * @param string
	 * @return bool
	 */
	public function isCompoundName($name) {
		return (bool) preg_match('/[^\\\\]:/', $name);
	}

}