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

namespace vBuilder\Config;

use Nette;

/**
 * Configuration scope
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 17, 2011
 */
abstract class ConfigScope extends ConfigDAO {

	protected $data = array();
	protected $isLoaded = false;
	protected $hasChanged = false;

	protected $fallback = null;

	/**
	 * Load function. It needs to be overloaded when subclassing.
	 * See $isLoaded and $data properties.
	 */
	abstract protected function load();

	/**
	 * Save function. It needs to be overloaded when subclassing.
	 * See $hasChanged and $data properties.
	 */
	abstract public function save();

	/**
	 * Constructor
	 */
	function __construct($fallback = null) {
		$this->fallback = $fallback;

		parent::__construct($this, null, null, $this->data);
	}

	/**
	 * Returns reference to fallback scope. If there isn't any, null is returned.
	 *
	 * @return ConfigScope|null
	 */
	final public function getFallbackScope() {
		return $this->fallback;
	}

	/**
	 * Overloaded getter for load triggering
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function & get($key, $default = null) {
		if(!$this->isLoaded) $this->load();
		return parent::get($key, $default);
	}

	/**
	 * Returns array of all keys
	 *
	 * @return array of keys
	 */
	public function getKeys() {
		if(!$this->isLoaded) $this->load();
		return parent::getKeys();
	}

	/**
	 * Overloaded setter for load triggering.
	 * Data have to be loaded be loaded before setting any variables
	 * or it should be overwritten later.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function set($key, $value) {
		//if(!$this->isLoaded) $this->load();
		return parent::set($key, $value);
	}

	/**
	 * Overloaded key unsetter for load triggering.
	 * Data has to be loaded, or it won't unset any key during the key non-existence.
	 *
	 * @param string $key
	 */
	public function remove($key) {
		if(!$this->isLoaded) $this->load();
		return parent::remove($key);
	}

}
