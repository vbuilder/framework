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
	Nette;
	
/**
 * Basic ACL authorization layer (proxy to Nette\Security\Permission).
 * Supports lazy initialization (Authorizator is always initialized with User service,
 * but not always needed, so some extensive stuff like DB load are not necessary until the first query).
 * This approach also allows easier extension through addInitCallback() method.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class AclAuthorizatorProxy extends Nette\Object implements Nette\Security\IAuthorizator {

	/** @var Nette\DI\IContainer */
	protected $context;

	/** @var Nette\Security\Permission */
	protected $acl;

	/** @var Nette\Reflection\ClassType */
	protected $aclReflection;

	/** @var array of function(AclAuthorizator $authorizator); Occurs when first query is requested (on lazy initialization)
	 * We don't want it to be public, because we need to check if the object wasn't initialized yet.
	 * User addInitCallback()
	 */
	protected $onInit = array();

	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;
		$this->aclReflection = new Nette\Reflection\ClassType('Nette\\Security\\Permission');
	}

	/**
	 * Performs ACL initialization
	 */
	protected function init() {
		// First thing: to avoid circular dependency
		$this->acl = $this->aclReflection->newInstance();

		// Trigger registered initializators
		foreach($this->onInit as $cb)
			Nette\Callback::create($cb)->invokeArgs(array($this));
	}

	/**
	 * Adds new initialization callback
	 *
	 * @param callable
	 * @return self
	 * @throws Nette\InvalidStateException if class has been already initialized
	 */
	public function addInitCallback($cb) {
		if(!is_callable($cb))
			throw new Nette\InvalidArgumentException("Expected callable");

		if(isset($this->acl))
			throw new Nette\InvalidStateException(get_called_class() . ' already initialized');

		$this->onInit[] = $cb;
	}

	/**
	 * Performs a role-based authorization.
	 *
	 * @param  string  role
	 * @param  string  resource
	 * @param  string  privilege
	 * @return bool
	 */
	function isAllowed($role/*5.2* = self::ALL*/, $resource/*5.2* = self::ALL*/, $privilege/*5.2* = self::ALL*/) {
		if(!isset($this->acl)) $this->init();

		// If querying the compound name, ensure it exists
		$this->addRoleIfNotExist($role, TRUE);
		$this->addResourceIfNotExist($resource, TRUE);

		return $this->acl->isAllowed($role, $resource, $privilege);
	}

	/**
	 * Adds role if it doesn't exist already.
	 *
	 * @param string role name
	 * @param bool only add if role is compound
	 * @param self
	 */
	public function addRoleIfNotExist($role, $onlyCompound = FALSE) {
		if($this->hasRole($role)) return ;

		// Compound
		if(preg_match('/[^\\\\]:/', $role)) {
			list($name, $params) = Strings::parseParametrizedString($role);
			$this->addRole($role, $name);
		} else {
			$this->addRole($role);
		}

		return $this;
	}

	/**
	 * Adds resource if it doesn't exist already.
	 *
	 * @param resource name
	 * @param bool only add if resource is compound
	 * @param self
	 */
	public function addResourceIfNotExist($resource, $onlyCompound = FALSE) {
		if($this->hasResource($resource)) return ;

		// Compound
		if(preg_match('/[^\\\\]:/', $resource)) {
			list($name, $params) = Strings::parseParametrizedString($resource);
			$this->addResource($resource, $name);
		} else {
			$this->addResource($resource);
		}

		return $this;
	}

	/**
	 * Pass calls to actual ACL class
	 *
	 * @param  string  method name
	 * @param  array   arguments
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public function __call($name, $args) {
		if($this->aclReflection->hasMethod($name)) {
			if(!isset($this->acl)) $this->init();

			return $this->aclReflection->getMethod($name)->invokeArgs($this->acl, $args);
		}

		return parent::__call($name, $args);
	}

}