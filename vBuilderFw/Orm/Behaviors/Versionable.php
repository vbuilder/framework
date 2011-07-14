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

namespace vBuilder\Orm\Behaviors;

use vBuilder, vBuilder\Orm\Entity, dibi;

/**
 * Versionable behavior for ORM entities
 * 
 * It assumes you have following columns (names can be changed by options):
 *		- id INT(11) - which is with revision complex primary key
 *		- revision INT(11) - for containing revision
 * 
 * ID is automatically taken as maximum of table + 1 if not specified. It is important
 * that this field is NOT set to auto-increment, because many versions can have the same id.
 * 
 * Revision is also automatically incremented number. But only last revision is number
 * greater than 0. All other revision are negative numbers for quick select of current
 * version from DB without special index.
 * 
 * ActiveEntity only.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 7, 2011
 */
class Versionable implements vBuilder\Orm\IBehavior {
	
	/** @var reference to entity */
	private $entity;
	
	/** @var name of ID field (it should be NOT auto-increment integer
	 *		- incrementation is taken care of internally) */
	private $idField;
	
	/** @var name of ID column */
	private $idColumn;
	
	/** @var name of revision field */
	private $revisionField;
	
	/** @var name of revision column */
	private $revisionColumn;
	
	/**
	 * Register behavior to entity
	 * 
	 * @param ActiveEntity entity reference
	 */
	public function __construct(Entity &$entity, array $args = array()) {
		$this->entity = &$entity;
		
		// Zjisitm ID, Revision column a field name, pripadne hodim default
		foreach(array('id', 'revision') as $curr) {
			if(isset($args[$curr . 'Field'])) {
				
				$this->{$curr . 'Field'} = $args[$curr . 'Field'];
				$this->{$curr . 'Column'} = isset($args[$curr . 'Col']) ? $args[$curr . 'Col'] : $entity->getMetadata()->getFieldColumn($args[$curr . 'Field']);
			} else
				$this->{$curr . 'Field'} = $this->{$curr . 'Column'} = isset($args[$curr . 'Col']) ? $args[$curr . 'Col'] : $curr;
		}
		
		$entity->onPreSave[] = \callback($this, 'preSave');
		$entity->onPostSave[] = \callback($this, 'postSave');
	}
	
	/**
	 * Pre-save actions
	 * 
	 * @return void 
	 */
	function preSave() {
		// Cele je to uzavrene v transakci diky internimu zpracovani save,
		// takze se nemusim starat o reseni problemu, protoze v pripade jakekoliv
		// vyjimky se provede rollback
		
		$table = $this->entity->getMetadata()->getTableName();
		dibi::query("LOCK TABLES [" . $table . "] WRITE");
		dibi::begin();
		
		// Pokud se zadna data nezmenila, nema to smysl
		if(!$this->entity->hasChanged()) return ;
		
		// Zjistim cislo posledni revize
		$revision = isset($this->entity->{$this->idField}) ? dibi::query("SELECT [". $this->revisionColumn ."] FROM [". $table
				  ."] WHERE [". $this->revisionColumn ."] > 0"
				  ." AND [". $this->idColumn ."] = %i", $this->entity->{$this->idField})
				  ->setType($this->revisionColumn, dibi::INTEGER)->fetchSingle() : false;
				  
		// Pokud neexistuje vubec zadna revize tyhle instance, vytvorim auto-id
		if($revision === false) {
			$id = dibi::query("SELECT COALESCE(MAX([". $this->idColumn ."]), 0) + 1 AS [maxid]  FROM [". $table ."]")
					  ->setType('maxid', dibi::INTEGER)->fetchSingle();
			
			$this->entity->{$this->idField} = $id;
			$this->entity->{$this->revisionField} = 1;
			
		// Pokud existuje nejaka verze, prevratim jeji ID
		} else {
			$this->entity->{$this->revisionField} = $revision + 1;
			
			$revision = dibi::query("UPDATE [". $table . "]"
				  ." SET [". $this->revisionColumn ."] = 0 - [". $this->revisionColumn ."]"
				  ." WHERE [". $this->revisionColumn ."] > 0"
				  ." AND [". $this->idColumn ."] = %i", $this->entity->{$this->idField}
			);
		}
		
	}
	
	/**
	 * Post-save actions
	 * 
	 * @return void 
	 */
	function postSave() {
		// Nesmim tu davat commit, o commit se mi postara uz samotna active entity.
		// Kdybych tu dal commit, nedal by se udelat pripadny rollback z handleru
		// zavolanych po me (behavior se registruje jako prvni).
		dibi::query("UNLOCK TABLES");
	}
	
	/**
	 * Returns real revision number
	 * 
	 * @return int 
	 */
	function getRevision() {
		return abs($this->entity->defaultGetter($this->revisionField));
	}
	
	/**
	 * Returns true if this revision is last in DB
	 * 
	 * @return bool
	 */
	function isLastRevision() {
		return $this->entity->defaultGetter($this->revisionField) > 0;
	}
	
}
