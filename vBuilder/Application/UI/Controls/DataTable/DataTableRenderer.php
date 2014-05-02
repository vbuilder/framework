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
	Nette;

/**
 * DataTable renderer
 *
 * @see http://datatables.net/
 *
 * @author Adam Staněk (velbloud)
 * @since Sep 9, 2012
 */
class DataTableRenderer extends vBuilder\Application\UI\ControlRenderer {

	public function renderDefault() {

		$resultSet = $this->control->resultSet;

		$this->template->columns = $this->control->getColumns();

		//$this->template->visibleColumns = array();
		$this->template->sortingColumns = array();
		foreach($this->control->getColumns() as $index => $column) {
			//if($column->isVisible())
				//$this->template->visibleColumns[] = $column;

			if(isset($resultSet->sortingColumns[$column->getName()])) {
				// $sortingColumns[] = array(columnIndex, sortingMethod)
				$this->template->sortingColumns[] = array($index, $resultSet->sortingColumns[$column->getName()]);
			}
		}

		// First page of records
		$this->template->rows = $this->control->getRenderedData(
			0,
			$this->control->recordsPerPage,
			$resultSet
		);

		$this->template->total = $resultSet->count;
		$this->template->unfilteredTotal = $resultSet->unfilteredCount ?: $resultSet->count;

		$this->template->isSortable = true;
		$this->template->filter = $resultSet->filteringRules;
	}

}
