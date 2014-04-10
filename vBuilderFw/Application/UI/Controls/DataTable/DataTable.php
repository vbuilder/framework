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

	/** @var helper array for lazy applying of global filter */
	private $_globalFilter = array('keywords' => NULL, 'regexp' => FALSE);

	/** @var array of filtering rules */
	private $_filter = array();

	/** @var array of callbacks (actionName => callback) */
	private $_actions = array();

	/** @var array of DataTable\Button */
	private $_buttons = array();

	/** @var number of records per page */
	private $_recordsPerPage = 10;

	/** @var NULL|string|Callable addtional row class */
	private $_rowClass;

	/** @var bool initialized? */
	private $_initialized = false;

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
		if(!$this->_initialized) $this->init();

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
	 * Returns registered column with given name
	 *
	 * @param string column name
	 * @param bool throw exception if column does not exist?
	 *
	 * @return DataTable\Column|NULL
	 * @throws Nette\InvalidArgumentException if column does not exist and second argument is TRUE
	 */
	public function getColumn($name, $need = TRUE) {
		foreach($this->_columns as $col) {
			if($col->getName() == $name)
				return $col;
		}

		if($need)
			throw new Nette\InvalidArgumentException("Column '$name' does not exist");

		return NULL;
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
	public function addSortColumn($columnName, $direction = 'asc') {
		$this->_sortColumns[$columnName] = $direction;
		return $this;
	}

	/**
	 * Returns array of default sorting rules (columnName => direction)
	 * @return array (columnName => direction)
	 */
	public function getSortColumns() {
		return $this->_sortColumns;
	}

	/**
	 * Returns array of effective sorting rules (columnName => direction)
	 * @return array (columnName => direction)
	 */
	public function getEffectiveSortColumns() {
		return isset($this->session->sortColumns) ? $this->session->sortColumns : $this->_sortColumns;
	}

	// -------------------------------------------------------------------------

	/**
	 * Sets global filter keywords
	 *
	 * @param  string search keywords
	 * @param  boolean should be keywords treated like regular expression?
	 * @return DataTable fluent interface
	 */
	public function setGlobalFilter($keywords, $regexp = false) {

		$this->_globalFilter['keywords'] = trim($keywords) ?: NULL;
		$this->_globalFilter['regexp'] = (bool) $regexp;

		return $this;
	}

	// -------------------------------------------------------------------------

	/**
	 * Sets number of records to show on a single page.
	 * Set to NULL if no pagination should be done.
	 *
	 * @param int|NULL
	 * @return DataTable fluent interface
	 */
	public function setRecordsPerPage($numRecords) {
		$this->_recordsPerPage = $numRecords === NULL ? NULL : (intval($numRecords) ?: 10);
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
	 * Sets addtional row class
	 *
	 * @param NULL|string|Callable
	 */
	public function setRowClass($class) {
		$this->_rowClass = $class;
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
		if(!$this->_initialized) $this->init();
	}

	public function actionGetData() {
		if(!$this->_initialized) $this->init();

		if($this->getParam('authToken') != $this->getAuthToken())
			throw new Nette\Application\ForbiddenRequestException("Invalid authorization token");

		// d($this->context->httpRequest->getQuery());

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

		// Creates a nested array of filtering rules
		// First level means AND operator, second OR
		// Index 0: will be created from global search filter if any
		// Other items are indexed by column name
		$filter = array(array());

		// Global filter
		if(($globalFilter = $this->context->httpRequest->getQuery("sSearch")) != "") {
			$globalFilter = array(
				'keywords' => $globalFilter,
				'regexp' => $this->context->httpRequest->getQuery("bRegex") == "true"
			);
		}

		// Column filtering
		for($i = 0; $i < count($this->_columns); $i++) {
			if($this->context->httpRequest->getQuery("bSearchable_$i") != "true") continue;


			if(($keywords = $this->context->httpRequest->getQuery("sSearch_$i")) == "") {

				// We use this column for global filtering only if it does not have
				// a specified value to filter
				if($globalFilter)
					$filter[0][$this->_columns[$i]->getName()] = $globalFilter;

				continue;
			}

			$filter[$this->_columns[$i]->getName()] = array(
				'keywords' => $keywords,
				'regexp' => $this->context->httpRequest->getQuery("bRegex_$i") == "true"
			);
		}

		// In case no column is searchable and global filtering was requested
		if(count($filter) && count($filter[0]) == 0)
			array_shift($filter);

		$renderedData = $this->getRenderedData(
			intval($this->context->httpRequest->getQuery('iDisplayStart', 0)),
			intval($this->context->httpRequest->getQuery('iDisplayLength', 0)) ?: $this->_recordsPerPage,
			$sortingColumns,
			$filter
		);

		// Returned structure
		$data = array(
			"sEcho" => intval($this->context->httpRequest->getQuery('sEcho')),
			"iTotalRecords" => $this->model->getUnfilteredCount(),
			"iTotalDisplayRecords" => $this->model->getCount(),
			"aaData" => $renderedData
		);

		// Getting unfiltered record count might not be supported by the model
		if($data['iTotalRecords'] === NULL)
			$data['iTotalRecords'] = $data['iTotalDisplayRecords'];

		$this->getPresenter(true)->sendResponse(new JsonResponse($data));
	}

	/**
	 * Creates an array of data for deffered loading
	 *
	 * @return array of arrays (ordered)
	 */
	public function getDefferedData() {
		return $this->getRenderedData(
			0,
			$this->_recordsPerPage,
			$this->effectiveSortColumns,
			$this->_filter
		);
	}

	/**
	 * Creates an array of rendered data for a page of table rows
	 *
	 * @param  int starting index
	 * @param  int number of records to render
	 * @param  array of columns to sort by
	 * @param  array of filtering rules
	 *
	 * @return array of arrays (ordered)
	 */
	public function getRenderedData($start, $length, array $sortingColumns, array $filter) {
		$rowData = array();

		// Persistent sorting
		if($sortingColumns != $this->_sortColumns)
			$this->session->sortColumns = $sortingColumns;

		// Setting filter
		$this->model->setFilter($filter);

		// Retrieve data iterator
		$iterator = $this->model->getIterator(
			$start,
			$length,
			$sortingColumns,
			$filter
		);

		while($iterator->valid()) {
			$rowData[] = $this->getRenderedRowData($iterator->current());
			$iterator->next();
		}

		return $rowData;
	}

	/**
	 * Returns ordered array formatted for load into table
	 *
	 * @todo Add support for DT_RowId
	 * @todo Add support for DT_RowClass
	 *
	 * @param array|object associative data
	 * @return array
	 */
	public function getRenderedRowData($rowData) {
		$trRow = array();

		// Cell data
		foreach($this->_columns as $col) {
			$value = $this->getDataForKey($rowData, $col->getName());
			$trRow[] = $col->render($value, $rowData);
		}

		// Row id
		// $trRow['DT_RowId'] = NULL;

		// Row class
		if(isset($this->_rowClass)) {
			if(is_callable($this->_rowClass)) {
				$cb = $this->_rowClass;
				$class = $cb($rowData);
			} else
				$class = $this->_rowClass;

			if($class != '')
				$trRow['DT_RowClass'] = $class;
		}

		return $trRow;
	}

	/**
	 * Helper function for gathering column data from row
	 *
	 * @param mixed row data
	 * @param string key
	 * @param bool should I throw an exception if data not found?
	 *
	 * @return mixed
	 * @throws Nette\InvalidArgumentException if key is not found in given row (or is NULL) and $need == TRUE
	 */
	protected function getDataForKey($rowData, $key, $need = FALSE) {

		// Objects
		if(is_object($rowData)) {

			// Class property
			if(isset($rowData->{$key}))
				return $rowData->{$key};

			// Array access
			if($rowData instanceof \ArrayAccess) {
				if(isset($rowData[$key]))
					return $rowData[$key];
			}

			// Getter
			$getter = 'get' . ucfirst($key);
			if(method_exists($rowData, $getter)) {
				$value = $rowData->{$getter}();
				if(isset($value))
					return $value;
			}

		}

		// Simple array
		elseif(is_array($rowData)) {
			if(isset($rowData[$key]))
				return $rowData[$key];
		}

		// Data not found or NULL
		if($need) throw new Nette\InvalidArgumentException("Missing ID column $key in given row data");
		return NULL;
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
		if(!$this->_initialized) $this->init();

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
	 * @param  array optional addtional GET parameters
	 * @return string absolute URL
	 */
	public function createActionLink($action, $rowData, $addtionalParams = array()) {
		$args = array_merge(array(
			'action' => $action,
			'authToken' => $this->getAuthToken()
		), $addtionalParams);

		foreach($this->_idColumns as $key => $index) {
			$args['record' . ucfirst($key)] = $this->getDataForKey($rowData, $key, TRUE);
		}

		return (string) $this->link('//perform', $args);
	}

	/**
	 * Creates data payload for AJAX response when updating a single table row
	 *
	 * @param array of ID values (associative)
	 * @return array
	 */
	public function createUpdateRowPayload($pk) {

		$filter = array();
		foreach($pk as $key => $val) {
			$filter[] = array(
				$key => array(
					'keywords' => $val,
					'regexp' => FALSE
				)
			);
		}

		$this->_model->setFilter($filter);
		$it = $this->_model->getIterator(0, 1);
		$rowData = $it->current();

		$payload = array(
			'aRowData' => $this->getRenderedRowData($rowData)
		);

		return $payload;
	}

	/**
	 * Sends JSON response for row update request and terminates script
	 *
	 * @param array of ID values (associative)
	 * @return void
	 */
	public function sendUpdateRowJsonResponse($pk) {
		$data = $this->createUpdateRowPayload($pk);
		$this->getPresenter(true)->sendResponse(new JsonResponse($data));
	}

	// -------------------------------------------------------------------------

	/**
	 * Performs some checking and initializes instance variables
	 *
	 * @internal
	 */
	protected function init() {

		// Sanity check
		if($this->_model == NULL) throw new Nette\InvalidStateException("No data model has been set (Forget to call DataTable::setModel?)");
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
			$this->addColumn('buttons')->setLabel('')->setSortable(false)->setRenderer(function ($value, $rowData) use ($table) {
				$data = '';

				foreach($table->getButtons() as $button)
					if($button->isVisible($rowData))
						$data .= $button->render($rowData) . "\n";

				return $data;
			});
		}

		// Global filter -> filtering rules
		$globalFilterColumns = array();
		foreach($this->_columns as $col) {
			if($col->isSearchable())
				$globalFilterColumns[] = $col;
		}

		if(count($globalFilterColumns)) {
			$this->_filter[0] = array();
			foreach($globalFilterColumns as $col)
				$this->_filter[0][$col->getName()] = $this->_globalFilter;
		}

		$this->_model->setFilter($this->_filter);

		$this->_initialized = true;
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
