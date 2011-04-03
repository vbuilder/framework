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
 * Implementation of IEntityMetadata for annotated classes
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 17, 2011
 */
class AnnotationMetadata implements IEntityMetadata {
	
	/** @var string table name */
	private $tableName;
	
	/** @var array of ID field names */
	private $idFields = array();
	
	/** @var array of fields */
	private $fields = array();
	
	/**
	 * Parses class annotation and build metadata object
	 * 
	 * @param Nette\Reflection\ClassReflection $reflection 
	 * @throws vBuilder\InvalidAnnotationException if any annotation is missing or bad formed
	 */
	public function __construct(Nette\Reflection\ClassReflection $reflection) {			
		
		// Nazev tabulky
		$annotations = $reflection->getAnnotations();
		if(isset($annotations['Table']) && isset($annotations['Table'][0]['name'])) {
			$this->tableName = $annotations['Table'][0]['name'];
		}
		
		// Sloupecky
		if(isset($annotations['Column'])) {
			foreach($annotations['Column'] as $curr) {
				$fieldMetadata = $curr->getMetadata();
				if(!isset($fieldMetadata['name']))
					throw new vBuilder\InvalidAnnotationException('Missing name attribute in @Column(...) declaration in class ' . $reflection->getName());
				
				$this->fields[$fieldMetadata['name']] = $fieldMetadata;
				if(isset($fieldMetadata['id'])) $this->idFields[] = $fieldMetadata['name'];
			}
		}
		
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getTableName() {
		return $this->tableName;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getIdFields() {
		return $this->idFields;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFields() {
		return array_keys($this->fields);
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function hasField($name) {
		return isset($this->fields[$name]);
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldType($name) {
		if(!$this->hasField($name)) throw new \InvalidArgumentException("Field '$name' is not defined");
		
		return isset($this->fields[$name]['type']) ? $this->fields[$name]['type'] : 'string';
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldEntityName($name) {
		if(!$this->hasField($name)) throw new \InvalidArgumentException("Field '$name' is not defined");
		
		return isset($this->fields[$name]['entity']) ? $this->fields[$name]['entity'] : null;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldTableName($name) {
		if(!$this->hasField($name)) throw new \InvalidArgumentException("Field '$name' is not defined");
		
		if(isset($this->fields[$name]['entity'])) {
			$class = $this->fields[$name]['entity'];
			$metadata = $class::getMetadata();
			return $metadata->getTableName();
		} elseif(isset($this->fields[$name]['table'])) {
			return $this->fields[$name]['table'];
		}
		
		return null;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function getFieldJoinPairs($name) {
		if(!$this->hasField($name)) throw new \InvalidArgumentException("Field '$name' is not defined");
		
		$pairs = array();
		
		if(isset($this->fields[$name]["joinUsing"]))
				  $pairs[] = array($this->fields[$name]["joinUsing"], $this->fields[$name]["joinUsing"]);
		
		return $pairs;
	}
	
	/**
	 * {@inheritdoc} 
	 */
	public function isFieldGenerated($name) {
		if(!$this->hasField($name)) throw new \InvalidArgumentException("Field '$name' is not defined");
		
		return isset($this->fields[$name]['generatedValue']) ? (bool) $this->fields[$name]['generatedValue'] : false;
	}
	
}
