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

namespace vBuilder\Utils;

use vBuilder,
		Nette;

/**
 * Gathering sorting iterator. Same as SortingIterator but creates array of values to sort first for
 * better performance when working with external data.
 */
class GatheringSortingIterator implements \Iterator {

	protected $data;
	protected $sortingData;
	protected $callback;
	protected $gatheringCallback;
	private $position = 0;

	public function __construct($data, $sortingDataGathererCallback, $sortingCallback = null) {

		if (!is_array($data)) {
			if ($data instanceof \Traversable)
				$data = iterator_to_array($data);
			else
				throw new \InvalidArgumentException('Given data has to be an Array or class implementing Traversable interface');
		}

		if (!is_callable($sortingDataGathererCallback))
			throw new \InvalidArgumentException('Given gathering callback is not callable!');
		else
			$this->gatheringCallback = $sortingDataGathererCallback;

		if ($sortingCallback !== null && !is_callable($sortingCallback))
			throw new \InvalidArgumentException('Given sorting callback is not callable!');


		$this->data = $data;
		$this->callback = $sortingCallback;
	}

	public function gatherSortingData() {
		$sortingDataGatherer = $this->gatheringCallback;
		$this->sortingData = $sortingDataGatherer($this->data);


		if ($this->callback !== null)
			uasort($this->sortingData, $this->callback);
		else
			asort($this->sortingData);

		reset($this->sortingData);
	}

	function rewind() {
		if (isset($this->sortingData)) {
			reset($this->sortingData);
			$this->position = 0;
		}
	}

	function current() {
		if (!isset($this->sortingData))
			$this->gatherSortingData();

		return $this->data[key($this->sortingData)];
	}

	function key() {
		return $this->position;
	}

	function next() {
		if (!isset($this->sortingData))
			$this->gatherSortingData();
		++$this->position;
		next($this->sortingData);
	}

	function valid() {
		if (!isset($this->sortingData))
			$this->gatherSortingData();

		return $this->position < count($this->sortingData);
	}

}