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

namespace vBuilder\Config;

use Nette;

/**
 * Config data access object
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 17, 2011
 */
class ConfigDAO implements \ArrayAccess {

	/**
	 * @var ConfigScope reference to scope object
	 */
	private $scope;
	
	/**
	 * @var ConfigScope|null reference to fallback scope
	 * (got from $scope->getFallbackScope())
	 */
	private $fallbackScope;
	
	/**
	 * @var ConfigDAO|null reference to parent dao 
	 */
	private $parent;
	
	/**
	 * @var string|null absolute key for this DAO (null if root DAO - scope)
	 */
	private $key;
	
	/** 
	 * @var array of data
	 * (actually pointer to position in data array in the scope)
	 */
	private $data;
	
	/**
	 * @var array of cached DAO objects (associative by query key)
	 */
	private $dao = array();
	
	/**
	 * @var bool status of last ::get call, if true, everithing went ok, if false
	 * key was not found. This variable is used for internal purpose of ::has method.
	 */
	private $lastFound;
	
	/**
	 * Constructor
	 * 
	 * @param ConfigScope effective configuration scope
	 * @param ConfigDAO parent object
	 * @param string absolute key
	 * @param array reference to data
	 */
	protected function __construct(ConfigScope $scope, $parent, $key, &$dataPtr) {		
		$this->data = &$dataPtr;
		$this->scope = $scope;
		$this->fallbackScope = $scope->getFallbackScope();
		$this->parent = $parent;
		$this->key = $key;
	}

	/**
	 * Deletes all cached DAO objects and purge data.
	 * This function is neccessary to call after each reload.
	 * 
	 * It is not meant to be called recursively, call it on scope only!
	 * 
	 * @internal
	 * 
	 * @param array reference to new data array
	 */
	protected function reset(&$newDataPtr) {
		$this->dao = array();
		$this->data = &$dataPtr;
	}
	
