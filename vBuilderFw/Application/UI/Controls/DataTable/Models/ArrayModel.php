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
 * Basic array based data model
 *
 * @author Adam Staněk (velbloud)
 * @since Sep 9, 2012
 */
class ArrayModel extends BaseModel {

	/** @var array */
	protected $_data;

	function __construct(array $data) {
		$this->_data = $data;
	}
	
	public function setFilter(array $rules = array()) {
		// TODO

		return $this;
	}

	public function getFilter() {
		// TODO

		return NULL;
	}

	protected function getData() {
		$this->freeze();
		return $this->_data;
	}
	
	/**
	 * Returns total data count
	 * 
	 * @return int
	 */
	public function getCount() {
		return count($this->getData());
	}
	
	/**
	 * Returns iterator for given boundary
	 *
	 * @return ArrayIterator
	 */
	public function getIterator($start, $count, array $sortingColumns = array()) {

		// TODO: Support for sorting and filtering or at least throw a notice

		return new \ArrayIterator(array_slice($this->getData(), $start, $count));
	}

}
