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
 * Basic array result set implementation
 *
 * @author Adam Staněk (velbloud)
 * @since Apr 10, 2014
 */
class ArrayResultSet extends BaseResultSet {

	private $_count;
	private $_data;
	private $_iterator;

	function __construct(array &$data, array $filteringRules = array(), array $sortingColumns = array()) {
		parent::__construct($filteringRules, $sortingColumns);

		$this->_data = &$data;
		$count = &$this->_count;

		// Filtering and counting
		$this->_iterator = new Nette\Iterators\Filter(new \ArrayIterator($data), function ($rowData) use ($filteringRules, &$count){

			foreach($filteringRules as $key => $rule) {
				$keywords = $rule['regexp'] ? array($rule['keywords']) : preg_split('/\\s+/', trim($rule['keywords']));
				$columns = isset($rule['columns']) ? $rule['columns'] : array($key);

				foreach($keywords as $keyword) {
					$found = FALSE;
					foreach($columns as $col) {
						if(preg_match($rule['regexp'] ? $keyword : '/' . preg_quote($keyword, '/') . '/', $rowData[$col])) {
							$found = TRUE;
							break;
						}
					}

					if(!$found)
						return FALSE;
				}
			}

			// Count and allow
			$count++;
			return TRUE;
		});

		// Sorting
		if(count($sortingColumns)) {

			// @todo Not optimal
			// @note Values are compared as strings (ie. 12 < 2)
			$this->_iterator = new \IteratorIterator(new vBuilder\Utils\SortingIterator($this->_iterator, function ($a, $b) use ($sortingColumns) {
				foreach($sortingColumns as $key => $direction) {
					if(!isset($a[$key])) return $direction == 'asc' ? 1 : -1;
					if(!isset($b[$key])) return $direction == 'asc' ? -1 : 1;

					if($a[$key] > $b[$key]) return $direction == 'asc' ? 1 : -1;
					elseif($a[$key] < $b[$key]) return $direction == 'asc' ? -1 : 1;
				}

				return 0;
			}));
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getCount() {
		if(!isset($this->_count)) {
			$this->_iterator->rewind();
			while($this->_iterator->valid()) { $this->_iterator->next(); }
		}

		return $this->_count;
	}

	/**
	 * @inheritDoc
	 */
	public function getUnfilteredCount() {
		return count($this->_data);
	}

	/**
	 * @inheritDoc
	 */
	public function getIterator($start = 0, $count = NULL) {
		$this->_iterator->rewind();
		return new \LimitIterator($this->_iterator, $start, $count === NULL ? -1 : $count);
	}

}