	/**
	 * Returns value for key. Unlike direct object access / array access
	 * it doesn't throw exception if key is not found, but it returns
	 * default value instead.
	 * 
	 * Key can be written in nested format: key1.key2.foo
	 * 
	 * @param string key
	 * @param mixed default value
	 * 
	 * @return mixed 
	 */
	public function & get($key, $default = null) {
		if(!is_scalar($key))
			throw new \InvalidArgumentException("Key must be either a string or an integer.");
		
		if(empty($key) && $key !== 0) 
			throw new \InvalidArgumentException("Key can't be empty");
		
		$this->lastFound = true;
		$prefixTokens = $this->key ? explode('.', $this->key) : array();
		$tokens = array_merge($prefixTokens, explode('.', $key));
		$ptr = $this;
		
		for($i = count($prefixTokens); $i < count($tokens); $i++) {
			$key = $tokens[$i];
			
			// Pokud uz mam pripraveny DAO
			if(isset($ptr->dao[$key])) {
				$ptr = $ptr->dao[$key];
			}
			
			// Pokud klic je k nalezeni v nasi datech
			elseif(array_key_exists($key, $ptr->data)) {
				// Vytvorim pro nej DAO objekt, pokud je to pole, jinak rovnou vratim data
				if(is_array($ptr->data[$key])) {						
					$ptr = $ptr->dao[$key] = new self(
							  $this->scope, $ptr,
							  $i > 0 ? implode(array_intersect_key($tokens, range(0, $i - 1)), '.') . ".$key" : $key,
							  $ptr->data[$key]
					);
					
				} else
					return $ptr->data[$key];
			}
				
			// Pokud klic neni v nasich datech, musim se zkusit mrknout jinam
			else {				
				$scope = $ptr->fallbackScope;
				while($scope) {
					if(!$scope->isLoaded) $scope->load();		
					
					$ptr2 = &$scope->data;
					$notFound = false;
					for($p = 0; $p < $i; $p++) {
						if(array_key_exists($tokens[$p], $ptr2)) {
							$ptr2 = &$ptr2[$tokens[$p]];
						} else {
							$notFound = true;
							break;
						}
					}
					
					if(!$notFound) {
						if(array_key_exists($key, $ptr2)) {
							if(is_array($ptr2[$key])) {
								$ptr = $ptr->dao[$key] = new self(
									$this->scope, $ptr,
									$i > 0 ? implode(array_intersect_key($tokens, range(0, $i - 1)), '.') . ".$key" : $key,
									$ptr2[$key]
								);
							
								$ptr->fallbackScope = $scope->getFallbackScope();
							} else {
								return $ptr2[$key];
							}
							
							break;
						}
					}
					
					$scope = $scope->getFallbackScope();
				}
				
				if(!$scope) break;
			}
			
			// Pokud jsem nasel klic a uz je to posledni index, vratim hodnotu
			if($i == count($tokens) - 1)
				return $ptr;
		}
		
		$this->lastFound = false;
		return $default;
	}
	
	
	/**
	 * Sets value for configuration directive
	 * 
	 * @param string key in nested format
	 * @param mixed default value
	 * 
	 * @return mixed 
	 */
	public function set($key, $value) {
		if(!is_scalar($key))
			throw new \InvalidArgumentException("Key must be either a string or an integer.");
		
		if(empty($key))
			throw new \InvalidArgumentException("Key can't be empty");
		
		// Musim jit od zacatku, protoze moje data ve skutecnosti nemusi patrit do
		// tohohle scope
		$ptr = &$this->scope->data;
		$ptrDao = $this->scope;
		$prefixTokens = $this->key ? explode('.', $this->key) : array();
		$tokens = array_merge($prefixTokens, explode('.', $key));
		
		for($i = 0; $i < count($tokens) - 1; $i++) {
			$ptr = &$ptr[$tokens[$i]];
			if($ptrDao && isset($ptrDao->dao[$tokens[$i]])) $ptrDao = $ptrDao->dao[$tokens[$i]];
			else $ptrDao = null;
		}
		
		$ptr[end($tokens)] = $value;
		if($ptrDao && $ptrDao->data !== $ptr) $ptrDao->data = &$ptr;
		$this->scope->hasChanged = true;
	}
	
	/**
	 * Checks if key exists
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		$this->get($key, null);
		return $this->lastFound;
	}
	
	/**
	 * Removes key from **this** scope. Be aware that this function does not remove
	 * key completly. It can be still inheriet from another scope.
	 * 
	 * @param string $key
	 */
	public function remove($key) {
		if(!is_scalar($key))
			throw new \InvalidArgumentException("Key must be either a string or an integer.");
		
		if(empty($key))
			throw new \InvalidArgumentException("Key can't be empty");
		
		// Musim jit od zacatku, protoze moje data ve skutecnosti nemusi patrit do
		// tohohle scope
		$ptr = &$this->scope->data;
		$ptrDao = $this->scope;
		$prefixTokens = $this->key ? explode('.', $this->key) : array();
		$tokens = array_merge($prefixTokens, explode('.', $key));
		
		for($i = 0; $i < count($tokens) - 1; $i++) {
			if(!array_key_exists($tokens[$i], $ptr)) return ;
			$ptr = &$ptr[$tokens[$i]];
			if($ptrDao && isset($ptrDao->dao[$tokens[$i]])) $ptrDao = $ptrDao->dao[$tokens[$i]];
		}
		
		unset($ptr[end($tokens)]);
		
		if(count($ptr) == 0 && count($tokens) > 1) {
			if($ptrDao && $ptrDao->parent) {
				unset($ptrDao->parent->dao[end($tokens)]);
			}
			
			$this->scope->remove(implode(array_intersect_key($tokens, range(0, count($tokens) - 2)), '.'));
		}
		
		$this->scope->hasChanged = true;
	}
	
