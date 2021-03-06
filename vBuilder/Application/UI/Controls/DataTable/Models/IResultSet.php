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
 * Result set interface
 *
 * @author Adam Staněk (velbloud)
 * @since Apr 10, 2014
 */
interface IResultSet {

	/**
	 * Returns applied filtering rules.
	 *
	 * @return array
	 */
	public function getFilteringRules();

	/**
	 * Returns applied sorting.
	 *
	 * @return array
	 */
	public function getSortingColumns();

	/**
	 * Returns number of records.
	 *
	 * @return int
	 */
	public function getCount();

	/**
	 * Returns number of records before filtering.
	 * If information is not available, returns NULL.
	 *
	 * @return int|NULL
	 */
	public function getUnfilteredCount();

	/**
	 * Returns iterator of records.
	 *
	 * @param int start offset
	 * @param int|NULL count
	 * @return Iterator
	 */
	public function getIterator($start = 0, $count = NULL);

}
