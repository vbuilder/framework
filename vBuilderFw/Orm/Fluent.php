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

namespace vBuilder\Orm;

use vBuilder,
	 Nette,
	 dibi;

/**
 * Modified DibiFluent for getting results
 * associated in some row class.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 27, 2011
 */
class Fluent extends \DibiFluent {

	/** @var string name of row class */
	protected $rowClass = null;
	
	/** @var Nette\DI\IContainer DI */
	protected $context;

	/**
	 * Constructs fluent object
	 * 
	 * @param string $rowClass row class
	 * @param Nette\DI\IContainer DI
	 */
	public function __construct($rowClass, Nette\DI\IContainer $context) {
		$this->rowClass = $rowClass;
		$this->context = $context;
		
		parent::__construct($this->context->connection);
	}

	/**
	 * Returns row class name which will be assigned as result set data holder
	 * 
	 * @return string class name
	 */
	public function getRowClass() {
		return $this->rowClass;
	}
	
	/**
	 * Executes query and translates result
	 * 
	 * @param  array|mixed      one or more arguments
	 * @return DibiResult|int
	 */
	protected function query($args) {
		$result = call_user_func_array(array($this->connection, 'query'), func_get_args());
		if($result instanceOf \DibiResult) {
			$context = $this->context;
			$rowClass = $this->rowClass;
			
			$result->setRowFactory(function ($data) use ($context, $rowClass) {
				return new $rowClass($data, $context);
			});
		}

		return $result;
	}

	// Overloaded methods from DibiFluent for executing query -------------------

	/**
	 * Generates and executes SQL query.
	 * @param  mixed what to return?
	 * @return DibiResult|int  result set object (if any)
	 * @throws DibiException
	 */
	public function execute($return = NULL) {
		$res = $this->query($this->_export());
		return $return === dibi::IDENTIFIER ? $this->connection->getInsertId() : $res;
	}

	/**
	 * Generates, executes SQL query and fetches the single row.
	 * @return DibiRow|FALSE  array on success, FALSE if no next record
	 */
	public function fetch() {
		if($this->command === 'SELECT') {
			return $this->query($this->_export(NULL, array('%lmt', 1)))->fetch();
		} else {
			return $this->query($this->_export())->fetch();
		}
	}

	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed  value on success, FALSE if no next record
	 */
	public function fetchSingle() {
		if($this->command === 'SELECT') {
			return $this->query($this->_export(NULL, array('%lmt', 1)))->fetchSingle();
		} else {
			return $this->query($this->_export())->fetchSingle();
		}
	}

	/**
	 * Fetches all records from table.
	 * @param  int  offset
	 * @param  int  limit
	 * @return array
	 */
	public function fetchAll($offset = NULL, $limit = NULL) {
		return $this->query($this->_export(NULL, array('%ofs %lmt', $offset, $limit)))->fetchAll();
	}

	/**
	 * Fetches all records from table and returns associative tree.
	 * @param  string  associative descriptor
	 * @return array
	 */
	public function fetchAssoc($assoc) {
		return $this->query($this->_export())->fetchAssoc($assoc);
	}

	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @param  string  associative key
	 * @param  string  value
	 * @return array
	 */
	public function fetchPairs($key = NULL, $value = NULL) {
		return $this->query($this->_export())->fetchPairs($key, $value);
	}

	/**
	 * Required by the IteratorAggregate interface.
	 * @param  int  offset
	 * @param  int  limit
	 * @return DibiResultIterator
	 */
	public function getIterator($offset = NULL, $limit = NULL) {
		return $this->query($this->_export(NULL, array('%ofs %lmt', $offset, $limit)))->getIterator();
	}

}