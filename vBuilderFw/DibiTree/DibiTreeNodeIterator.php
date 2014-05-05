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

namespace vBuilder\DibiTree;

use vBuilder,
	Nette,
	Iterator,
	ArrayIterator;

/**
 * Tree node iterator which benefits from 
 * structure lft and rgt indexes
 *
 * @author Adam Staněk (V3lbloud)
 * @since Sep 14, 2013
 */
class DibiTreeNodeIterator implements Iterator {

	/** @var array of DibiTreeNode */
	protected $nodes;

	/** @var NULL|int base node id (must exist) */
	protected $baseNodeId;

	/** @var int depth limit relative to base node (< 1 for no limit) */
	protected $depthLimit;

	/** @var NULL|ArrayIterator */
	protected $innerIterator;

	/** @var NULL|int */
	protected $index;

	/**
	 * Constructor
	 *
	 * @param array DibiTree data array
	 * @param int base node id (must exist)
	 * @param int depth limit (relative to base node, < 1 for no limit)
	 */
	public function __construct(array &$treeNodes, $nodeId = NULL, $depthLimit = -1) {
		$this->nodes = &$treeNodes;
		$this->baseNodeId = $nodeId;
		$this->depthLimit = $depthLimit;
	}

	function rewind() {
		if(!isset($this->innerIterator))
			$this->innerIterator = new ArrayIterator($this->nodes);

		if(isset($this->baseNodeId)) {
			// Get index of the possible first child
			$this->index = ($this->nodes[$this->baseNodeId]->lft
				 		 + $this->nodes[$this->baseNodeId]->level - 1) / 2
						 + 1;

			$this->innerIterator->seek($this->index);
		} else {
			$this->index = 0;
			$this->innerIterator->rewind();
		}
	}

	function current() {
		return $this->innerIterator->current();
	}

	function key() {
		return $this->innerIterator->key();
	}

	function next() {
		$this->index++;

		if($this->depthLimit > 0) {
			$maxLevel = isset($this->baseNodeId)
				? $this->nodes[$this->baseNodeId]->level + $this->depthLimit
				: $this->depthLimit - 1;

			if($this->innerIterator->current()->level == $maxLevel && $this->innerIterator->current()->rgt - $this->innerIterator->current()->lft > 1) {

				$this->index += (int) (($this->innerIterator->current()->rgt
				              - $this->innerIterator->current()->lft
				              - 1) / 2);

				return $this->innerIterator->seek($this->index);

			} else {
				
				return $this->innerIterator->next();
			}

		} else {
			return $this->innerIterator->next();
		}
	}

	function valid() {
		return $this->innerIterator->valid()
			&& (!isset($this->baseNodeId) || $this->innerIterator->current()->lft < $this->nodes[$this->baseNodeId]->rgt);
	}

}
