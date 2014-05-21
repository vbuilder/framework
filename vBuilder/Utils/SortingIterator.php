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
 * Sorting iterator
 *
 * <code>
 * $data = array(1, 8, 5, 4, 10);
 * $it = new SortingIterator($data2, function ($item1, $item2) {
 *	return $item1['value'] >= $item2['value'];
 * });
 * </code>
 */
class SortingIterator implements \IteratorAggregate {

	protected $data;
	protected $callback;
	private $iterator;

	public function __construct($data, $callback = null) {

		if (!is_array($data)) {
			if ($data instanceof \Traversable)
				$data = iterator_to_array($data);
			else
				throw new \InvalidArgumentException('Given data has to be an Array or class implementing Traversable interface');
		}

		if ($callback !== null && !is_callable($callback))
			throw new InvalidArgumentException('Given callback is not callable!');

		$this->data = $data;
		$this->callback = $callback;
	}

	public function createIterator() {
		$array = $this->data;

		if ($this->callback !== null)
			usort($array, $this->callback);
		else
			sort($array);

		return new \ArrayIterator($array);
	}

	public function getIterator() {
		if (!isset($this->iterator))
			$this->iterator = $this->createIterator();

		return $this->iterator;
	}

}