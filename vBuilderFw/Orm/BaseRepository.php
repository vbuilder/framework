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
 * Base repository
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
abstract class BaseRepository extends vBuilder\Object implements IRepository {
	
	/** @var Nette\DI\IContainer DI */
	protected $_context;
	private $_context2;
	
	private $entityCache = array();

	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI
	 */
	public function __construct(Nette\DI\IContainer $context) {
		$this->_context = $context;
	}
	
	public function getContext() {
		if(!isset($this->_context2)) {
			// Aby vsechny mnou vytvorene tridy mely me jako repozitar
			$this->_context2 = clone $this->_context;
			$this->_context2->removeService('repository');
			$this->_context2->addService('repository', $this);
		}
		
		return $this->_context2;
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
	 * Returns new entity
	 * 
	 * @param string entity name
	 * @return vBuilder\Orm\IActiveEntity
	 */
	public function create($entityName) {
		return $this->get($entityName);
	}
	
	/**
	 * Returns entity cache model
	 *
	 * @return vBuilder\Orm\Cache
	 */
	public function cache($entityName) {
		$class = $this->getEntityClass($entityName);
			
		if(!isset($this->entityCache[$class])) {
			if($class === false) throw new EntityException("Entity '$entity' does not exist", EntityException::ENTITY_TYPE_NOT_DEFINED);
			
			$this->entityCache[$class] = new Cache($this, $class, $this->context->cacheStorage);
		}
	
		return $this->entityCache[$class]; 		
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
	
	// REPO INDEPENDENT STUFF ---------------------------------------------------
	
	public function saveEntityCollection(EntityCollection $collection) {
		if(count($collection) == 0) return;
				
		$joinPairs = $collection->parent->getMetadata()->getFieldJoinPairs($collection->idField);
		
		foreach($collection as &$member) {
			foreach($joinPairs as $join) {
				// Oboustranna vazba
				if($member->getMetaData()->getFieldType($join[1]) == 'OneToOne' && ($entityName = $member->getMetaData()->getFieldEntityName($join[1])) != '' && $collection->parent instanceof $entityName) {
				
					$member->{$join[1]} = $collection->parent;
					
				// Obycejny join
				} else {
					// \Nette\Diagnostics\Debugger::log("ASSERT: " . $member->{$join[1]} . " != " . $collection->parent->{$join[0]});
				
					if($member->{$join[1]} != $collection->parent->{$join[0]})
						$member->{$join[1]} = $collection->parent->{$join[0]};
				}
				
			}
			
			$this->save($member);
		}
	}

}
