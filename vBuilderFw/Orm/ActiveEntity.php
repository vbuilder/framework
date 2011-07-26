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
		
		// Delam zvlast, protoze jinak by se mohla vyhazovat
		// vyjimka pri DibiFluent::__toString
		if(!$this->getDb()->isConnected()) $this->getDb()->connect();
		
		$query = $this->getDb()->select('*')->from($this->metadata->getTableName());
		$idFields = $this->metadata->getIdFields();
		foreach($idFields as $name) 
			$query = $query->where("[".$this->metadata->getFieldColumn($name)."] = %s", $this->data->$name);
		
		$result = $query->fetch();
		if($result !== false) {
			$loadedData = (array) $result;			
			$this->data->loadData($loadedData);
			
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
		$fields = $this->metadata->getFields();
		$autoField = null;
		
		// Pokud jsou na zaznam vazany relace, ktere je treba ulozit
		$needToSaveEvenWithoutData = false;
		
		dibi::begin();	
		
		try {
			// Provedu upravy pred ulozenim (zaregistrovane Behaviors, etc.)
			$this->onPreSave($this);

			// Kontrola, jestli mam definovane sloupce s PK indexem
			foreach($idFields as $name) {
				if(!$this->metadata->isFieldGenerated($name)) {
					if(!isset($this->data->$name))
						throw new EntityException("Cannot save with missing value for field '$name' which is mandatory because of ID index", EntityException::ID_NOT_DEFINED);
				} elseif($autoField === null) {
					$autoField = $name;
				} else
					throw new \LogicException('More than one generated ID field are not supported');
			}

			// Nactu si vsechny zmenene polozky do pole: sloupec => hodnota
			$updateData = $this->data->getChangedData(true);
			
			// Pole se vsemi virtualnimi sloupci, ktere jsou ve skutecnosti vazany v jine tabulce
			// Musime je na konci odebrat z tech, co se ukladaji do teto entity
			$externalFields = array();
			
			
			// Projdu vsechny registrovane polozky a overim pripadne externi vazby
			foreach($fields as $curr) {
				$type = $this->metadata->getFieldType($curr);
				
				// Pokud je polozka OneToOne relaci (z moji strany -> mappedBy moje entita)
				// musim ji ulozit PRED samotnou entitou (potrebuje jeji ID)
				// Po ulozeni svazane entity si musim vzit jeji ID a pridat ho do dat k ulozeni.
				if($type == 'OneToOne') {
					if($this->metadata->getFieldMappedBy($curr) === null || (get_called_class() != $this->metadata->getFieldMappedBy($curr) && !is_subclass_of(get_called_class(), $this->metadata->getFieldMappedBy($curr)) )) {						
						
						// Ukladam jen non-NULL sloupce
						if(isset($this->data->{$curr})) {
							// Bacha na to, ze to nesmim brat z dat (kvuli tomu, ze tam muze bejt
							// pri nacteni z DB pouze ID)
							$targetEntity = $this->{$curr};
							if($targetEntity !== null) {
								if(!($targetEntity instanceof ActiveEntity))
									throw new \LogicException("Can't save OneToMany entity for field '$curr'. Data object is not instance of ActiveEntity.");

								$targetEntity->save();
								$joinPairs = $this->metadata->getFieldJoinPairs($curr);
								if(count($joinPairs) != 1) throw new \LogicException("Joining entity on more keys is currently not supported");
								list($localIdField, $targetIdField) = reset($joinPairs);
								if($this->data->{$localIdField} !== $targetEntity->{$targetIdField})
									$updateData[$this->metadata->getFieldColumn($localIdField)] = $targetEntity->{$targetIdField};
							}
						}
					
					// Pokud polozka je pouze mapovana na nasi entitu, klic je na druhe strane
					// => Ukladam ji az po ulozeni sama sebe
					} else {
						$externalFields[] = $curr;
						unset($updateData[$curr]);
					}
				}
				
				// Pokud je polozka OneToMany relaci, sloupec v teto entite neexistuje,
				// vazba probiha na druhem konci, proto data vyloucim z updatu.
				// Entity se musi ulozi az POTOM, co se ulozi tato entita (aby znaly jeji ID).
				elseif($type == 'OneToMany') {
					if($this->{$curr} instanceOf Collection && $this->{$curr}->count())
						$needToSaveEvenWithoutData = true;
					
					$externalFields[] = $curr;
					unset($updateData[$curr]);
				}
			}
			
			// Vsechny data k ulozeni do tabulky (vcetne tech nezmenenych, ale bez virtualnich sloupcu - vazeb)
			$allTableFields = array_diff_key(array_merge($this->data->getAllData(true), $updateData), array_flip($externalFields));	
			
			// ULOZENI SAMOTNE ENTITY ---------------------------------------------
			// Pokud jsou k ulozeni nejaka TABULKOVA data, ulozim je
			$addtionalDataToMerge = array();
			$action = null;
			if(count($updateData) > 0 || $needToSaveEvenWithoutData) {
				dibi::query('INSERT IGNORE ', $this->metadata->getTableName(), $allTableFields, '%if', $updateData, ' ON DUPLICATE KEY UPDATE %a', $updateData);

				// Provedl se INSERT
				if(dibi::affectedRows() == 1) {

					// Zjistim ID pro generovane sloupce
					$addtionalDataToMerge = $autoField === null ? array() : array($autoField => dibi::insertId());
					$this->data->mergeData($addtionalDataToMerge);

					$action = 'create';
				}

				// Provedl se UPDATE
				elseif(dibi::affectedRows() == 2) {
					$action = 'update';
				}

				// Data se nezmenila
				else {
					// Nevim jestli je to takhle uplne idealni, sice insert ignore ... on duplicate key update
					// setri zamykani tabulky, ale zese je treba overovat, jestli se neco neposralo
					// a pokud jo, tak nemam zadny chybovy report
					// Zkontroluju, jeslti byl zaznam opravdu ulozen do DB
					$query = $this->getDb()->select('1')->from($this->metadata->getTableName());
					$idFields = $this->metadata->getIdFields();
					foreach($idFields as $name)
						$query = $query->where("[".$this->metadata->getFieldColumn($name)."] = %s", $this->data->$name);

					$result = $query->fetch();
					if($result === false)
						throw new EntityException('Error saving entity. Missing mandatory fields?', EntityException::SAVE_FAILED);
				}
			}
			
			// KONEC ULOZENI ENTITY -----------------------------------------------
			
			// Relace (OneToMany) -> ulozeni externich sloupcu
			foreach($externalFields as $curr) {
				
				// Reverzni OneToOne relace
				if($this->metadata->getFieldType($curr) == 'OneToOne') {
					if($this->metadata->getFieldMappedBy($curr) !== null && (get_called_class() == $this->metadata->getFieldMappedBy($curr) || is_subclass_of(get_called_class(), $this->metadata->getFieldMappedBy($curr)) ) ) {
						
						// Bacha na to, ze to nesmim brat z dat (kvuli tomu, ze tam muze bejt
						// pri nacteni z DB pouze ID)
						$targetEntity = $this->{$curr};
						if($targetEntity !== null) {
							if($targetEntity->hasChanged())
								throw new Nette\NotImplementedException("Saving of reversed OneToOne entities is currently not implemented");
						}
						
					}
					
					continue;
				}
				
	
				// Dale resim pouze OneToMany
				if($this->metadata->getFieldType($curr) != 'OneToMany') continue;
				
				// OneToMany zalozene na kolekcich
				if($this->{$curr} instanceOf EntityCollection) {
					$this->{$curr}->save();
					continue;
				}
				
				// Puvodni save pro OneToMany (TODO: prepsat taky na kolekce)
				// Pokud se data zmenila
				if(array_key_exists($curr, $this->data->getChangedData())) {
					
					// Momentalne nepodporuju relace s plnou entitou
					if($this->metadata->getFieldEntityName($curr) !== null)
						throw new Nette\NotImplementedException("Entity based OneToMany save is currently not implemented");
					
					// Smazu soucasny zaznamy a vytvorim si ID data pro nove
					$joinIdFields = array();
					$query2 = $this->getDb()->delete($this->metadata->getFieldTableName($curr));
					foreach($this->metadata->getFieldJoinPairs($curr) as $join)  {
						$query2->where("[".$join[1]."] = %s", $this->{$join[0]});	
						$joinIdFields[$join[1]] = $this->{$join[0]};
					}
					$query2->execute();
					
					$ch = $this->data->getChangedData();
					$dataToSave = (array) $ch[$curr];
					if(count($dataToSave) > 0) {
						// Pokud se jedna o neassociativni pole, musim zjistit nazvy sloupcu
						if(!is_array(reset($dataToSave))) {
							$columnNames = $this->getDb()->query("SHOW COLUMNS FROM [".$this->metadata->getFieldTableName($curr)."]")->fetchAll();
							
							$singleColumnKey = null;
							foreach($columnNames as $column) {								
								if(!array_key_exists($column["Field"], $joinIdFields)) {
									if($singleColumnKey != null) throw new \LogicException("Saving joined single column data into multi column table");
									$singleColumnKey = $column["Field"];
								}
							}
						}
						
						// Nahazim tam nove
						foreach($dataToSave as $joinFields) {
							$iData = array_merge(!is_array($joinFields) ? array($singleColumnKey => $joinFields) : $joinFields, $joinIdFields);
							dibi::insert($this->metadata->getFieldTableName($curr), $iData)->execute();
						}
					}
				}
			}
			
			
			// Zavolani eventu
			if($action == 'create')	$this->onCreate($this);
			elseif($action == 'update') $this->onUpdate($this);
			$this->onPostSave($this);
			
			// Commitnuti dat a provedeni merge automaticky generovanech polozek do entity
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
		
		$query = $this->getDb()->delete($this->metadata->getTableName());
		$idFields = $this->metadata->getIdFields();
		foreach($idFields as $name) 
			$query = $query->where("[".$this->metadata->getFieldColumn($name)."] = %s", $this->data->$name);
		
		$query = $query->limit("1");
		
		$query->execute();
		if(dibi::affectedRows() == 0) $this->throwNoRecordFound();
		
		// Relace (OneToMany, ...)
		// TODO: Prekontrolovat preklad nazvu sloupcu, nejak se mi to nelibi
		foreach($this->metadata->getFields() as $curr) {
			if($this->metadata->getFieldType($curr) == "OneToMany") {
				$query2 = $this->getDb()->delete($this->metadata->getFieldTableName($curr));
				foreach($this->metadata->getFieldJoinPairs($curr) as $join) {
					$query2->where("[".$join[1]."] = %s", $this->{$join[0]});
				}
				
				$query2->execute();
			}
		}
		
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
	 * Returns connection to DB
	 * 
	 * @return dibi\DibiConnection
	 */
	protected function getDb() {
		return $this->container->connection;
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
			
			$acl = Nette\Environment::getUser()->getAuthorizationHandler();
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
