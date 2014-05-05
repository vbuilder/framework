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
 * Implementation of ACL authorization layer with DB load
 *
 * @todo Caching of loaded DB records
 * @todo Getters / setters for table name
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class DatabaseAclAuthorizator extends AclAuthorizator {

	protected $tableName = 'security_acl';

	protected function setup() {
		parent::setup();

		// Load from DB
		$db = $this->context->database->connection;
		$rules = $db->query("SELECT * FROM %n", $this->tableName);

		foreach($rules as $rule) {
			// If querying the compound name, ensure it exists

			if($rule->type == 'allow')
				$this->allow($rule->role, $rule->resource, $rule->privilege);
			else
				$this->deny($rule->role, $rule->resource, $rule->privilege);
		}
	}

}