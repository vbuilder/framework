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
		
		// Delam zvlast, protoze jinak by se mohla vyhazovat
		// vyjimka pri DibiFluent::__toString
		dibi::getConnection()->connect();
		
		$query = dibi::select('*')->from($this->metadata->getTableName());
		$idFields = $this->metadata->getIdFields();
		foreach($idFields as $name) 
			$query = $query->where("[$name] = %s", $this->data->$name);
		
		$result = $query->fetch();
		if($result !== false) {
			$loadedData = (array) $result;
			
			// Relace (OneToMany, ...)
			// TODO: Bylo by hezky to udelat lazy
			// imho by slo zrusit na zacatku $this->state != self::STATE_NEW
			// a volat load s nazvem sloupce
			foreach($this->metadata->getFields() as $curr) {
				if($this->metadata->getFieldType($curr) == "OneToMany") {
					// Vytvorim DS
					if($this->metadata->getFieldEntityName($curr) !== null)
						$ds = Repository::findAll($this->metadata->getFieldEntityName($curr));
					else
						$ds = new DataSource((String) dibi::select("*")->from($this->metadata->getFieldTableName($curr)), $class);
					
					// Podminky spojeni
					foreach($this->metadata->getFieldJoinPairs($curr) as $join) 
						$ds->where("[".$join[1]."] = %s", $this->{$join[0]});
					
					
					// Stahnu data a pokud se jedna o jednoduche spojeni (bez entity)
					// Rozparsuju to do pole
					$joinedData = $ds->fetchAll();
					if($this->metadata->getFieldEntityName($curr) === null) {
						$joinKeys = array();
						foreach($this->metadata->getFieldJoinPairs($curr) as $join) $joinKeys[$join[1]] = null;
						
						$d = array();
						foreach($joinedData as $c) {
							$cd = array_diff_key((array) $c, $joinKeys);
							if(count($cd) == 1) $cd = current($cd);
							
							$d[] = $cd;
						}

						$loadedData[$curr] = $d;
					} else						 
						$loadedData[$curr] = $joinedData;
				}
			}
			
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
		
		// Vytridim polozky, ktere nejsou primo v tyhle tabulce (relace)
		$externalFields = array();
		foreach($this->metadata->getFields() as $curr) {
			if($this->metadata->getFieldType($curr) == "OneToMany") {
				$externalFields[] = $curr;
			}
		}
		

		$allChangedFieldsData = $this->data->getChangedData();
		$changedFieldsData = array_diff_key($allChangedFieldsData, array_flip($externalFields));		
		if(count($allChangedFieldsData) == 0) return ;		
		$allFieldsData = array_diff_key($this->data->getAllData(), array_flip($externalFields));
		
		
		dibi::begin();	
		
		try {
			// Pokud jsou k ulozeni nejaka TABULKOVA data musim je ulozid driv nez zacnu resit
			// relace, protoze by jinak nemuselo byt k dispozici IDcko
			if(count($changedFieldsData) > 0) {
				dibi::query('INSERT IGNORE ', $this->metadata->getTableName(), $allFieldsData, ' ON DUPLICATE KEY UPDATE %a', $changedFieldsData);

				// Provedl se INSERT
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
			}
			
			// Relace (OneToMany, ...) -> ulozeni extenrich sloupcu
			foreach($externalFields as $curr) {
				// Pokud se data zmenila
				if(isset($allChangedFieldsData[$curr])) {
					
					// Momentalne nepodporuju relace s plnou entitou
					if($this->metadata->getFieldEntityName($curr) !== null)
						throw new \NotImplementedException("Entity based OneToMany save is currently not implemented");
					
					// Smazu soucasny zaznamy a vytvorim si ID data pro nove
					$joinIdFields = array();
					$query2 = dibi::delete($this->metadata->getFieldTableName($curr));
					foreach($this->metadata->getFieldJoinPairs($curr) as $join)  {
						$query2->where("[".$join[1]."] = %s", $this->{$join[0]});	
						$joinIdFields[$join[1]] = $this->{$join[0]};
					}
					$query2->execute();
					
					if(count($allChangedFieldsData[$curr]) > 0) {
						// Pokud se jedna o neassociativni pole, musim zjistit nazvy sloupcu
						if(!is_array(reset($allChangedFieldsData[$curr]))) {
							$columnNames = dibi::query("SHOW COLUMNS FROM [".$this->metadata->getFieldTableName($curr)."]")->fetchAll();
							
							$singleColumnKey = null;
							foreach($columnNames as $column) {								
								if(!array_key_exists($column["Field"], $joinIdFields)) {
									if($singleColumnKey != null) throw new \LogicException("Saving joined single column data into multi column table");
									$singleColumnKey = $column["Field"];
								}
							}
						}
						
						// Nahazim tam nove
						foreach($allChangedFieldsData[$curr] as $joinFields) {
							$iData = array_merge(!is_array($joinFields) ? array($singleColumnKey => $joinFields) : $joinFields, $joinIdFields);
							dibi::insert($this->metadata->getFieldTableName($curr), $iData)->execute();
						}
					}
				}
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
		
		// Relace (OneToMany, ...)
		foreach($this->metadata->getFields() as $curr) {
			if($this->metadata->getFieldType($curr) == "OneToMany") {
				$query2 = dibi::delete($this->metadata->getFieldTableName($curr));
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