	/**
	 * Returns array of all keys
	 * 
	 * @return array of keys
	 */
	public function getKeys() {
		$keys = array_keys($this->data);
		if($this->fallbackScope) {
			$node = $this->key ? $this->fallbackScope->get($this->key) : $this->fallbackScope;
			return array_unique(array_merge($keys, $node->getKeys()));
		}
		
		return $keys;
	}
	
	/**
	 * Returns array representation of object
	 * 
	 * @return array
	 */
	public function toArray() {
		$keys = $this->getKeys();
		$items = array();
		
		foreach($keys as $key) {
			$value = $this->get($key);
			
			if($value instanceof self)
				$items[$key] = $value->toArray();
			else
				$items[$key] = $value;
		}
		
		return $items;
	}
	
	// ==========================================================================
	
	// <editor-fold defaultstate="collapsed" desc="Object access">
	
	public function &__get($key) {
		$this->checkKey($key);

		$val = $this->get($key);

		// Non-secure getter, it will produce notice, if index does not exist
		// It is meant to behave same as regular array access
		if(!$this->lastFound)
			trigger_error("Trying to read unset property '$key'", E_USER_NOTICE);
		
		return $val;
	}

	public function __set($key, $value) {
		$this->checkKey($key);
		
		
		$this->set($key, $this->parseBools($value));
	}
	
	/**
	 * Returns true, if $key exists and is not NULL
	 * 
	 * @param string key
	 * @return bool 
	 */
	public function __isset($key) {
		return $this->get($key, null) !== null;
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Array access">
	
	/**
	 * Implementation of ArrayAccess::offsetGet
	 * 
	 * @param string key
	 * 
	 * @return mixed
	 * @throws \InvalidArgumentException if key is not scalar or contains dot
	 */
	public function offsetGet($key) {
		return $this->__get($key);
	}

	/**
	 * Implementation of \ArrayAccess::offsetExists
	 * Unlike isset it honors NULL values
	 * 
	 * @param string key
	 * 
	 * @return bool
	 */
	public function offsetExists($key) {
		return array_key_exists($key, (array) $this->data);
	}

	/**
	 * Implementation of \ArrayAccess::offsetSet
	 * 
	 * @param string key
	 * @param mixed value 
	 * 
	 * @return void
	 * @throws \InvalidArgumentException if key is not scalar or contains dot
	 */
	public function offsetSet($key, $value) {
		$this->__set($key, $value);
	}
	
	/**
	 * Implementation of \ArrayAccess::offsetUnset
	 * 
	 * @param string key
	 * 
	 * @return void
	 * @throws \InvalidArgumentException if key is not scalar or contains dot
	 */
	public function offsetUnset($key) {
		$this->checkKey($key);

		$this->remove($key);
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Helpers">
	
	/**
	 * Parse boolean value from string
	 * 
	 * @param mixed value to parse
	 * @return bool
	 */
	protected function parseBools($value) {
		$consts = array(
			'true' => TRUE, 'True' => TRUE, 'TRUE' => TRUE, 'yes' => TRUE, 'Yes' => TRUE, 'YES' => TRUE, 'on' => TRUE, 'On' => TRUE, 'ON' => TRUE,
			'false' => FALSE, 'False' => FALSE, 'FALSE' => FALSE, 'no' => FALSE, 'No' => FALSE, 'NO' => FALSE, 'off' => FALSE, 'Off' => FALSE, 'OFF' => FALSE, 
		);
		if (isset($consts[$value])) {
			$value = $consts[$value];
		}
		return $value;
	}
	
	/**
	 * Function helper for key sane checking
	 * 
	 * @param string key 
	 * @throws \InvalidArgumentException
	 */
	private function checkKey($key) {
		if(!is_scalar($key))
			throw new \InvalidArgumentException("Key must be either a string or an integer.");

		if(strpos($key, '.') !== false)
			throw new \InvalidArgumentException("Key must not contain any dots");
	}
	
	// </editor-fold>
	
}
