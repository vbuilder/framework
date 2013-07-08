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
	Nette,
	DibiFluent;

/**
 * Data model for dibi fluent interface
 *
 * @author Adam Staněk (velbloud)
 * @since Sep 9, 2012
 */
class DibiModel extends BaseModel {

	/** @var DibiFluent */
	protected $_fluent;

	/** @var callable */
	protected $_applySortingCallback;

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
	 * @warn This function should not be called repeatedly
	 * 
	 * @return int
	 */
	public function getCount() {
		// Calling getQueryString() will freeze the object
		return (int) $this->_fluent->connection->query("SELECT COUNT(*) FROM (%sql) a", $this->getQueryString())->fetchSingle();
	}
	
	/**
	 * Returns iterator for given boundary
	 *
	 * @warn This function should not be called repeatedly
	 * 
	 * @return ArrayIterator
	 */
	public function getIterator($start, $count, array $sortingColumns = array()) {
		$this->freeze();

		$fluent = $this->_fluent;
		if(count($sortingColumns) > 0) {
			$fluent = clone $fluent;
			foreach($sortingColumns as $column => $direction)
				$this->applySortingRule($fluent, $column, $direction);
		}
		
		return new \ArrayIterator($fluent->fetchAll($start, $count));
	}

	/**
	 * Sets callback for applying sorting rules.
	 *
	 * Callback has to return TRUE if rule has been applied to avoid aplying of default rule.
	 *
	 * Example:
	 * 
	 * $model->setApplySortingCallback(function ($fluent, $columnName, $direction) {
	 *		if(in_array($columnName, array('energie', 'vlaknina'))) {
	 *			if($direction == 'desc')
	 *				$fluent->orderBy('IFNULL(%n, -1) DESC', $columnName);
	 *			else
	 *				$fluent->orderBy('IFNULL(%n, 99999999)', $columnName);
     *
	 *			return TRUE;
	 *		}
     *
	 *		return FALSE;
	 *	});
	 * 
	 * @param Callable
	 * @return DibiModel fluent interface
	 */
	public function setApplySortingCallback($cb) {
		if(!is_callable($cb))
			throw new Nette\InvalidArgumentException(__CLASS__ . "::setApplySortingCallback() expects callable as an argument but " . var_export($cb, true) . " given");

		$this->_applySortingCallback = $cb;

		return $this;
	}

	/**
	 * Applies sorting rule (default or by callback)
	 * 
	 * @param  DibiFluent $fluent
	 * @param  string     $columnName 
	 * @param  string     $direction
	 * @return DibiModel fluent interface
	 */
	protected function applySortingRule(DibiFluent &$fluent, $columnName, $direction = 'asc') {
		$cb = $this->_applySortingCallback;

		if($cb === NULL || !$cb($fluent, $columnName, $direction))
			$fluent->orderBy('%n' . ($direction == 'desc' ? ' DESC' : ''), $columnName);

		return $this;
	}

}
