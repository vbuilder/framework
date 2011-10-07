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

use vBuilder,
		Nette,
		dibi;

/**
 * Entity repository for temporary store in the session
 *
 * @author Adam Staněk (V3lbloud)
 * @since Oct 7, 2011
 */
class SessionRepository extends BaseRepository {
	
	const SESSION_NAMESPACE = 'vBuilder.Orm';

	protected $_session;
	
	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI
	 */
	public function __construct(Nette\DI\IContainer $context) {
		parent::__construct($context);
	}
		
	public function findAll($entityName) {
		$array = isset($this->session[$entityName]) ? $this->session[$entityName] : array();
		return new ArrayFluent($array, $this->context);
	}
	
	/**
	 * Loads data from DB
	 * 
	 * @param Entity|Collection data holder
	 * 
	 * @return bool true if record has been successfuly loaded, false if record does not exist
	 */
	public function load($holder) {		
		if($holder instanceof Entity) {
			return $this->loadEntity($holder);
		}
				
		throw new Nette\NotSupportedException("Loading of class '".get_class($holder)."' is not supported by repository '".get_called_class()."'");		
		return false;
	}
	
	/**
	 * Loads entity
	 * 
	 * @param Entity entity 
	 * 
	 * @return bool true if record has been successfuly loaded, false if record does not exist
	 */
	protected function loadEntity($entity) {		
		$entities = isset($this->session[get_class($entity)]) ? $this->session[get_class($entity)] : array();		
		$id = $this->getEntityId($entity);
		
		if(isset($entities[$id])) {
			$entity->data->loadData($entities[$id]);
			
			return true;
		}		
		
		return false;
	}
		
	/**
	 * Removes entity from repository
	 * 
	 * @param Entity entity 
	 */
	public function delete(Entity $entity) {
		$entities = isset($this->session[get_class($entity)]) ? $this->session[get_class($entity)] : array();		
		$id = $this->getEntityId($entity);
		
		if(isset($entities[$id])) {
			unset($entities[$id]);
			
			return true;
		}		
		
		return false;
	}
	
	/**
	 * Loads data into DB
	 * 
	 * @param Entity|Collection data holder
	 * 
	 * @return bool true if record has been successfuly loaded, false if record does not exist
	 */
	public function save($holder) {		
		if($holder instanceof Entity) {
				return $this->saveEntity($holder);
		}
				
		throw new Nette\NotSupportedException("Saving of class '".get_class($holder)."' is not supported by repository '".get_called_class()."'");		
		return false;
	}
	
	/**
	 * Saves entity into session
	 * 
	 * @param Entity $entity 
	 */
	public function saveEntity(Entity $entity) {
		$entities = isset($this->session[get_class($entity)]) ? $this->session[get_class($entity)] : array();
	
		$entities[$this->getEntityId($entity)] = $entity->getData()->getAllData();
		
		$fields = $entity->metadata->getFields();
		foreach($fields as $curr) {
			if($entity->{$curr} instanceof EntityCollection || $entity->{$curr} instanceof ActiveEntity) {
				$entity->{$curr}->save();
				continue;
			} elseif(in_array($entity->metadata->getFieldType($curr), array('OneToOne', 'OneToMany'))) {
				throw new Nette\NotSupportedException("Only saving of ActiveEntities and EntityCollections is supported at the moment");
			}
		}
		
		$this->session[get_class($entity)] = $entities;
	}

	/**
	 * Returns session section
	 * 
	 * @return SessionSection 
	 */
	public function getSession() {
		if(!isset($this->_session)) {
			$this->_session = $this->context->session->getSection(self::SESSION_NAMESPACE);
		}
	
		return $this->_session;
	}
	
	/**
	 * Clears all entities from session
	 */
	public function clear() {
		$this->session->remove();
	}
	
	/**
	 * Creates ID for entity
	 * 
	 * @param Entity entity instance
	 * @return string
	 */
	protected function getEntityId($entity) {
		$idFields = array();
		foreach($entity->metadata->getIdFields() as $curr) $idFields[] = $entity->{$curr};
		return count($idFields) > 1 ? md5(implode(',', $idFields)) : reset($idFields);
	}	
	
}
