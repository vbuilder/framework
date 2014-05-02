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
 * Dibi result set implementation
 *
 * @author Adam Staněk (velbloud)
 * @since Apr 10, 2014
 */
class DibiResultSet extends BaseResultSet {

	private $fluent;

	function __construct(DibiFluent $fluent, array $filteringRules = array(), array $sortingColumns = array()) {
		parent::__construct($filteringRules, $sortingColumns);

		$this->fluent = $fluent;
	}

	/**
	 * @inheritDoc
	 */
	public function getCount() {
		return $this->fluent->count();
	}

	/**
	 * @inheritDoc
	 */
	public function getUnfilteredCount() {
		// Not supported
		return NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function getIterator($start = 0, $count = NULL) {
		return $this->fluent->getIterator($start, $count);
	}

}