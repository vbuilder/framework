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
 * Column representation for DataTable
 *
 * @author Adam Staněk (velbloud)
 * @since Sep 9, 2012
 */
class DataTableColumn extends Nette\Object {

	private $_name;
	private $_caption;
	private $_renderer;
	private $_sortable = true;
	private $_visible = true;

	function __construct($name, $caption = NULL) {
		$this->_name = $name;
		$this->_caption = $caption;
	}
	
	function getName() {
		return $this->_name;
	}
	
	function getCaption() {
		return $this->_caption ?: $this->_name;
	}

	function isSortable() {
		return $this->_sortable;
	}

	function setSortable($enabled) {
		$this->_sortable = (bool) $enabled;
		return $this;
	}

	function isVisible() {
		return $this->_visible;
	}

	function setVisible($enabled) {
		$this->_visible = (bool) $enabled;
		return $this;
	}

	function render($value, $rowData) {
		if($this->_renderer) {
			$r = $this->_renderer;
			return $r($value, $rowData);
		}

		return $value;
	}

	function setRenderer($callback) {
		if(!is_callable($callback))
			throw new Nette\InvalidArgumentException("Given renderer is not callable");

		$this->_renderer = $callback;
		return $this;
	}

}
