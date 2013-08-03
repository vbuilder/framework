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
	Nette;
	
/**
 * Basic ACL authorization layer (proxy to Nette\Security\Permission)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class AclAuthorizator extends Nette\Object implements Nette\Security\IAuthorizator {

	/** @var Nette\DI\IContainer */
	protected $context;

	/** @var Nette\Security\Permission */
	protected $acl;

	/** @var Nette\Reflection\ClassType */
	protected $aclReflection;

	/** @var array of function(AclAuthorizator $authorizator); Occurs when first query is requested (on lazy initialization) */
	public $onInit = array();

	protected $tableName = 'security_acl';

	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;
		$this->aclReflection = new Nette\Reflection\ClassType('Nette\\Security\\Permission');
	}

	/**
	 * Performs ACL initialization
	 */
	public function init() {
		// First thing: to avoid circular dependency
		$this->acl = $this->aclReflection->newInstance();

		// Registered initializators
		$this->onInit($this);

		// Load from DB
		// TODO: Caching
		// TODO: Dodelat
		/* $db = $this->context->database->connection;
		$rules = $db->query("SELECT * FROM %n", $this->tableName);

		foreach($rules as $rule) {
			if($rule->type == 'allow')
				$this->acl->allow($rule->role, $rule->resource, $rule->privilege);
			else
				$this->acl->deny($rule->role, $rule->resource, $rule->privilege);
		} */
	}

	/**
	 * Performs a role-based authorization.
	 * @param  string  role
	 * @param  string  resource
	 * @param  string  privilege
	 * @return bool
	 */
	function isAllowed($role/*5.2* = self::ALL*/, $resource/*5.2* = self::ALL*/, $privilege/*5.2* = self::ALL*/) {
		if(!isset($this->acl)) $this->init();

		return $this->acl->isAllowed($role, $resource, $privilege);
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