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

	/** @var IModel */
	private $_model;
	
	/** @var array */
	private $_columns = array();
	
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
	
	/**
	 * Sets datamodel
	 *
	 * @param IModel
	 */
	public function setModel(IModel $model) {
		$this->_model = $model;
	}
	
	/**
	 * Returns datamodel
	 *
	 * @return IModel
	 */
	public function getModel() {
		return $this->_model;
	}
	
	public function addColumn($name, $caption = NULL) {
		$col = new DataTable\DataTableColumn($name, $caption);
		$this->_columns[] = $col;
		return $col;
	}
	
	public function columns() {
		return $this->_columns;
	}
		
	public function actionGetData() {
		
		if($this->model == NULL) throw new Nette\InvalidStateException("No data model has been set (Forget to call DataTable::setModel?)");
		if(count($this->columns()) == 0) throw new Nette\InvalidStateException("No columns has been set (Forget to call DataTable::addColumn?)");
		
		$startLimit = intval($this->context->httpRequest->getQuery('iDisplayStart')) ?: 0;
		$countLimit = intval($this->context->httpRequest->getQuery('iDisplayLength')) ?: 10;
		$iterator = $this->model->getIterator($startLimit, $countLimit);
					
		$data = array(
			"sEcho" => intval($this->context->httpRequest->getQuery('sEcho')),
			"iTotalRecords" => 0,
			"iTotalDisplayRecords" => $this->model->getCount(),
			"aaData" => array()
		);
		
		
		while($iterator->valid()) {
			$currRow = $iterator->current();
			$trRow = &$data["aaData"][];
			$trRow = array();
			
			foreach($this->columns() as $col) {
				if(isset($currRow[$col->name()])) $trRow[] = $currRow[$col->name()];
				elseif(isset($currRow->{$col->name()})) $trRow[] = $currRow->{$col->name()};
				else $trRow[] = NULL;
			}
			
			$data['iTotalRecords']++;
			$iterator->next();
			
		}

		$this->getPresenter(true)->sendResponse(new JsonResponse($data));
	}
	
	// ------------------------------------------------------------
	
	/**
	 * Renderer factory
	 *
	 * @return DataTableRenderer
	 */
	protected function createDefaultRenderer() {
		return new DataTableRenderer($this);
	}
	 
}
