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

namespace vBuilder\Application\UI\Controls;

use vBuilder,
	Nette,
	Nette\Application\Responses\JsonResponse,
	vBuilder\Application\UI\Controls\DataTable\IModel;	

/**
 * Control for generating jQuery datatable
 *
 * @see http://datatables.net/
 * @see http://datatables.net/usage/server-side
 *
 * @author Adam Staněk (velbloud)
 * @since Sep 9, 2012
 */
class DataTable extends vBuilder\Application\UI\Control {

	/** @var DataTable\IModel */
	private $_model;
	
	/** @var array of DataTable\Column */
	private $_columns = array();

	/** @var array (columnName => columnIndex) */
	private $_idColumns = array();

	/** @var array (columnName => direction) */
	private $_sortColumns = array();

	/** @var number of records per page */
	private $_recordsPerPage = 10;
	
	/**
	 * Creates dummy table
	 *
	 * @return DataTable
	 */
	public static function createDummyTable($rows = 72, $cols = 4) {
		$dt = new static;
		
		$dt->setModel(new DataTable\DummyArrayModel($rows, $cols));
		
		for($i = 0; $i < $cols; $i++) {
			$ch = chr(ord('A') + $i);
			$dt->addColumn($ch);
		}
		
		return $dt;
	}
	
	// -------------------------------------------------------------------------

	/**
	 * Sets data model
	 *
	 * @param DataTable\IModel
	 */
	public function setModel(IModel $model) {
		$this->_model = $model;
	}
	
	/**
	 * Returns data model
	 *
	 * @return DataTable\IModel
	 */
	public function getModel() {
		return $this->_model;
	}
	
	// -------------------------------------------------------------------------

	/**
	 * Adds new column to the table
	 * 
	 * @param string name of column
	 * @param string|null column caption (shows in table head)
	 */
	public function addColumn($name, $caption = NULL) {
		$col = new DataTable\DataTableColumn($name, $caption);
		$this->_columns[] = $col;
		return $col;
	}
	
	/**
	 * Returns registered column instances
	 * 
	 * @return array of DataTable\Column
	 */
	public function getColumns() {
		return $this->_columns;
	}

	/**
	 * Sets names of columns which uniquely identifies a record
	 * 
	 * @param string|array column names
	 * @return DataTable fluent interface
	 */
	public function setIdColumns($columnName) {
		$columns = array();
		$args = func_get_args();

		array_walk_recursive($args, function($a) use (&$columns) { $columns[$a] = NULL; } );
		$this->_idColumns = $columns;

		return $this;
	}

	// -------------------------------------------------------------------------
	
	/**
	 * Adds default rule for sorting by column name
	 * 
	 * @param string column name
	 * @param string direction ('asc' || 'desc')
	 *
	 * @return DataTable fluent interface
	 */
	public function addDefaultSortColumn($columnName, $direction = 'asc') {
		$this->_sortColumns[$columnName] = $direction;
		return $this;
	}

	/**
	 * Returns array of default sorting rules (columnName => direction)
	 * @return array (columnName => direction)
	 */
	public function getDefaultSortColumns() {
		return $this->_sortColumns;
	}

	// -------------------------------------------------------------------------

	/**
	 * Sets number of records to show on a single page
	 * 
	 * @param int
	 * @return DataTable fluent interface
	 */
	public function setRecordsPerPage($numRecords) {
		$this->_recordsPerPage = intval($numRecords) ?: 10;
		return $this;
	}

	/**
	 * Returns number of records to show on a single page
	 * 
	 * @return int
	 */
	public function getRecordsPerPage() {
		return $this->_recordsPerPage;
	}
	
	// -------------------------------------------------------------------------

	public function actionDefault() {
		// Better to perform sanity check here than in AJAX request
		$this->init();
	}

	public function actionGetData() {
		$this->init();		

		// dd($this->context->httpRequest->getQuery());

		// Creates array of sorting rules ordered by priority
		// columnName => direction (asc || desc)
		$sortingColumns = array();
		if(($numSortingColumns = intval($this->context->httpRequest->getQuery("iSortingCols", 0))) > 0) {
			for($i = 0; $i < $numSortingColumns; $i++) {
				$columnIndex = intval($this->context->httpRequest->getQuery("iSortCol_$i", -1)); // Column index (starting with 0)
				$direction = $this->context->httpRequest->getQuery("sSortDir_$i", 'asc'); // Sorting direction ('asc' || 'desc')

				if(isset($this->_columns[$columnIndex])) 
					$sortingColumns[$this->_columns[$columnIndex]->getName()] = $direction;
			}
		}		
		
		// Returned structure			
		$data = array(
			"sEcho" => intval($this->context->httpRequest->getQuery('sEcho')),
			"iTotalRecords" => $this->model->getUnfilteredCount(),
			"iTotalDisplayRecords" => $this->model->getCount(),
			"aaData" => $this->getRenderedData(
				$this->context->httpRequest->getQuery('iDisplayStart'), 
				$this->context->httpRequest->getQuery('iDisplayLength'),
				$sortingColumns
			)
		);

		// Getting unfiltered record count might not be supported by the model
		if($data['iTotalRecords'] === NULL)
			$data['iTotalRecords'] = $data['iTotalDisplayRecords'];
		
		$this->getPresenter(true)->sendResponse(new JsonResponse($data));
	}

	/**
	 * Creates an array of rendered data for a page of table rows
	 * 
	 * @param  int|null starting index
	 * @param  int|null number of records to render
	 * @param  array of columns to sort by
	 * 
	 * @return array of arrays (ordered)
	 */
	public function getRenderedData($start = NULL, $length = NULL, array $sortingColumns = array()) {
		$rowData = array();

		$iterator = $this->model->getIterator(
			intval($start) ?: 0,
			intval($length) ?: $this->_recordsPerPage,
			count($sortingColumns) ? $sortingColumns : $this->_sortColumns
		);

		while($iterator->valid()) {
			$currRow = $iterator->current();
			$trRow = &$rowData[];
			$trRow = array();
			
			foreach($this->_columns as $col) {
				$value = NULL;
				if(isset($currRow[$col->getName()])) $value = $currRow[$col->getName()];
				elseif(isset($currRow->{$col->getName()})) $value = $currRow->{$col->getName()};
				else $value = NULL;

				$trRow[] = $col->render($value, $currRow);
			}
			
			$iterator->next();			
		}

		return $rowData;
	}
	
	// -------------------------------------------------------------------------

	/**
	 * Performs some checking and initializes instance variables
	 *
	 * @internal
	 */
	protected function init() {
		if($this->model == NULL) throw new Nette\InvalidStateException("No data model has been set (Forget to call DataTable::setModel?)");
		if(count($this->_columns) == 0) throw new Nette\InvalidStateException("No columns has been set (Forget to call DataTable::addColumn?)");

		if(count($this->_idColumns) > 0) {
			foreach($this->_idColumns as $name => &$index) {
				foreach($this->_columns as $index2 => $col) {
					if($col->getName() == $name) {
						$index = $index2;
					}
				}

				if($index === NULL) {
					$index = count($this->_columns);
					$this->addColumn($name)->setVisible(false);
				}
			}	

		} else {
			foreach($this->_columns as $index => $col)
				$this->_idColumns[$col->getName()] = $index;
		}
	}

	// -------------------------------------------------------------------------
	
	/**
	 * Renderer factory
	 *
	 * @return DataTableRenderer
	 */
	protected function createDefaultRenderer() {
		return new DataTableRenderer($this);
	}
	 
}
