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
 * Column representation for DataTable
 *
 * @author Adam Staněk (velbloud)
 * @since Sep 9, 2012
 */
class Column extends Component {

	private $_sortable = true;
	private $_searchable = false;

	public function isSortable() {
		return $this->_sortable;
	}

	public function setSortable($enabled) {
		$this->_sortable = (bool) $enabled;
		return $this;
	}

	public function isSearchable() {
		return $this->_searchable;
	}

	public function setSearchable($enabled) {
		$this->_searchable = (bool) $enabled;
		return $this;
	}

	public function render($value, $rowData) {
		if($this->_renderer) {
			return call_user_func($this->_renderer, $value, $rowData);
		}

		return $value;
	}

}
