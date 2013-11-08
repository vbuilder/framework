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
	
	/** @var array of event listeners - function (Entity, EntityCollection) */
	public $onItemAdded = array();

	/** @var array of event listeners - function (Entity, EntityCollection) */
	public $onItemRemoved = array();
	
	/** @var string name of target entity */
	protected $targetEntity;
	
	function __construct(Entity &$parent, $fieldName, $targetEntityName, Nette\DI\IContainer $context) {
		$this->targetEntity = $targetEntityName;
		parent::__construct($parent, $fieldName, $context);
	}
	
	public function load() {
		// Jinak by se data stale pricitala
		if($this->loaded) return ;
		
		$this->loaded = true;
		if(!$this->data) $this->data = array();
		
		$parentMetadata = $this->parent->getMetadata();
		
		$targetClass = $this->targetEntity;
		$targetMetadata = $targetClass::getMetadata();
		
		$processSubclasses = $parentMetadata->getFieldProperty($this->field, 'processSubclasses', false);
		$ds = $this->context->repository->findAll($this->targetEntity, $processSubclasses);

		// Podminky spojeni a separace joinKeys
		$joinKeys = array();
		foreach($parentMetadata->getFieldJoinPairs($this->field) as $join)
			$ds->where("[".$targetMetadata->getFieldColumn($join[1])."] = %s", $this->parent->{$join[0]});
			
		// Nactu data (a musim zachovat soucasna)
		foreach($ds->fetchAll() as $curr) {
			$this->data[] = $curr;
			$this->connect($curr);
		}
	}
	
	public function save(IRepository $repository = null) {	
		if(!$this->mightNeedSave()) return ;
	
		if($repository === null) $repository = $this->parent->repository;
		$repository->save($this);
	}
	
	public function add($relatedEntity) {
		if(!($relatedEntity instanceOf $this->targetEntity))
			  throw new \InvalidArgumentException("Added entity has to be instance of '$this->targetEntity'");
		
		foreach((array) $this->data as $curr) 
			if($curr === $relatedEntity)
				throw new \InvalidArgumentException('This entity is already contained in collection');
			
		$this->data[] = $relatedEntity;
		
		$this->onItemAdded($relatedEntity, $this);
	}
	
	public function remove($entity) {
		foreach($this->data as $k=>$curr) {
			if($entity === $curr) {
				unset($this->data[$k]);
				$this->onItemRemoved($entity, $this);
				
				break;
			}
		}
	}
	
	protected function connect(Entity $entity) {
		if($entity instanceof ActiveEntity) {
			$entity->onPostDelete[] = array($this, 'remove');
		}
	}
	
}