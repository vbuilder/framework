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
 * @author Adam Staněk (V3lbloud)
 * @since Feb 17, 2011
 */
class ActiveEntity extends Entity {
	
	const STATE_NEW = 1;
	const STATE_LOADED = 2;
	const STATE_DELETED = 3;
	
	
	/** @var array of event listeners for post load event */
	public $onPreLoad = array();
	
	/** @var array of event listeners for post load event */
	public $onPostLoad = array();
	
	/** @var array of event listeners for event, when existing entity is saved to DB */
	public $onUpdate = array();
	
	/** @var array of event listeners for event, when new entity is saved to DB */
	public $onCreate = array();
	
	/** @var array of event listeners for event, when entity removes DB */
	public $onDelete = array();
	
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
		if($this->state != self::STATE_NEW || !$this->checkIfIdIsDefined()) return ;
		
		$this->onPreLoad();
		
		$query = dibi::select('*')->from($this->metadata->getTableName());
		$idFields = $this->metadata->getIdFields();
		foreach($idFields as $name) 
			$query = $query->where("[$name] = %s", $this->data->$name);
		
		$result = $query->fetch();
		if($result !== false) {
			$this->data->loadData((array) $result);
		} else {
			$this->throwNoRecordFound();
		}
		
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
		if(!$this->checkIfIdIsDefined() || $this->state == self::STATE_DELETED) return false;
		
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
		$idFields = $this->metadata->getIdFields();
		$autoField = null;
		
		// Checks mandatory fields
		foreach($idFields as $name) {
			if(!$this->metadata->isFieldGenerated($name)) {
				if(!isset($this->data->$name))
					throw new EntityException("Cannot save with missing value for field '$name' which is mandatory because of ID index", EntityException::ID_NOT_DEFINED);
			} elseif($autoField === null) {
				$autoField = $name;
			} else
				throw new \LogicException('More than one generated ID field is not supported');
		}
		

		$changedFieldsData = $this->data->getChangedData();		
		if(count($changedFieldsData) == 0) return ;		
		$allFieldsData = $this->data->getAllData();
		
		
		dibi::begin();	
		
		dibi::query('INSERT IGNORE ', $this->metadata->getTableName(),
				  $allFieldsData, ' ON DUPLICATE KEY UPDATE %a', $changedFieldsData);
		
		// Provedl se INSERT
		try {
			if(dibi::affectedRows() == 1) {

				// Zjistim ID pro generovane sloupce
				$addtionalDataToMerge = $autoField === null ? array() : array($autoField => dibi::insertId());
				$this->data->mergeData($addtionalDataToMerge);

				$this->onCreate($this);
			}

			// Provedl se UPDATE
			elseif(dibi::affectedRows() == 2) {
				$this->onUpdate($this);
			}

			// Data se nezmenila
			else {
				// Nevim jestli je to takhle uplne idealni, sice insert ignore ... on duplicate key update
				// setri zamykani tabulky, ale zese je treba overovat, jestli se neco neposralo
				// a pokud jo, tak nemam zadny chybovy report

				// Zkontroluju, jeslti byl zaznam opravdu ulozen do DB
				$query = dibi::select('1')->from($this->metadata->getTableName());
				$idFields = $this->metadata->getIdFields();
				foreach($idFields as $name) 
					$query = $query->where("[$name] = %s", $this->data->$name);

				$result = $query->fetch();
				if($result === false)
					throw new EntityException('Error saving entity. Missing mandatory fields?', EntityException::SAVE_FAILED);
			}
			
			dibi::commit();
			$this->data->performSaveMerge();
		} catch(\Exception $e) {
			dibi::rollback();
			throw $e;
		}
		
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
		$this->checkIfIdIsDefined(true);
		
		dibi::begin();
		
		$query = dibi::delete($this->metadata->getTableName());
		$idFields = $this->metadata->getIdFields();
		foreach($idFields as $name) 
			$query = $query->where("[$name] = %s", $this->data->$name);
		
		$query = $query->limit("1");
		
		$query->execute();
		if(dibi::affectedRows() == 0) $this->throwNoRecordFound();
		
		$tmpState = $this->state;
		$this->state = self::STATE_DELETED;
		
		try {
			$this->onDelete($this);
			dibi::commit();
		} catch(Exception $e) {
			dibi::rollback();
			$this->state = $tmpState;
			throw $e;
		}
		
		return $this;
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
