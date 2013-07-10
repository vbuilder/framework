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

	/** @var array of callbacks (actionName => callback) */
	private $_actions = array();

	/** @var array of DataTable\Button */
	private $_buttons = array();

	/** @var number of records per page */
	private $_recordsPerPage = 10;

	/** @var Nette\Http\SessionSection */
	protected $session;
	
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
	 *
	 * @return DataTable\Column
	 */
	public function addColumn($name, $caption = NULL) {
		$col = new DataTable\Column($name, $caption);
		
		$this->_columns[] = $col;
		$col->setTable($this);

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

	/**
	 * Returns ID column names
	 * 
	 * @return array of string
	 */
	public function getIdColumns() {
		return array_keys($this->_idColumns);
	}

	// -------------------------------------------------------------------------

	/**
	 * Adds new button to the table
	 *
	 * @warn Please note that if you are passing direct URL as third argument,
	 *   it is your own responsibility to protect against CSRF.
	 * 
	 * @param string name of column
	 * @param string|null column caption (shows in table head)
	 * @param string|Callable action callback or URL
	 *
	 * @return DataTable\Button
	 */
	public function addButton($name, $caption, $callback) {
		
		$button = new DataTable\Button($name, $caption);
		
		if(is_callable($callback))
			$this->registerAction($name, $callback);
		else
			$button->setUrl($callback);

		$this->_buttons[] = $button;
		$button->setTable($this);

		return $button;
	}

	/**
	 * Returns registered button instances
	 * 
	 * @return array of DataTable\Button
	 */
	public function getButtons() {
		return $this->_buttons;
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

	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 * 
	 * @param  Nette\ComponentModel\IComponent
	 * @return void
 	 */
	protected function attached($parent) {
		parent::attached($parent);

		// Creates session storage for each instance of DataTable
		$context = $parent->getPresenter()->getContext();
		$sessionSection = $context->session->getSection(strtr(__CLASS__, '\\', '.'));
		$this->session = &$sessionSection->{$this->getUniqueId()};
		if($this->session == NULL) $this->session = new \StdClass;

		// Creates unique authorization token (CSRF prevention)
		if(!isset($this->session->authToken))
			$this->session->authToken = vBuilder\Utils\Strings::randomHumanToken(8);
	}

	/**
	 * Returns authorization token
	 * 
	 * @return string
	 */
	public function getAuthToken() {
		return $this->session->authToken;
	}

	// -------------------------------------------------------------------------

	public function actionDefault() {
		// Better to perform sanity check here than in AJAX request
		$this->init();
	}

	public function actionGetData() {
		$this->init();

		if($this->getParam('authToken') != $this->getAuthToken())
			throw new Nette\Application\ForbiddenRequestException("Invalid authorization token");

		// if($this->getAuthToken())

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
			
			// TODO: Support for DT_RowId
			// TODO: Support for DT_RowClass
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
	 * Registers new action with callable
	 * 
	 * @param  string action name
	 * @param  Callable
	 * @return DataTable fluent interface
	 */
	public function registerAction($action, $callable) {
		if(!is_callable($callable))
			throw new Nette\InvalidArgumentException("Given action callback is not callable");

		$this->_actions[$action] = $callable;
		return $this;
	}

	/**
	 * Handles action execution
	 */
	public function actionPerform() {
		$this->init();

		if($this->getParam('authToken') != $this->getAuthToken())
			throw new Nette\Application\ForbiddenRequestException("Invalid authorization token");

		if(!isset($this->_actions[$this->getParam('action')]))
			throw new Nette\InvalidArgumentException("Action " . var_export($this->getParam('action'), true) . " not registered");

		$pk = array();
		foreach($this->_idColumns as $key => $index) {
			$pk[$key] = $this->getParam('record' . ucfirst($key));
			if($pk[$key] === NULL)
				throw new Nette\InvalidArgumentException("Missing ID column " . var_export($key, true) . " for action " . var_export($this->getParam('action'), true));
		}

		$cb = $this->_actions[$this->getParam('action')];
		$cb($pk, $this);
	}

	/**
	 * Generates absolute URL to DataTable action.
	 * URL contains row ID columns + authorization token as parameters.
	 * 
	 * @param  string action name
	 * @param  object|array row data
	 * @return string absolute URL
	 */
	public function createActionLink($action, $rowData) {
		$args = array(
			'action' => $action,
			'authToken' => $this->getAuthToken()
		);

		foreach($this->_idColumns as $key => $index) {
			
			if(isset($rowData->{$key})) $value = $rowData->{$key};
			elseif(isset($rowData[$key])) $value = $rowData[$key];
			else throw new Nette\InvalidArgumentException("Missing ID column $key in given row data");

			$args['record' . ucfirst($key)] = $value;
		}

		return (string) $this->link('//perform', $args);
	}

	// -------------------------------------------------------------------------

	/**
	 * Performs some checking and initializes instance variables
	 *
	 * @internal
	 */
	protected function init() {

		// Sanity check
		if($this->model == NULL) throw new Nette\InvalidStateException("No data model has been set (Forget to call DataTable::setModel?)");
		if(count($this->_columns) == 0) throw new Nette\InvalidStateException("No columns has been set (Forget to call DataTable::addColumn?)");

		// Auto ID columns
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

		// Auto column for buttons
		if(count($this->_buttons) > 0) {
			$table = $this;
			$this->addColumn('buttons')->setLabel('')->setRenderer(function ($value, $rowData) use ($table) {
				$data = '';

				foreach($table->getButtons() as $button)
					$data .= $button->render($rowData) . "\n";

				return $data;
			});
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
