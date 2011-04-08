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

namespace vBuilder\Orm;

use vBuilder, Nette;

/**
 * Implementation of IEntityMetadata for config based entities
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 3, 2011
 */
class ConfigMetadata implements IEntityMetadata {
	
	private $config;
	
	public function __construct(Array $config) {			
		$this->config = $config;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getTableName() {
		return isset($this->config["table"]) ? $this->config["table"] : null;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getBehaviors() {
		return array();
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getBehaviorArgs($behaviorName) {
		return array();
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getIdFields() {
		return array();
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFields() {
		if(isset($this->config["fields"]))
			return array_keys((array) $this->config["fields"]);
		
		return array();
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function hasField($name) {
		return in_array($name, $this->getFields());
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldType($name) {
		if(!isset($this->config["fields"][$name])) throw new \InvalidArgumentException("Field '$name' is not defined");
		
		return isset($this->config["fields"][$name]["type"])
				  ? $this->config["fields"][$name]["type"] : 'string';
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldEntityName($name) {
		return null;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldTableName($name) {
		return null;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldJoinPairs($name) {
		return array();
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function isFieldGenerated($name) {
		return null;
	}
	
}
