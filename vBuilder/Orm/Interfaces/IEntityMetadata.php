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

/**
 * Interface for Entity metadata object
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 17, 2011
 */
interface IEntityMetadata {
	
	/**
	 * Returns table name
	 * 
	 * @return string
	 */
	public function getTableName();
	
	/**
	 * Returns names of entity behaviors
	 * 
	 * @return array of behavior names
	 */
	public function getBehaviors();
	
	/**
	 * Returns behavior arguments
	 * 
	 * @return array of arguments
	 */
	public function getBehaviorArgs($behaviorName);
	
	/**
	 * Return names of ID fields
	 * 
	 * @return array of field names
	 */
	public function getIdFields();
	
	/**
	 * Return names of all fields
	 * 
	 * @return array of field names
	 */
	public function getFields();
	
	/**
	 * Is field set?
	 * 
	 * @param string field name
	 * 
	 * @return bool
	 */
	public function hasField($name);
	
	/**
	 * Returns real column name
	 * 
	 * @param string field name
	 * @return string column name
	 */
	public function getFieldColumn($name);
	
	/**
	 * Returns data type for field
	 * 
	 * @param string field name
	 * @return string field type name
	 */
	public function getFieldType($name);
	
	/**
	 * Returns entity name for field (OneToMany, etc)
	 * 
	 * @param string field name
	 * @return string entity name
	 */
	public function getFieldEntityName($name);
	
	/**
	 * Returns table name for field (OneToMany, etc)
	 * 
	 * @param string field name
	 * @return string field table name
	 */
	public function getFieldTableName($name);
	
	/**
	 * Returns array of pairs which use as join keys in relations such as OneToMany.
	 * The first item of pair is always from current entity side.
	 * 
	 * @param string field name
	 * @return array of column pairs
	 */
	public function getFieldJoinPairs($name);
	
	/**
	 * Returns name of entity to which it is mapped (the other side has a joining column)
	 * or null if field is not in relation.
	 * 
	 * @param string field name
	 * @return string entity name
	 */
	public function getFieldMappedBy($name);
	
	/**
	 * Is field auto-generated (auto-increment, etc)?
	 * 
	 * @param string field name
	 * @return bool
	 */
	public function isFieldGenerated($name);
	
	/**
	 * Does field have $property option set?
	 * 
	 * @param string field name
	 * @param string property name
	 * 
	 * @return bool
	 */
	public function hasFieldProperty($field, $property);
	
	/**
	 * Returns field's property value
	 * 
	 * @param string field name
	 * @param string property name
	 * @param mixed default value
	 * 
	 * @return mixed
	 */
	public function getFieldProperty($field, $property, $default = null);
	
}
