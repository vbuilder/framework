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

use vBuilder, Nette, dibi,
	 vBuilder\dibi\DataSource;

/**
 * Repository class for ORM entities
 *
 * @author Adam Staněk (V3lbloud)
 * @since Mar 4, 2011
 */
class Repository extends vBuilder\Object {
	
	/**
	 * Returns one entity
	 * 
	 * @param string entity name
	 * @return IActiveEntity
	 */
	public static function get($entity) {
		$class = self::getEntityClass($entity);
		// TODO: Dodelat genericke entity z configu
		if($class === false) throw new EntityException("Entity '$entity' does not exist", EntityException::ENTITY_TYPE_NOT_DEFINED);
		
		$args = func_get_args();
		array_shift($args);
		
		$reflection = new \ReflectionClass($class);
		$entityInstance = $reflection->newInstanceArgs($args);
				
		return $entityInstance;
	}
	
	/**
	 * Creates DataSource for finding all entities
	 * 
	 * @param string entity name
	 * @return DataSource 
	 */
	public static function findAll($entity) {
		$class = self::getEntityClass($entity);
		// TODO: Dodelat genericke entity z configu
		if($class === false) throw new EntityException("Entity '$entity' does not exist", EntityException::ENTITY_TYPE_NOT_DEFINED);
		
		$metadata = $class::getMetadata();
		
		$stmt = dibi::select("*")->from($metadata->getTableName());
		$ds = new DataSource((String) $stmt, $class);
		return $ds;
	}
	
	/**
	 * Returns new entity
	 * 
	 * @param string entity name
	 * @return IActiveEntity
	 */
	public static function create($entity) {
		return self::get($entity);
	}
	
	/**
	 * Helper function for getting class from entity name
	 * 
	 * @param string entity name
	 * @return string|bool return false, if no such class has been found 
	 */
	private static function getEntityClass($entity) {
		if(class_exists($entity)) {
			return $entity;
		}
		
		return false;
	}
	
}
