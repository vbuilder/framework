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

	/** @var int */
	protected $_minFilterWordLength = 3;

	/**
	 * Construcs DataTable model over DibiFluent interface
	 *
	 * @warning Given fluent will not be cloned!
	 * 
	 * @param \DibiFluent $fluent [description]
	 */
	function __construct(\DibiFluent $fluent) {
		$this->_fluent = $fluent;
	}
	
	/**
	 * Sets record filter
	 * 
	 * @param array of filtering rules
	 * @return DibiModel fluent interface
	 */
	public function setFilter(array $rules = array()) {
		$this->updating();

		if(count($rules) > 0) {
			$this->applyFilter($this->_fluent, $rules);
		}

		return $this;
	}
	
	/**
	 * Returns total data count
	 *
	 * @warn This function should not be called repeatedly
	 * 
	 * @return int
	 */
	public function getCount() {
		$this->freeze();

		return (int) $this->_fluent->connection->query("SELECT COUNT(*) FROM (%sql) a", (string) $this->_fluent)->fetchSingle();
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
		
		// Sorting
		if(count($sortingColumns) > 0) {
			foreach($sortingColumns as $column => $direction)
				$this->applySortingRule($this->_fluent, $column, $direction);
		}
		
		return new \ArrayIterator($this->_fluent->fetchAll($start, $count));
	}

	/**
	 * Applies filtering rule
	 * 
	 * @param  DibiFluent $fluent
	 * @param  array of filtering rules
	 * @return DibiModel fluent interface
	 */
	protected function applyFilter(DibiFluent &$fluent, array $rules) {	

		// Each 1st level rule will be treated with AND operator
		// OR will be used on the subsets
		foreach($rules as $column => $rule) {

			$prepared = array();

			// Walk through all rule subsets
			if(!isset($rule['keywords'])) {
				foreach($rule as $column2 => $rule2)
					$prepared[] = $this->prepareFilteringRule($column2, $rule2);
				
			} else 
				$prepared[] = $this->prepareFilteringRule($column, $rule);
			
			// d($prepared);

			// Prepare an array of all rules that should be ORed
			$orRules = array();
			$it = new \RecursiveArrayIterator($prepared);
			foreach($it as $columnRules) {
				foreach($columnRules as $rule) {
					$orRules[] = $rule;
				}
			}

			// Apply ORed rules
			$singleConditionCounter = 0;
			foreach($orRules as $rule) {
				if(count($orRules) == 1)
					$fluent->where('%n %sql %s', $rule['column'], $rule['operator'], $rule['keywords']);
				elseif($singleConditionCounter++ == 0)
					$fluent->where('(%n %sql %s', $rule['column'], $rule['operator'], $rule['keywords']);
				elseif($singleConditionCounter == count($orRules))
					$fluent->or('%n %sql %s)', $rule['column'], $rule['operator'], $rule['keywords']);
				else
					$fluent->or('%n %sql %s', $rule['column'], $rule['operator'], $rule['keywords']);
			}
		}

		//$fluent->test();
		//exit;

		return $this;
	}

	/**
	 * Returns prepared filtering rules for SQL use
	 * 
	 * @param  string column name
	 * @param  array of rule options
	 * @return array of rule tokens
	 */
	protected function prepareFilteringRule($column, $rule) {

		$prepared = array();
		$keywords = preg_split('/\\s+/', trim($rule['keywords']));

		// If we have only single word and it is shorter than specified length
		// we will match only strings starting with this word
		$wordStartMatching = count($keywords) == 1 && mb_strlen($keywords[0]) < $this->_minFilterWordLength;

		foreach($keywords as $keyword) {
			
			// We won't apply any filter on words shorter than specified length
			// with exception of words starting with a number.
			if(!$wordStartMatching && mb_strlen($keyword) < $this->_minFilterWordLength && ($keyword[0] < '0' || $keyword[0] > '9')) continue;

			// TODO: Some lexical manipulation
			// TODO: Regexp matching
			
			// Escaping for a LIKE operator
			$keyword = str_replace(
				array('%', 		'_'),
				array('\\%',	'\\_'),
				$keyword
			);

			$prepared[] = array(
				'column' => $column,
				'operator' => 'LIKE',	// Warning: Unescaped! Possibly unsafe for SQL injection
				'keywords' => $wordStartMatching ? "$keyword%" : "%$keyword%"
			);
		}

		return $prepared;
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
