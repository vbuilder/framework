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

namespace vBuilder\Orm\DataTypes;

use vBuilder,
	 Nette;

/**
 * Datatype for saving/loading serialized arrays into DB
 * 
 * TODO: SET!
 *
 * @author Adam Staněk (V3lbloud)
 * @since Oct 7, 2011
 */
class Json extends vBuilder\Object implements vBuilder\Orm\IDataType, \IteratorAggregate {

	protected $entity;
	protected $fieldName;
	
	protected $array;
	
	public function __construct($fieldName, &$entity, Nette\DI\IContainer $context) {
		$this->entity = $entity;
		$this->fieldName = $fieldName;
	}
	
	public function convertFrom(&$data) {
		if(is_array($data)) {
			$this->entity->data->{$this->fieldName} = json_encode($data);
			$this->array = $data;
			return ;
		}
		
		throw new Nette\InvalidArgumentException("'".  gettype($data)."' is not supported by " . get_called_class());
	}
	
	public function toArray() {
		if(!isset($this->array)) {
			
			if($this->entity->data->{$this->fieldName} == '') $this->array = array();
			else $this->array = json_decode($this->entity->data->{$this->fieldName}, true);
		}
		
		return $this->array;
	}
	
	public function getIterator() {
		return new \ArrayIterator($this->toArray());
	}
	
	public static function acceptedDataTypes() {
		return array("Json");
	}

	public function  __toString() {
		return var_export($this->toArray(), true);
	}

}
