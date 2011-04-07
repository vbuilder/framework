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
 * Class which merges metadata from more objects into one
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 3, 2011
 */
class MergedMetadata implements IEntityMetadata {
	
	private $metadata = array();
	
	public function __construct(IEntityMetadata $metadata1, IEntityMetadata $metadata2) {			
		foreach(func_get_args() as $curr)
			$this->metadata[] = $curr;				
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getTableName() {
		for($i = count($this->metadata) - 1; $i >= 0; $i--)
			if($this->metadata[$i]->getTableName() != "")
				return $this->metadata[$i]->getTableName(); 
	
		return null;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getBehaviors() {
		$merged = array();
		
		foreach($this->metadata as $curr)
			$merged = array_merge($merged, $curr->getBehaviors());
		
		return $merged;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getIdFields() {
		$merged = array();
		
		foreach($this->metadata as $curr)
			$merged = array_merge($merged, $curr->getIdFields());
		
		return $merged;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFields() {
		$merged = array();
		
		foreach($this->metadata as $curr)
			$merged = array_merge($merged, $curr->getFields());
		
		return $merged;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function hasField($name) {
		foreach($this->metadata as $curr)
			if($curr->hasField($name))
				return true;
				  
		return false;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldType($name) {
		for($i = count($this->metadata) - 1; $i >= 0; $i--)
			if($this->metadata[$i]->hasField($name))
				return $this->metadata[$i]->getFieldType($name);
		
		throw new \InvalidArgumentException("Field '$name' is not defined");
		return null;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldEntityName($name) {
		for($i = count($this->metadata) - 1; $i >= 0; $i--)
			if($this->metadata[$i]->hasField($name))
				return $this->metadata[$i]->getFieldEntityName($name);
		
		throw new \InvalidArgumentException("Field '$name' is not defined");
		return null;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldTableName($name) {
		for($i = count($this->metadata) - 1; $i >= 0; $i--)
			if($this->metadata[$i]->hasField($name))
				return $this->metadata[$i]->getFieldTableName($name);
		
		throw new \InvalidArgumentException("Field '$name' is not defined");
		return null;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldJoinPairs($name) {
		for($i = count($this->metadata) - 1; $i >= 0; $i--)
			if($this->metadata[$i]->hasField($name))
				return $this->metadata[$i]->getFieldJoinPairs($name);
		
		throw new \InvalidArgumentException("Field '$name' is not defined");
		return array();
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function isFieldGenerated($name) {
		for($i = count($this->metadata) - 1; $i >= 0; $i--)
			if($this->metadata[$i]->hasField($name))
				return $this->metadata[$i]->isFieldGenerated($name);
		
		throw new \InvalidArgumentException("Field '$name' is not defined");
		return null;
	}
	
}
