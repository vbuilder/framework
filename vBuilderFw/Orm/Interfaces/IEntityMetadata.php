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
	 * Returns data type for field
	 * 
	 * @param string field name
	 * @return field type name
	 */
	public function getFieldType($name);
	
	/**
	 * Returns entity name for field (OneToMany, etc)
	 * 
	 * @param string field name
	 * @return field type name
	 */
	public function getFieldEntityName($name);
	
	/**
	 * Returns table name for field (OneToMany, etc)
	 * 
	 * @param string field name
	 * @return field type name
	 */
	public function getFieldTableName($name);
	
	/**
	 * Returns array of pairs which use as join keys in relations such as OneToMany.
	 * First of the pair is always from "One" side.
	 * 
	 * @param string field name
	 * @return array of column pairs
	 */
	public function getFieldJoinPairs($name);
	
	/**
	 * Is field auto-generated (auto-increment, etc)?
	 * 
	 * @param string field name
	 * @return bool
	 */
	public function isFieldGenerated($name);
	
}
