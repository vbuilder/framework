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
	
	/** @var name of ID column (it should be NOT auto-increment integer
	 *		- incrementation is taken care of internally) */
	private $idCol;
	
	/** @var name of revision column */
	private $revisionCol;
	
	/**
	 * Register behavior to entity
	 * 
	 * @param ActiveEntity entity reference
	 */
	public function __construct(Entity &$entity, array $args = array()) {
		$this->idCol = isset($args['idCol']) ? $args['idCol'] : 'id';
		$this->revisionCol = isset($args['revisionCol']) ? $args['revisionCol'] : 'revision';
		$this->entity = &$entity;
		
		$entity->onPreSave[] = \callback($this, 'preSave');
		$entity->onPostSave[] = \callback($this, 'postSave');
	}
	
	function preSave() {
		// Cele je to uzavrene v transakci diky internimu zpracovani save,
		// takze se nemusim starat o reseni problemu, protoze v pripade jakekoliv
		// vyjimky se provede rollback
		
		$table = $this->entity->getMetadata()->getTableName();
		dibi::query("LOCK TABLES [" . $table . "] WRITE");
		
		// Pokud se zadna data nezmenila, nema to smysl
		if(!$this->entity->hasChanged()) return ;
		
		// Zjistim cislo posledni revize
		$revision = isset($this->entity->{$this->idCol}) ? dibi::query("SELECT [". $this->revisionCol ."] FROM [". $table
				  ."] WHERE [". $this->revisionCol ."] > 0"
				  ." AND [". $this->idCol ."] = %i", $this->entity->{$this->idCol})
				  ->setType($this->revisionCol, dibi::INTEGER)->fetchSingle() : false;
				  
		// Pokud neexistuje vubec zadna revize tyhle instance, vytvorim auto-id
		if($revision === false) {
			$id = dibi::query("SELECT COALESCE(MAX([". $this->idCol ."]), 0) + 1  FROM [". $table ."]")
					  ->setType($this->revisionCol, dibi::INTEGER)->fetchSingle();
			
			$this->entity->{$this->idCol} = $id;
			$this->entity->{$this->revisionCol} = 1;
			
		// Pokud existuje nejaka verze, prevratim jeji ID
		} else {
			$revision = dibi::query("UPDATE [". $table . "]"
				  ." SET [". $this->revisionCol ."] = 0 - [". $this->revisionCol ."]"
				  ." WHERE [". $this->revisionCol ."] > 0"
				  ." AND [". $this->idCol ."] = %i", $this->entity->{$this->idCol}
			);
			
			$this->entity->{$this->revisionCol} = $revision + 1;
		}
		
	}
	
	function postSave() {
		dibi::query("UNLOCK TABLES");
	}
	
}
