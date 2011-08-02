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

use vBuilder, Nette, dibi;

/**
 * Repository class for ORM entities
 *
 * @author Adam Staněk (V3lbloud)
 * @since Mar 4, 2011
 */
class Repository extends vBuilder\Object {
	
	/** @var Nette\DI\IContainer DI */
	protected $context;

	/** @var DibiConnection DB connection */
	protected $db;

	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI
	 */
	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;
		$this->db = $this->context->connection;
	}
	
	 /**
	 * Returns one entity
	 * 
	 * @param string entity name
	 * @return vBuilder\Orm\IActiveEntity
	 */
	public function get($entity) {
		$class = $this->getEntityClass($entity);
		
		if($class === false) throw new EntityException("Entity '$entity' does not exist", EntityException::ENTITY_TYPE_NOT_DEFINED);
		
		$args = func_get_args();
		array_shift($args);
		$args[] = $this->context;
		
		$reflection = new \ReflectionClass($class);
		$entityInstance = $reflection->newInstanceArgs($args);
				
		return $entityInstance;
	}
	
	/**
	 * Creates Fluent for building select query
	 * 
	 * @param string entity name
	 * @return vBuilder\Orm\Fluent
	 */
	public function findAll($entity) {
		$class = self::getEntityClass($entity);
		// TODO: Dodelat genericke entity z configu
		if($class === false) throw new EntityException("Entity '$entity' does not exist", EntityException::ENTITY_TYPE_NOT_DEFINED);
		
		$metadata = $class::getMetadata();

		// Delam zvlast, protoze jinak by se mohla vyhazovat
		// vyjimka pri DibiFluent::__toString
		if(!$this->db->isConnected()) $this->db->connect();
		
		$fluent = new Fluent($class, $this->context);
		$fluent->select('*')->from($metadata->getTableName());
		
		return $fluent;
	}
	
	/**
	 * Returns new entity
	 * 
	 * @param string entity name
	 * @return vBuilder\Orm\IActiveEntity
	 */
	public function create($entity) {
		return $this->get($entity);
	}
	
	/**
	 * Helper function for getting class from entity name
	 * 
	 * @param string entity name
	 * @return string|bool return false, if no such class has been found 
	 */
	protected function getEntityClass($entity) {
		if(class_exists($entity)) {
			return $entity;
		}
		
		return false;
	}

}
