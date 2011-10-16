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
 * Dummy fluent interface for arrays
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 27, 2011
 */
class ArrayFluent implements \IteratorAggregate {

	/** @var array of data */
	protected $data;
	
	/** @var Nette\DI\IContainer DI */
	protected $context;
	
	private $whereConditions = array();
	
	private $iterator;

	/**
	 * Constructs fluent object
	 * 
	 * @param array of entities (or entity data)
	 * 
	 * @param Nette\DI\IContainer DI
	 */
	public function __construct($array, Nette\DI\IContainer $context) {
		$this->data = $array;
		
		$this->context = $context;
	}

	/**
	 * @return ArrayFluent
	 */
	public function where($expression, $value) {
		if(preg_match('/^\\[([^\\]]+)\\] = %[si]$/', $expression, $matches) < 1) 
				throw new Nette\NotSupportedException("Only simple '[column] = %s' where expressions supported");
		
		$this->iterator = null;
		$this->whereConditions[] = array($matches[1], $value);
		
		return $this;
	}

	/*
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		if(!$this->iterator) {
			$this->iterator = new \ArrayIterator($this->data);

			if(count($this->whereConditions)) {
				$conditions = $this->whereConditions;
				$this->iterator = new Nette\Iterators\Filter($this->iterator, function ($it) use ($conditions) {
					$entity = $it->getInnerIterator()->current();
					
					foreach($conditions as $cond) {
						if($entity instanceof Entity) {
							if($entity->{$cond[0]} != $cond[1]) return false;
						}
					}
					
					return true;
				});
			}
		}
		
		return $this->iterator;
	}
	
	/**
	 * @return Array 
	 */
	public function fetchAll() {
		$result = array();
		foreach($this as $curr) $result[] = $curr;
		return $result;
	}

}