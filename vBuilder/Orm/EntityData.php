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
 * Entity data holder.
 * 
 * This class is provider of RAW data for Entity classes. It takes care of
 *  calling onNeedToFetch events to perform lazy-loading. Class is also
 *  responsible for holding only one instance of data and it's fullfilling
 *  after load.
 * 
 * Any data can be changed directly by setter. In that case the onFieldChanged
 *  event is called for letting Entity class know about obligation to clear cache.
 * 
 * Any such change won't harm any data unless the performSaveMerge is called.
 *  This method is used to project changes into global data storage. Which also means
 *  that all instances of saved entity will update their data to new state (Of course
 *  any local changes will persist).
 * 
 * If performRollback is called all changes are deleted and class returns unchanged
 * data from last loaded state.
 * 
 * The event onPreload is called when data are recieved through loadData function
 *  (which is called from internal load of ActiveEntity or from constructor, if it takes data). You
 *  can use this event if you want to change data before loading them into class.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 17, 2011
 */
class EntityData extends vBuilder\Object {
	
	/** @var array of metadata */
	private $metadata;
	
	/** @var array of data from db */
	private $data = array();
	
	/** @var array of newly set data */
	private $newData;
	
	/** @var array of event listeners for fetch event */
	public $onNeedToFetch = array();
	
	/** @var array of event listeners for field modification */
	public $onFieldChanged = array();
	
	/** @var array of event listeners for first read */
	public $onFirstRead = array();
	
	/** @var Holds true until any field is accessed for the first time */
	private $firstRead = true;
	
	/** @var array of all loaded entities data */
	private static $_repository = array();
	
	/** @var need to fetch lock */
	private $currentlyPerformingOnNeedToFetch = false;
	
	/** @var string repository key */
	private $repositoryId;
	
	/**
	 * Constructor of entity data object
	 * 
	 * @param IEntityMetadata metadata object instance (ref)
	 * @param array of initial data
	 */
	public function __construct(IEntityMetadata &$metadata, array $data = array(), IRepository $repository) {
		$this->metadata = &$metadata;
		$this->repositoryId = get_class($repository);
		$this->loadData($data);
	}
	
	/**
	 * Returns array of changed fields
	 * 
	 * @param bool true if keys of array should be real column names instead of field names
	 * 
	 * @return array of data (associative)
	 */
	public function getChangedData($realColumnNames = false) {
		if(!$realColumnNames || $this->newData === null) return (array) $this->newData;
		
		$data = array();
		foreach($this->newData as $field=>$value)
			$data[$this->metadata->getFieldColumn($field)] = $value;
		
		return $data;
	}
	
	/**
	 * Returns array of all fields with current values.
	 * This function does NOT call onNeedToFetch event,
	 * not set fields will be set to null.
	 * 
	 * @param bool true if keys of array should be real column names instead of field names
	 * 
	 * @return array of field values (associative)
	 */
	public function getAllData($realColumnNames = false) {
		$data = array();
		$fields = $this->metadata->getFields();
		foreach($fields as $name) { 
			$key = $realColumnNames ? $this->metadata->getFieldColumn($name) : $name;
			
			if(isset($this->newData) && array_key_exists($name, $this->newData)) $data[$key] = $this->newData[$name];
			elseif(array_key_exists($name, $this->data)) $data[$key] =  $this->data[$name];
			else $data[$key] = null;
		}
		
		return $data;
	}
	
	/**
	 * Loads data into record
	 * 
	 * @param array of field values (associative)
	 * @param bool true if keys are column names, false if they are field names
	 */
	public function loadData(array $newData, $trColumnNames = true) {
		if($trColumnNames) {
			$trData = array();
			foreach($this->metadata->getFields() as $name) {
				$colName = $this->metadata->getFieldColumn($name);
				if(array_key_exists($colName, $newData)) $trData[$name] = $newData[$colName];
			} 
			
			$this->mergeToRepository($trData);
			return ;
		}
		
		$this->mergeToRepository($newData);
	}
	/**
	 * Merge data from array to this entity.  All fields in this array overwrite existing ones.
	 * 
	 * Warning: array is supposed to be indexed by FIELD names (not column names)
	 * 
	 * @param array of data to merge
	 */
	public function mergeData(array $mergeData) {
		foreach($mergeData as $k => $v) $this->$k = $v;
	}
	
	/**
	 * Performs merge of changed data into repository, so all instances of this
	 * entity will update their data. Data are also marked as persist so no rollback
	 * will take any effect. 
	 * 
	 * You can use function mergeData to add fields such as auto-increment
	 * ids which wasn't known before save.
	 */
	public function performSaveMerge() {
		if(!isset($this->newData)) return ;
		
		$this->mergeToRepository((array) $this->newData);
		$this->newData = null;
	}
	
	/**
	 * Reverts to last loaded state. If any field has been
	 * changed since it will be changed and call onFieldChanged on it.
	 */
	public function performRollback() {
		if(!isset($this->newData)) return ;
		
		$changedFields = array_keys($this->newData);
		$this->newData = null;
		
		foreach($changedFields as $name) $this->onFieldChanged($name);
	}

