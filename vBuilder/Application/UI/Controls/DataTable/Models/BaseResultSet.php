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
 * Base result set implementation
 *
 * @author Adam Staněk (velbloud)
 * @since Apr 10, 2014
 */
abstract class BaseResultSet extends Nette\Object implements IResultSet {

	private $filteringRules;
	private $sortingColumns;

	function __construct(array $filteringRules = array(), array $sortingColumns = array()) {
		$this->filteringRules = $filteringRules;
		$this->sortingColumns = $sortingColumns;
	}

	/**
	 * @inheritDoc
	 */
	public function getFilteringRules() {
		return $this->filteringRules;
	}

	/**
	 * @inheritDoc
	 */
	public function getSortingColumns() {
		return $this->sortingColumns;
	}

}