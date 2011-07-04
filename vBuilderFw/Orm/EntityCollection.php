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
 * Collection of entities for OneToMany relations
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 28, 2011
 */
class EntityCollection extends Collection {
	
	/** @var string name of target entity */
	protected $targetEntity;
	
	function __construct(Entity &$parent, $fieldName, $targetEntityName) {
		$this->targetEntity = $targetEntityName;
		parent::__construct($parent, $fieldName);
	}
	
	public function load() {
		$parentMetadata = $this->parent->getMetadata();
		$ds = Repository::findAll($this->targetEntity);

		// Podminky spojeni a separace joinKeys
		$joinKeys = array();
		foreach($parentMetadata->getFieldJoinPairs($this->field) as $join)
			$ds->where("[".$join[1]."] = %s", $this->parent->{$join[0]});
		
		// Nactu data
		$this->data = $ds->fetchAll();
		$this->loaded = true;
	}
	
	public function save() {		
		$joinPairs = $this->parent->getMetadata()->getFieldJoinPairs($this->field);
		
		foreach($this->data as &$member) {
			foreach($joinPairs as $join) 
				$member->{$join[1]} = $this->parent->{$join[0]};
				
			$member->save();
		}
	}
	
	public function add($relatedEntity) {
		if(!($relatedEntity instanceOf $this->targetEntity))
			  throw new \InvalidArgumentException("Added entity has to be instance of '$relatedEntity'");
		
		foreach($this->data as $curr) 
			if($curr === $relatedEntity)
				throw new \InvalidArgumentException('This entity is already contained in collection');
		
		$this->data[] = $relatedEntity;
	}
	
}