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

namespace vBuilder\Application\UI\Controls\DataTable;

use vBuilder,
	Nette;

/**
 * Base component for DataTable
 *
 * @author Adam Staněk (velbloud)
 * @since Jul 9, 2013
 */
class Component extends Nette\Object {

	private $_name;
	private $_label;
	private $_visible = true;

	protected $_table;
	protected $_renderer;

	function __construct($name, $label = NULL) {
		$this->_name = $name;
		$this->_label = $label ?: $name;
	}

	public function setTable(vBuilder\Application\UI\Controls\DataTable $parentTable) {
		$this->_table = $parentTable;
	}
	
	public function getName() {
		return $this->_name;
	}
	
	public function getLabel() {
		return $this->_label;
	}

	public function setLabel($label) {
		$this->_label = $label;
		return $this;
	}

	public function isVisible($rowData = NULL) {
		if(is_callable($this->_visible) && $rowData !== NULL) {
			$foo = $this->_visible;
			return $foo($rowData);
		}

		return $this->_visible;
	}

	/**
	 * Sets if component should be visible
	 *
	 * @param bool|callable
	 */
	public function setVisible($enabled) {
		if(is_callable($enabled))
			$this->_visible = $enabled;
		else
			$this->_visible = (bool) $enabled;
		
		return $this;
	}

	public function setRenderer($callback) {
		if(!is_callable($callback))
			throw new Nette\InvalidArgumentException("Given renderer is not callable");

		$this->_renderer = $callback;
		return $this;
	}

}
