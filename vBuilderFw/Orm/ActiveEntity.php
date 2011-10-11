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
 * Active record layer for Entity
 *
 * TODO: Refaktorovat! V podstate by tu zadna logika nemela byt. Vse delegovat
 * do repozitare.
 * 
 * @author Adam Staněk (V3lbloud)
 * @since Feb 17, 2011
 */
class ActiveEntity extends Entity implements Nette\Security\IResource {
	
	const STATE_NEW = 1;
	const STATE_LOADED = 2;
	const STATE_DELETED = 3;
	
	
	/** @var array of event listeners for post load event */
	public $onPreLoad = array();
	
	/** @var array of event listeners for post load event */
	public $onPostLoad = array();
	
	/** @var array of event listeners for pre save event
	 *  you can use it for exampl for locking table and calculating auto-fields.
	 */
	public $onPreSave = array();
	
	/** @var array of event listeners for post save event (it is called AFTER onUpdate/onCreate event)
	 * you can use it for example for unlocking table and cleanup po pres-save */
	public $onPostSave = array();
	
	/** @var array of event listeners for event, when existing entity is updated to DB */
	public $onUpdate = array();
	
	/** @var array of event listeners for event, when new entity is saved to DB */
	public $onCreate = array();
	
	/** @var array of event listeners for event before entity removes itself from DB */
	public $onPreDelete = array();
	
	/** @var array of event listeners for event, when entity has removed itself from DB */
	public $onPostDelete = array();
	
	/** @var int state of entity */
	private $state = ActiveEntity::STATE_NEW;
	
	/**
	 * Constructor of active record Entity. Passess arguments to Entity.
	 * 
	 * @param array of data 
	 */
	public function __construct($data = array()) {
		call_user_func_array(array('parent', '__construct'), func_get_args()); 
		
		$this->data->onNeedToFetch[] = \callback($this, 'load');
	}
	
	/**
	 * Loads entity data. This function is meant to perform lazy loading,
	 * it's not supposed to handle direct calls. Try not to call this function directly,
	 * because if you want to, it's probably something wrong with your design.
	 * 
	 * Calls onPreLoad and onPostLoad events.
	 * 
	 * @return ActiveEntity fluent interface
	 * @throws EntityException if no matching record has been found
	 */
	public function load() {
		if($this->state != self::STATE_NEW || (!$this->checkIfIdIsDefined() && !$this->repository->isEmptyIdFieldAllowed())) return ;
		
		$this->onPreLoad();
				
		if(!$this->repository->load($this))
			$this->throwNoRecordFound();
		
		$this->state = self::STATE_LOADED;
		$this->onPostLoad();
		
		return $this;
	}
	
	/**
	 * Checks if entity exists in DB.
	 * 
	 * Function returns true if any non-id field is set. If not
	 * it actualy loads the DB row and checks again.
	 * 
	 * return @bool
	 */
	public function exists() {
		if($this->state == self::STATE_LOADED)	return true;
		if((!$this->checkIfIdIsDefined() && !$this->repository->isEmptyIdFieldAllowed()) || $this->state == self::STATE_DELETED) return false;
		
		// Zjistit jestli jsou definovany jiny sloupce nez jen ID
		$nonIdFields = \array_diff($this->metadata->getFields(), $this->metadata->getIdFields());
				
		foreach($nonIdFields as $name) {
			if(isset($this->data->$name)) 
				return true;
		}
		
		// Pokud zadny nejsou definovany, zkusim data nacist z DB
		try {
			$this->load();
			if($this->state == self::STATE_LOADED)	return true;
		} catch(EntityException $e) {
			if($e->getCode() == EntityException::NOT_FOUND)
				return false;
			
			throw $e;
		}
		
			
		return false;
	}
	
	/**
	 * Saves data into DB. Performs save only if data actualy changed.
	 * 
	 * @return ActiveEntity fluent interface
	 * @throws EntityException if any of ID fields is not defined (except of auto-generated fields)
	 * @throws \LogicException if there is more than one auto-generated fields
	 */
	public function save() {		
		$this->repository->save($this);
		
		return $this;
	}
	
	/**
	 * Reverts any changes and set entity to unmodified state.
	 * 
	 * @return ActiveEntity fluent interface
	 */
	public function revert() {
		$this->data->performRollback();
		
		return $this;
	}
	
	/**
	 * Deletes record, if no record has been found throws exception.
	 * 
	 * @return ActiveEntity fluent interface
	 * @throws EntityException if record was not found
	 */
	public function delete() {		
		if($this->state == self::STATE_DELETED) return ;
		if(!$this->repository->isEmptyIdFieldAllowed()) $this->checkIfIdIsDefined(true);
								
		$success = $this->repository->delete($this);
		$tmpState = $this->state;
		$this->state = self::STATE_DELETED;
		
		if(!$success) {
			$this->state = $tmpState;
			$this->throwNoRecordFound();
		}
		
		return $this;
	}
	
	/**
	 * Return resource id of this entity, all entity instances are child of this
	 * resource.
	 * 
	 * @return string resource id 
	 */
	public static function getParentResourceId() {
		return \get_called_class();
	}
	
	/**
	 * Returns resource ID for ACL
	 * 
	 * @return string
	 */
	public function getResourceId() {
		if($this->checkIfIdIsDefined() && count($this->metadata->getIdFields()) > 0) {
			$ids = array();
			foreach($this->metadata->getIdFields() as $name) $ids[] = $this->data->$name;
			
			$resId = self::getParentResourceId() . '(' . implode($ids, ',') . ')';
			
			$acl = Nette\Environment::getUser()->getAuthorizator();
			if($acl instanceof Nette\Security\Permission && !$acl->hasResource($resId))
				$acl->addResource($resId, static::getParentResourceId()); 
			
			return $resId;
			
		} else
			return static::getParentResourceId();
	}
	
	/**
	 * Helper function for throwing EntityException with NOT_FOUND code
	 * 
	 * @throws EntityException
	 */
	final protected function throwNoRecordFound() {
		$idFields = $this->metadata->getIdFields();
		$fields = "";
		foreach($idFields as $name) {
			if($fields !== "") $fields .= ", ";
			$fields .= $name . " = '".$this->data->$name."'";
		}
			
		$class = get_class($this) . "(".$fields.")";
				
		throw new EntityException("No record found for $class in table '".$this->metadata->getTableName()."'", EntityException::NOT_FOUND);
	}
	
	/**
	 * Checks if all ID fields are defined.
	 * 
	 * @param bool true if you want function to throw an exception
	 * @return bool
	 * @throws EntityException if any of ID fields is not defined and $throw is true
	 */
	final protected function checkIfIdIsDefined($throw = false) {
		foreach($this->metadata->getIdFields() as $name) {
			if(!isset($this->data->$name)) {
				if($throw)
					throw new EntityException("Missing value for column '$name' which is mandatory because of primary key index", EntityException::ID_NOT_DEFINED);
				else
					return false;
			}
		}
		
		return true;
	}
	
}
