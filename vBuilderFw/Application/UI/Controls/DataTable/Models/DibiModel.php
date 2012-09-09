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

namespace vBuilder\Application\UI\Controls\DataTable;

use vBuilder,
	Nette;

/**
 * Data model for dibi fluent interface
 *
 * @author Adam Staněk (velbloud)
 * @since Sep 9, 2012
 */
class DibiModel extends BaseModel {

	/** @var DibiFluent */
	protected $_fluent;

	function __construct(\DibiFluent $fluent) {
		$this->_fluent = clone $fluent;
	}
	
	protected function getQueryString() {
		$this->freeze();
		return (string) $this->_fluent;
	}
	
	/**
	 * Returns total data count
	 * 
	 * @return int
	 */
	public function getCount() {
		return (int) $this->_fluent->connection->query("SELECT COUNT(*) FROM (%sql) a", $this->getQueryString())->fetchSingle();
	}
	
	/**
	 * Returns iterator for given boundary
	 *
	 * @return ArrayIterator
	 */
	public function getIterator($start, $count) {
		// return $this->_fluent->getIterator($start, $count);
		
		return new \ArrayIterator($this->_fluent->fetchAll($start, $count));
	}

}