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

	/** @var applied filtering fules */
	protected $_filteringRules;

	/** @var int */
	protected $_minFilterWordLength = 3;

	/**
	 * Constructs DataTable model over DibiFluent interface
	 *
	 * @warning Given fluent will not be cloned!
	 *
	 * @param \DibiFluent $fluent [description]
	 */
	function __construct(\DibiFluent $fluent) {
		$this->_fluent = $fluent;
	}

	/**
	 * @inheritDoc
	 */
	public function getResultSet(array $filteringRules = array(), array $sortingColumns = array()) {
		$fluent = clone $this->_fluent;

		// Filtering
		$this->applyFilter($fluent, $filteringRules);

		// Sorting
		foreach($sortingColumns as $column => $direction)
			$this->applySortingRule($fluent, $column, $direction);

		return new DibiResultSet(
			$fluent,
			$filteringRules,
			$sortingColumns
		);
	}

	// -------------------------------------------------------------------------

	/**
	 * Applies filtering rule
	 *
	 * @param  DibiFluent $fluent
	 * @param  array of filtering rules
	 * @return DibiModel fluent interface
	 */
	protected function applyFilter(DibiFluent $fluent, array $rules) {

		foreach($rules as $key => $rule) {
			$keywords = $rule['regexp'] ? array($rule['keywords']) : preg_split('/\\s+/', trim($rule['keywords']));
			$columns = isset($rule['columns']) ? $rule['columns'] : array($key);

			// If we have only single word and it is shorter than specified length
			// we will match only strings starting with this word
			$wordStartMatching = !$rule['regexp'] && count($keywords) == 1 && mb_strlen($keywords[0]) < $this->_minFilterWordLength;

			foreach($keywords as $keyword) {
				// Start new AND expression
				$params = array('');

				foreach($columns as $col) {
					// Expression
					$params[0] .= ' OR %n %sql %s';

					// Column name
					$params[] = $col;

					// Operator
					$params[] = $rule['regexp'] ? 'REGEXP' : 'LIKE';

					// Value (regexp)
					if($rule['regexp'])
						$params[] = $keyword;

					// Value (strings)
					else {
						// Escaping for a LIKE operator
						$keyword = str_replace(
							array('%', 		'_'),
							array('\\%',	'\\_'),
							$keyword
						);

						// Value (anchored string)
						if(count($keywords) == 1 && mb_strlen($keywords[0]) < $this->_minFilterWordLength)
							$params[] = "$keyword%";

						// Value (generic string)
						else
							$params[] = "%$keyword%";
					}
				}

				// Append as AND
				$params[0] = '(' . mb_substr($params[0], 4) . ')';
				call_user_func_array(array($fluent, 'where'), $params);
			}
		}
	}

	// -------------------------------------------------------------------------

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
	protected function applySortingRule(DibiFluent $fluent, $columnName, $direction = 'asc') {
		$cb = $this->_applySortingCallback;

		if($cb === NULL || !$cb($fluent, $columnName, $direction))
			$fluent->orderBy('%n' . ($direction == 'desc' ? ' DESC' : ''), $columnName);

		return $this;
	}

}
