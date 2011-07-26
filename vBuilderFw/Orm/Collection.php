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
 * Collection for OneToMany relations
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 28, 2011
 */
class Collection extends vBuilder\Object implements \ArrayAccess, \Countable, \IteratorAggregate {

	/** @var Entity reference to parent entity */
	protected $parent;
	/** @var string field name */
	protected $field;
	/** @var array of data */
	protected $data;
	/** @var bool true if data has been loaded already */
	protected $loaded = false;
	
	/** @var Nette\DI\Container */ 
	protected $container;

	/**
	 * Conctructs collection
	 * 
	 * @param Entity reference to parent entity
	 * @param string name of virtual field in parent entity
	 */
	function __construct(Entity &$parent, $fieldName, Nette\DI\Container $container) {
		$this->container = $container;
		$this->parent = &$parent;
		$this->field = $fieldName;

		$this->load();
	}

	/**
	 * Loads joined data from DB
	 */
	public function load() {
		$parentMetadata = $this->parent->getMetadata();

		if($parentMetadata->getFieldTableName($this->field) == null)
			throw new \InvalidArgumentException("Table name for field '$this->field' in entity '".get_class($this->parent)."' has to be specified");

		$ds = dibi::select("*")->from($parentMetadata->getFieldTableName($this->field));

		// Podminky spojeni a separace joinKeys
		$joinKeys = array();
		foreach($parentMetadata->getFieldJoinPairs($this->field) as $join) {
			$ds->where("[".$join[1]."] = %s", $this->parent->{$join[0]});
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

		$this->data = $d;
		$this->loaded = true;
	}

	/**
	 * Saves joined data
	 */
	public function save() {
		throw new Nette\NotImplementedException('Not implemented yet');
	}

	// General access functions =================================================

	/**
	 * Returns data as an array
	 * 
	 * @return array 
	 */
	function toArray() {
		if(!$this->loaded)
			$this->load();
		return $this->data;
	}

	/**
	 * Implementation of magic function for string representation
	 * 
	 * @return string 
	 */
	function __toString() {
		return "Collection({$this->count()})";
	}

	/**
	 * Implementation of IteratorAggregate::getIterator
	 * @return ArrayIterator 
	 */
	public function getIterator() {
		return new \ArrayIterator($this->data);
	}

	/**
	 * Implementation of ArrayAccess::offsetExists
	 * 
	 * @param string $offset
	 * 
	 * @return bool
	 */
	function offsetExists($offset) {
		if(!$this->loaded)
			$this->load();
		return isset($this->data[$offset]);
	}

	/**
	 * Implementation of ArrayAccess::offsetGet
	 * 
	 * @param string $offset
	 * 
	 * @return mixed
	 */
	function offsetGet($offset) {
		if(!$this->loaded)
			$this->load();
		return $this->data[$offset];
	}

	/**
	 * Implementation of ArrayAccess::offsetSet
	 * 
	 * @param string $offset
	 * @param mixed $value 
	 * 
	 * @return void
	 */
	function offsetSet($offset, $value) {
		$this->data[$offset] = $value;
	}

	/**
	 * Implementation of ArrayAccess::offsetUnset
	 * 
	 * @param string $offset
	 * 
	 * @return void
	 */
	function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	/**
	 * Implementation of Countable::count
	 * 
	 * @return int 
	 */
	function count() {
		if(!$this->loaded) $this->load();
		return count($this->data);
	}

}