	/**
	 * Magic getter. Gets current value by object access.
	 * Calls onNeedToFetch event if fetch is neaded.
	 * 
	 * @param string field name
	 * @return mixed 
	 */
	public function & __get($name) {
		if($this->metadata->hasField($name)) {
						
			$result = null;
			
			for($i = 0; $i < 2; $i++) {
				if(isset($this->newData) && array_key_exists($name, $this->newData)) { 
					$result = $this->newData[$name]; break;
				} elseif(array_key_exists($name, $this->data)) { 
					$result = $this->data[$name]; break;
				}
				
				if($i != 1) {
					// Nacitani pro me nema smysl, pokud policko nepatri moji entite
					if($this->metadata->getFieldType($name) == 'OneToMany') return $result;

					//debug("On need to fetch called for field", $name, $this->metadata->getTableName(), $this->data);
					//echo "----<br />\n";
					
					if(!$this->currentlyPerformingOnNeedToFetch) {
						$this->currentlyPerformingOnNeedToFetch = true;
						$this->onNeedToFetch();
						$this->currentlyPerformingOnNeedToFetch = false;
					} else {
						return $result;
					}
				}
			}
			
			// Musi byt az za samotnym nactenim, kvuli overovacim funkcim
			if($this->firstRead && !in_array($name, $this->metadata->getIdFields()) && $this->checkIfIdIsDefined()) {
				$this->firstRead = false; // Musi byt pred samotnym volanim, aby se zabranilo pripadnym cyklickym zavislostem
				$this->onFirstRead();
			}
			
			return $result;
		}
		
		return parent::__get($name);
	}
	
	/**
	 * Magic setter. Sets new value by object access.
	 * This function calls onFieldChanged if necessary.
	 * 
	 * @param string field name
	 * @param mixed new value
	 */
	public function __set($name, $value) {
		if($this->metadata->hasField($name)) {
			if(isset($this->newData) && array_key_exists($name, $this->newData))
				$current = $this->newData[$name];
			elseif(array_key_exists($name, $this->data))
				$current = $this->data[$name];
			else
				$current = null;
			
		
			if( $current !== $value || $current === null ) {				
				$this->newData[$name] = $value;
				$this->onFieldChanged($name);
			}
			
			return ;
		}
		
		parent::__set($name, $value);
	}
	
	/**
	 * Magic isset function. Checks if this field has already set.
	 * 
	 * @param string field name
	 * @return bool
	 */
	public function __isset($name) {
		if($this->metadata->hasField($name)) {
			return isset($this->newData[$name]) || isset($this->data[$name]);
		}
		
		parent::__isset($name);
	}
	
	/**
	 * Same as isset but allowing NULL values
	 * 
	 * @param string field name
	 * @return bool 
	 */
	public function isKeySet($name) {
		if($this->metadata->hasField($name)) {
			return array_key_exists($name, (array) $this->newData) || array_key_exists($name, (array) $this->data);
		}
		
		return false;
	}
	
	/**
	 * Function merges new data to repository
	 * 
	 * @param array of new data 
	 */
	private function mergeToRepository(array $newData) {		
		$oldData = $this->data;

		// Vyselektovani ID soucasneho a noveho zaznamu
		$newId = array();
		$oldId = array();
		$idFields = $this->metadata->getIdFields();
		foreach($idFields as $name) {
			if(isset($this->data[$name])) $oldId[] = $this->data[$name];
			
			// Pro nove IDcko pouzivam fragmenty z obou vzorku dat
			if(isset($newData[$name])) $newId[] = $newData[$name]; 
			
			// Pro pripady odpojeni musim kontrolovat existenci, protoze by mi pri NULL muze skocit fallback
			elseif(!array_key_exists($name, $newData) && isset($this->data[$name])) $newId[] = $this->data[$name];
		}
		
		// Pokud soucasna data maji validni ID a neni shodna s ID starych dat,
		//  musim je svazat s repozitarem => menim referenci
		if(count($newId) == count($idFields) && $newId != $oldId) {
			
			// Vytvorim si referenci pro nove ID
			$this->data = &self::$_repository[$this->repositoryId][$this->metadata->getTableName()];
			foreach($newId as $curr) {
				if(!isset($this->data[$curr])) $this->data[$curr] = array();
				$this->data = &$this->data[$curr];
			}
			
			$this->firstRead = true;
			
		// Pokud mam neuplne ID vytvorim data mimo repozitar
		} elseif(count($newId) != count($idFields)) {
			$newVar = array();
			$this->data = &$newVar;
			
			$this->firstRead = true;
		}
		
		// Merge dat
		foreach($oldData as $k => $v) $this->data[$k] = $v;
		foreach($newData as $k => $v) $this->data[$k] = $v;
	}
	
	/**
	 * Checks if all ID fields are defined. 
	 * 
	 * Warning: Not checking in $newData
	 * 
	 * @return bool
	 */
	protected function checkIfIdIsDefined() {
		foreach($this->metadata->getIdFields() as $name) {
			//if(!isset($this->$name))
			if(!isset($this->data[$name]))
				return false;
		}
		
		return true;
	}
	
}
