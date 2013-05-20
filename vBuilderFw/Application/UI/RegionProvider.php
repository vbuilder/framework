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

namespace vBuilder\Application\UI;

use vBuilder, Nette;


/**
 * UI Region provider
 *
 * @author Adam Staněk (velbloud)
 * @since May 20, 2013
 */
class RegionProvider extends Nette\Object {

	/** @var Nette\DI\IContainer DI context container */
	protected $context;

	private $_activeRegion;

	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;
	}

	public function getActiveRegion() {
		if($this->_activeRegion === NULL)
			return $this->switchToNewRegion();

		return $this->_activeRegion;
	}

	public function switchToNewRegion(array $regionData = array()) {
		$this->_activeRegion = new Region($regionData, $this->_activeRegion);
		return $this->_activeRegion;
	}

	public function switchToParent() {
		$this->_activeRegion = $this->activeRegion->parent;

		if($this->_activeRegion === NULL)
			throw new Nette\InvalidStateException("No parent region");
	}

}

/**
 * Region representation
 */
class Region { 

	protected $_parent;
	protected $_data = array();

	public function __construct(array $regionData = array(), Region $parent = NULL) {
		$this->_parent = $parent;
		$this->_data = $regionData;
	}

	/**
	 * Magic function for getting data by object access.
	 *
	 * @param string variable name
	 * @return mixed data
	 */
	public function & __get($name) {
		$v = NULL;

		if($name == 'parent')
			$v = $this->_parent;
		elseif(isset($this->_data[$name]))
			$v = $this->_data[$name];
		elseif($this->_parent !== NULL)
			$v = $this->_parent->{$name};
					
		return $v;
	}

	/**
	 * Magic function for setting data by object access.
	 *
	 * @param  string  property name
	 * @param  mixed   property value
	 * @return void
	 */
	final public function __set($name, $value) {
		$this->_data[$name] = $value;
	}

}
