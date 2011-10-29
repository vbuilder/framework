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
 * Entity repository build over Dibi
 *
 * @author Adam Staněk (V3lbloud)
 * @since Mar 4, 2011
 */
class DibiRepository extends BaseRepository {
	
	/** @var DibiConnection DB connection */
	protected $db;

	private $_inProgressLock = array();
	
	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI
	 */
	public function __construct(Nette\DI\IContainer $context) {
		parent::__construct($context);
		
		$this->db = $this->context->connection;
	}
	
	/**
	 * Creates Fluent for building select query
	 * 
	 * @param string entity name
	 * @return vBuilder\Orm\Fluent
	 */
	public function findAll($entityName, $processSubclasses = false) {
		// Kvuli objednavkam, krici to tam, protoze se to pouziva v session, ale tady
		// to neni potreba
		/* if($processSubclasses)
			throw new Nette\NotImplementedException("findAll() with subclasses is not supported by " . get_called_class() . " at the moment."); */
		
		$class = self::getEntityClass($entityName);
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
	 * Loads data from DB
	 * 
	 * @param Entity|Collection data holder
	 * 
	 * @return bool true if record has been successfuly loaded, false if record does not exist
	 */
	public function load($holder) {
		// Delam zvlast, protoze jinak by se mohla vyhazovat
		// vyjimka pri DibiFluent::__toString
		if(!$this->db->isConnected()) $this->db->connect();
		
		if($holder instanceof Entity) {
			return $this->loadEntity($holder);
		} elseif($holder instanceof Collection) {
			return $this->loadCollection($holder);
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
		$query = $this->db->select('*')->from($entity->metadata->getTableName());
		$idFields = $entity->metadata->getIdFields();
		foreach($idFields as $name) 
			$query = $query->where("[".$entity->metadata->getFieldColumn($name)."] = %s", $entity->data->$name);
		
		$result = $query->fetch();
		if($result !== false) {
			$loadedData = (array) $result;			
			$entity->data->loadData($loadedData);
			return true;
		}
		
		return false;
	}
	
	protected function loadCollection($collection) {
		$parentMetadata = $collection->parent->getMetadata();

		if($parentMetadata->getFieldTableName($collection->idField) == null)
			throw new \InvalidArgumentException("Table name for field '$collection->idField' in entity '".get_class($collection->parent)."' has to be specified");

		$ds = $this->db->select("*")->from($parentMetadata->getFieldTableName($collection->idField));

		// Podminky spojeni a separace joinKeys
		$joinKeys = array();
		foreach($parentMetadata->getFieldJoinPairs($collection->idField) as $join) {
			$ds->where("[".$join[1]."] = %s", $collection->parent->{$join[0]});
			$joinKeys[$join[1]] = null;
		}

		$targetData = $ds->fetchAll();

		// Upravim data do pole
		$d = array();
		foreach($targetData as $c) {
			$cd = array_diff_key((array) $c, $joinKeys);
			if(count($cd) == 1)
				$cd = current($cd);

			$d[] = $cd;
		}
		
		$collection->performDataLoad($d);
		return true;
	}
	
	/**
	 * Removes entity from repository
	 * 
	 * @param Entity entity 
	 */
	public function delete(Entity $entity) {
		$this->db->begin();
		
		if($entity instanceof ActiveEntity) $entity->onPreDelete($entity);
		
		$query = $this->db->delete($entity->metadata->getTableName());
		$idFields = $entity->metadata->getIdFields();
		foreach($idFields as $name) 
			$query = $query->where("[".$entity->metadata->getFieldColumn($name)."] = %s", $entity->data->$name);
		
		$query = $query->limit("1");
		
		$query->execute();
		if($this->db->affectedRows() == 0) return false;
		
		// Relace (OneToMany, ...)
		// TODO: Prekontrolovat preklad nazvu sloupcu, nejak se mi to nelibi
		foreach($entity->metadata->getFields() as $curr) {
			if($entity->metadata->getFieldType($curr) == "OneToMany") {
				$query2 = $this->db->delete($entity->metadata->getFieldTableName($curr));
				foreach($entity->metadata->getFieldJoinPairs($curr) as $join) {
					$query2->where("[".$join[1]."] = %s", $entity->{$join[0]});
				}
				
				$query2->execute();
			}
		}
		
		try {
			if($entity instanceof ActiveEntity)
				$entity->onPostDelete($entity);
			
			$this->db->commit();
			return true;
		} catch(\Exception $e) {
			$this->db->rollback();
			throw $e;
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
		$result = false;
		
		$classId = get_class($holder) . md5($holder);
		if($holder instanceof Entity) {
			$d = "";
			foreach($holder->data->getAllData() as $key => $value) {
				if(!$holder->metadata->isFieldGenerated($key))
					$d .= $value;
			}
				
			$classId .= md5($d);
		}
		
		// Ochrana proti nekonecneho cyklu pri ukladani obousmernych vazeb
		if(!isset($this->_inProgressLock[$classId])) {
			$this->_inProgressLock[$classId] = true;

			// Delam zvlast, protoze jinak by se mohla vyhazovat
			// vyjimka pri DibiFluent::__toString
			if(!$this->db->isConnected()) $this->db->connect();

			if($holder instanceof Entity) {
				$result = $this->saveEntity($holder);
			} elseif($holder instanceof EntityCollection) {
				$result = $this->saveEntityCollection($holder);
			}

			unset($this->_inProgressLock[$classId]);
			
			if($result === false)
				throw new Nette\NotSupportedException("Saving of class '".get_class($holder)."' is not supported by repository '".get_called_class()."'");		

		}
		
		return $result;
	}
	
	public function saveEntity(Entity $entity) {
		if($entity->repository !== $this && $entity instanceof ActiveEntity) $entity->load();
		
		$idFields = $entity->metadata->getIdFields();
		$fields = $entity->metadata->getFields();
		$autoField = null;
		
		// Pokud jsou na zaznam vazany relace, ktere je treba ulozit
		$needToSaveEvenWithoutData = $entity->repository !== $this;
		
		$this->db->begin();	
		
		try {
			// Provedu upravy pred ulozenim (zaregistrovane Behaviors, etc.)
			if($entity instanceof ActiveEntity)	$entity->onPreSave($entity);

			// Kontrola, jestli mam definovane sloupce s PK indexem
			foreach($idFields as $name) {
				if(!$entity->metadata->isFieldGenerated($name)) {
					if(!isset($entity->data->$name))
						throw new EntityException("Cannot save '".get_class($entity)."' with missing value for field '$name' which is mandatory because of ID index", EntityException::ID_NOT_DEFINED);
				} elseif($autoField === null) {
					$autoField = $name;
				} else
					throw new \LogicException('More than one generated ID field are not supported');
			}

			// Nactu si vsechny zmenene polozky do pole: sloupec => hodnota
			$updateData = $entity->data->getChangedData(true);
			
			// Pole se vsemi virtualnimi sloupci, ktere jsou ve skutecnosti vazany v jine tabulce
			// Musime je na konci odebrat z tech, co se ukladaji do teto entity
			$externalFields = array();
			
			
			// Projdu vsechny registrovane polozky a overim pripadne externi vazby
			foreach($fields as $curr) {
				$type = $entity->metadata->getFieldType($curr);
				
				// Pokud je polozka OneToOne relaci (z moji strany -> mappedBy moje entita)
				// musim ji ulozit PRED samotnou entitou (potrebuje jeji ID)
				// Po ulozeni svazane entity si musim vzit jeji ID a pridat ho do dat k ulozeni.
				if($type == 'OneToOne') {
					if($entity->metadata->getFieldMappedBy($curr) === null || (get_class($entity) != $entity->metadata->getFieldMappedBy($curr) && !is_subclass_of(get_class($entity), $entity->metadata->getFieldMappedBy($curr)) )) {						
						
						// Ukladam jen non-NULL sloupce
						if(isset($entity->data->{$curr})) {
							// Bacha na to, ze to nesmim brat z dat (kvuli tomu, ze tam muze bejt
							// pri nacteni z DB pouze ID)
							$targetEntity = $entity->{$curr};
							if($targetEntity !== null) {
								if(!($targetEntity instanceof ActiveEntity))
									throw new \LogicException("Can't save OneToMany entity for field '$curr'. Data object is not instance of ActiveEntity.");

								$this->save($targetEntity);
								
								$joinPairs = $entity->metadata->getFieldJoinPairs($curr);
								if(count($joinPairs) == 0) throw new \LogicException("Missing join pairs for ".get_class($entity)."::$curr, forgot to set joinOn/joinUsing?");
								elseif(count($joinPairs) > 1) throw new \LogicException("Joining entity on more keys is currently not supported (".get_class($entity)."::$curr)");
								
								list($localIdField, $targetIdField) = reset($joinPairs);
								if($entity->data->{$localIdField} !== $targetEntity->{$targetIdField})
									$updateData[$entity->metadata->getFieldColumn($localIdField)] = $targetEntity->{$targetIdField};
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
					if($entity->{$curr} instanceOf Collection && $entity->{$curr}->count())
						$needToSaveEvenWithoutData = true;
					
					$externalFields[] = $curr;
					unset($updateData[$curr]);
				}
			}
			
			// Vsechny data k ulozeni do tabulky (vcetne tech nezmenenych, ale bez virtualnich sloupcu - vazeb)
			$allTableFields = array_diff_key(array_merge($entity->data->getAllData(true), $updateData), array_flip($externalFields));	
						
			// ULOZENI SAMOTNE ENTITY ---------------------------------------------
			// Pokud jsou k ulozeni nejaka TABULKOVA data, ulozim je
			$addtionalDataToMerge = array();
			$action = null;
			if(count($updateData) > 0 || $needToSaveEvenWithoutData) {
				$this->db->query('INSERT IGNORE ', $entity->metadata->getTableName(), $allTableFields, '%if', $updateData, ' ON DUPLICATE KEY UPDATE %a', $updateData);

				// Provedl se INSERT
				if($this->db->affectedRows() == 1) {

					// Zjistim ID pro generovane sloupce
					$addtionalDataToMerge = $autoField === null ? array() : array($autoField => $this->db->insertId());
					$entity->data->mergeData($addtionalDataToMerge);

					$action = 'create';
				}

				// Provedl se UPDATE
				elseif($this->db->affectedRows() == 2) {
					$action = 'update';
				}

				// Data se nezmenila
				else {
					// Nevim jestli je to takhle uplne idealni, sice insert ignore ... on duplicate key update
					// setri zamykani tabulky, ale zese je treba overovat, jestli se neco neposralo
					// a pokud jo, tak nemam zadny chybovy report
					// Zkontroluju, jeslti byl zaznam opravdu ulozen do DB
					$query = $this->db->select('1')->from($entity->metadata->getTableName());
					$idFields = $entity->metadata->getIdFields();
					foreach($idFields as $name)
						$query = $query->where("[".$entity->metadata->getFieldColumn($name)."] = %s", $entity->data->$name);

					$result = $query->fetch();
					if($result === false)
						throw new EntityException('Error saving entity. Missing mandatory fields?', EntityException::SAVE_FAILED);
				}
			}
			
			// KONEC ULOZENI ENTITY -----------------------------------------------
			
			// Relace (OneToMany) -> ulozeni externich sloupcu
			foreach($externalFields as $curr) {
				
				// Reverzni OneToOne relace
				if($entity->metadata->getFieldType($curr) == 'OneToOne') {
					if($entity->metadata->getFieldMappedBy($curr) !== null && (get_class($entity) == $entity->metadata->getFieldMappedBy($curr) || is_subclass_of(get_class($entity), $entity->metadata->getFieldMappedBy($curr)) ) ) {
						
						// Bacha na to, ze to nesmim brat z dat (kvuli tomu, ze tam muze bejt
						// pri nacteni z DB pouze ID)
						$targetEntity = $entity->{$curr};
						if($targetEntity !== null) {
							if($targetEntity->hasChanged())
								throw new Nette\NotImplementedException("Saving of reversed OneToOne entities is currently not implemented");
						}
						
					}
					
					continue;
				}
				
	
				// Dale resim pouze OneToMany
				if($entity->metadata->getFieldType($curr) != 'OneToMany') continue;
				
				// OneToMany zalozene na kolekcich
				if($entity->{$curr} instanceOf EntityCollection) {
					$entity->{$curr}->save($this);
					continue;
				}
				
				// Puvodni save pro OneToMany (TODO: prepsat taky na kolekce)
				// Pokud se data zmenila
				if(array_key_exists($curr, $entity->data->getChangedData())) {
					
					// Momentalne nepodporuju relace s plnou entitou
					if($entity->metadata->getFieldEntityName($curr) !== null)
						throw new Nette\NotImplementedException("Entity based OneToMany save is currently not implemented");
					
					// Smazu soucasny zaznamy a vytvorim si ID data pro nove
					$joinIdFields = array();
					$query2 = $this->db->delete($entity->metadata->getFieldTableName($curr));
					foreach($entity->metadata->getFieldJoinPairs($curr) as $join)  {
						$query2->where("[".$join[1]."] = %s", $entity->{$join[0]});	
						$joinIdFields[$join[1]] = $entity->{$join[0]};
					}
					$query2->execute();
					
					$ch = $entity->data->getChangedData();
					$dataToSave = (array) $ch[$curr];
					if(count($dataToSave) > 0) {
						// Pokud se jedna o neassociativni pole, musim zjistit nazvy sloupcu
						if(!is_array(reset($dataToSave))) {
							$columnNames = $this->db->query("SHOW COLUMNS FROM [".$entity->metadata->getFieldTableName($curr)."]")->fetchAll();
							
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
							$this->db->insert($entity->metadata->getFieldTableName($curr), $iData)->execute();
						}
					}
				}
			}
			
			
			// Zavolani eventu
			if($entity instanceof ActiveEntity) {
				if($action == 'create')	$entity->onCreate($this);
				elseif($action == 'update') $entity->onUpdate($this);
				$entity->onPostSave($entity);
			}
			
			// Commitnuti dat
			$this->db->commit();
			$entity->data->performSaveMerge();
		} catch(\Exception $e) {
			$this->db->rollback();
			throw $e;
		}
	}
	
	public function isEmptyIdFieldAllowed() {
		return false;
	}

}
