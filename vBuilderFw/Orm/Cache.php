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
 *
 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Orm;

use vBuilder,
	Nette,
	Nette\Utils\Strings;

/**
 * Caching model for ORM entity class
 *
 * Cache is automatically updated on entity data change.
 * If cache does not exist at all findAll read is performed on first use.
 *
 * Usage:
 *	$repository->cache('MyEntity')->getTitle(123, 'cs');
 * 		or $repository->cache('MyEntity')->get('title', 123, 'cs');	
 *	(throws exception if entity is not cached)
 *
 *	$repository->cache('MyEntity')->setTitle(123, 'cs', 'new title');
 * 		or $repository->cache('MyEntity')->set('title', 123, 'cs', 'new title');
 *
 *	$repository->cache('MyEntity')->hasTitle(123, 'cs');
 * 		or $repository->cache('MyEntity')->has('title', 123, 'cs');		
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 9, 2013
 */
class Cache extends Nette\Object {
	
	/* @var IRepository repository */
	protected $repository;
	
	/* @var string entity class name */
	protected $eClass;
	
	/* @var IEntityMetadata entity metadata */
	protected $eMetadata;
	
	/* @var Nette\Caching\Cache */
	protected $cache;
	
	/* @var array|null data */
	protected $data;
	
	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI
	 */
	public function __construct(IRepository $repository, $entityClass, Nette\Caching\IStorage $cacheStorage) {
		$this->repository = $repository;
		
		if(!class_exists($entityClass))
			throw new Nette\InvalidArgumentException("Class '$entityClass' does not exist");
		
		$this->eClass = $entityClass;
		$this->eMetadata = $entityClass::getMetadata();
		
		if(count($this->eMetadata->getIdFields()) == 0)
			throw new Nette\InvalidStateException("Entity '$entityClass' needs to have at least one key to be used in ORM cache");
		
		
		$cacheKey = "vBuilder.Orm.Cache." . $this->eMetadata->getTableName();
		$this->cache = new Nette\Caching\Cache($cacheStorage, $cacheKey);
	}
	
	/**
	 * Magic function for getters / setters
	 *
	 * @param string variable name
	 * @return mixed data
	 *
	 * @throws Nette\MemberAccessException if field doesn't exists or name is empty
	 * @throws Nette\InvalidStateException if field with given key is not cached (and getter is called)
	 */
	public function __call($name, $args) {
		$setMode = Nette\Utils\Strings::startsWith($name, "set");
		$getMode = Nette\Utils\Strings::startsWith($name, "get");
		$hasMode = Nette\Utils\Strings::startsWith($name, "has");
	
		if(($setMode || $getMode || $hasMode) && count($args) > 0) {
			
			if(mb_strlen($name) > 3) {
				// Musim data bacha na MB kodovani
				$fieldName = \mb_substr($name, 3);
				$fieldName = \mb_strtolower(\mb_substr($fieldName, 0, 1), 'UTF-8') . \mb_substr($fieldName, 1);
			} else {
				$fieldName = array_shift($args);
			}

			// Pokud jde o definovane pole
			if($this->eMetadata->hasField($fieldName)) {

				if(!isset($this->data)) $this->loadData();
				
				$ptr = &$this->data->table;
				foreach($this->eMetadata->getIdFields() as $idField) {
					if(count($args) == 0)
						throw new Nette\InvalidArgumentException("Missing primary key");
						
					$key = array_shift($args);
						
					if(!isset($ptr[$key])) {
						if($setMode) $ptr[$key] = array();
						elseif($hasMode) return false;
						else throw new Nette\InvalidStateException("Key '$key' not cached for '{$this->eClass}'");
					}
					
					$ptr = &$ptr[$key];
				}
				
				if($getMode) return $ptr->{$fieldName};
				elseif($hasMode) return true;
				else {
					if(is_array($ptr)) $ptr = new \StdClass;
				
					$ptr->{$fieldName} = count($args) ? array_shift($args) : NULL;
					$this->cache->save('data', $this->data);
					return ;
				}
			}	
		}
		
		parent::__call($name, $args);
	}
	
	/**
	 * Loads data from cache / repository
	 */
	protected function loadData() {
	
		$repository = $this->repository;
		$eClass = $this->eClass;
		$eMetadata = $this->eMetadata;

		$cached = $this->cache->load('data');
		if(!isset($cached) || !isset($cached->keys) || $cached->keys != $eMetadata->getIdFields()) {

			 $this->cache->save('data', function () use ($eClass, $eMetadata, $repository) {
		
				$keys = $eMetadata->getIdFields();
				$data = new \StdClass;
				$data->keys = $keys;
				$data->ctime = date('Y-m-d H:i:s');
				$data->table = array();
				
				$lastKey = array_pop($keys);
						
				$records = $repository->findAll($eClass);
				foreach($records as $record) {
					$r = new \StdClass;
					
					foreach($eMetadata->getFields() as $fieldName) {
						if($eMetadata->hasFieldProperty($fieldName, 'cached'))
							$r->{$fieldName} = $record->{$fieldName};
					}
					
					$ptr = &$data->table;
					foreach($keys as $idField) {
						$key = $record->{$idField};
					
						if(!isset($ptr[$key])) $ptr[$key] = array();  
						$ptr = &$ptr[$key];
					}
					
					$ptr[$record->{$lastKey}] = $r;
				}
						
				return $data;
			});
			
			$this->data = $this->cache->load('data');
		} else
			$this->data = $cached;
			
		
		// dd($this->data);
	}
	
}