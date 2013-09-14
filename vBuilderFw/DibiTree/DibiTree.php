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
	Dibi,
	DibiConnection,
	IteratorAggregate;

/**
 * Abstract tree model implementation over dibi database layer
 *
 * @author Adam Staněk (V3lbloud)
 * @since Sep 12, 2013
 */
class DibiTree extends Nette\Object implements IteratorAggregate {

	/**/ /** Move direction */
	const MOVE_UNDER 		= 1;
	const MOVE_IN_FRONT_OF	= 2;
	const MOVE_BEHIND		= 3;
	/**/

	/** @var DibiConnection */
	protected $db;

	/** @var string table name */
	protected $table;

	/** @var Callable|NULL */
	protected $nodeFactory;

	/** @var string */
	protected $nodeClass = 'vBuilder\\DibiTree\\DibiTreeNode';

	/** @var array of DibiTreeNode or NULL if not loaded yet */
	protected $nodes;

	/** @var bool TRUE if data has been changed */
	protected $dirty;

	/**
	 * Constructor
	 * 
	 * @param DibiConnection dibi connection
	 */
	public function __construct($tableName, DibiConnection $dbConnection) {
		$this->db = $dbConnection;
		$this->table = (string) $tableName;
	}

	/**
	 * Returns true if data has been changed since load
	 * 
	 * @return boolean
	 */
	public function isDirty() {
		return $this->dirty;
	}

	/**
	 * Sets tree node factory
	 *
	 * @param Callable factory callable
	 * @return DibiTree fluent interface
	 * @throws  Nette\InvalidArgumentException if given attribute is not callable
	 */
	public function setNodeFactory($callable) {
		if(!is_callable($callable))
			throw new Nette\InvalidArgumentException("Node factory must be callable");

		$this->nodeFactory = $callable;
		return $this;
	}

	/**
	 * Sets tree node class
	 *
	 * @param string class name
	 * @return DibiTree fluent interface
	 * @throws  Nette\InvalidArgumentException if given class does not exist
	 */
	public function setNodeClass($class) {
		if(!class_exists($class))
			throw new Nette\InvalidArgumentException("Class '$class' does not exist");

		$this->nodeClass = $class;
		return $this;
	}

	/**
	 * Returns tree node with given id or NULL if it does not exist
	 * @param  int node id
	 * @return NULL|DibiTreeNode
	 */
	public function getNode($id) {
		if(!isset($this->nodes)) $this->loadFromDb();
		if(!isset($this->nodes[$id])) return NULL;

		return $this->nodes[$id];
	}

	/**
	 * Returns tree iterator
	 *
	 * @param int base node id (if specified only children of this node will be iterated)
	 * @param int number of levels to iterate ($depthLimit < 1 means no limit)
	 *
	 * @return DibiTreeNodeIterator
	 * @throws Nette\InvalidArgumentException if trying to iterate through children of non-existing node
	 */
	public function getIterator($nodeId = NULL, $depthLimit = -1) {
		if(!isset($this->nodes)) $this->loadFromDb();

		if(isset($nodeId) && !isset($this->nodes[$nodeId]))
			throw new Nette\InvalidArgumentException("Node '$nodeId' does not exist");

		return new DibiTreeNodeIterator($this->nodes, $nodeId, $depthLimit);
	}

	/**
	 * Performs atomic insert of new node into tree structure
	 * and returns it's ID
	 * 
	 * @param array node data
	 * @param NULL|int ID of parent node
	 * @return  int ID of newly inserted node [description]
	 * @throws Nette\InvalidArgumentException if trying to insert node under non-existing parent
	 */
	public function addNode(array $data = array(), $parentId = NULL) {

		// Root nodes
		if($parentId === null) {
			$this->db->query(
				'INSERT INTO %n (%n)', $this->table, array_merge(array('lft', 'rgt', 'level'), array_keys($data)),
				'SELECT IFNULL(MAX([rgt]), 0) + 1, IFNULL(MAX([rgt]), 0) + 2, 0',
				(count($data) ? ',' : ''), array_values($data),
				'FROM %n', $this->table
			);

			$id = $this->db->getInsertId();
		}

		// Child nodes
		else {
			$this->db->query('LOCK TABLES %n READ', $this->table);

			$query = $this->db->query(
				'SELECT [rgt] AS [parentRgt], ',
				'[level] AS [parentLevel] ',
				'FROM %n', $this->table,
				'WHERE [id] = %i', $parentId
			);

			$query->setType('parentRgt', Dibi::FIELD_INTEGER);
			$query->setType('parentLevel', Dibi::FIELD_INTEGER);
			$result = $query->fetch();

			if($result === false) {
				$this->db->query("UNLOCK TABLES");
				throw new Nette\InvalidArgumentException("Node with id '$parentId' not found in table '$this->table'");
			}

			$this->db->begin();

			$this->db->query(
				'UPDATE %n', $this->table,
				'SET [lft] = [lft] + 2',
				'WHERE [lft] > %i', $result["parentRgt"]
			);

			$this->db->query(
				'UPDATE %n', $this->table,
				'SET [rgt] = [rgt] + 2',
				'WHERE [rgt] >= %i', $result["parentRgt"]
			);

			$this->db->query("INSERT INTO %n %v", $this->table, array_merge($data, array(
				 'lft' => $result["parentRgt"],
				 'rgt' => $result["parentRgt"] + 1,
				 'level' => $result["parentLevel"] + 1,
			)));

			$id = $this->db->getInsertId();

			$this->db->commit();
			$this->db->query("UNLOCK TABLES");
		}

		$this->dirty = TRUE;
		return $id;
	}

	/**
	 * Performs atomic remove of node from the tree structure.
	 * 
	 * @param int node id
	 * @return void
	 * @throws Nette\InvalidArgumentException if trying to remove non-existing node
	 */
	public function removeNode($id) {

		$this->db->query('LOCK TABLES %n READ', $this->table);

		$query = $this->db->query(
			'SELECT [lft] AS [nodeLeft], ',
				'[rgt] AS [nodeRight], ',
				'[rgt]-[lft]+1 AS [nodeSize] ',
			'FROM %n', $this->table,
			'WHERE [id] = %i', $id
		);

		$query->setType('nodeLeft', Dibi::FIELD_INTEGER);
		$query->setType('nodeRight', Dibi::FIELD_INTEGER);
		$query->setType('nodeSize', Dibi::FIELD_INTEGER);
		$result = $query->fetch();

		if($result === false)  {
			$this->db->query("UNLOCK TABLES");
			throw new Nette\InvalidArgumentException("Node with id '$id' not found in table '$this->table'");
		}

		$this->db->begin();

		$this->db->query(
			'DELETE FROM %n', $this->table, 'WHERE [lft] >= %i', $result['nodeLeft'],
			'AND [rgt] <= %i', $result['nodeRight']);

		$this->db->query(
			'UPDATE %n', $this->table, 'SET [lft] = [lft] - %i', $result['nodeSize'],
			'WHERE [lft] > %i', $result['nodeRight']);

		$this->db->query(
			'UPDATE %n', $this->table, 'SET [rgt] = [rgt] - %i', $result['nodeSize'],
			'WHERE [rgt] > %i', $result['nodeRight']);

		$this->db->commit();
		$this->db->query("UNLOCK TABLES");

		$this->dirty = TRUE;
	}

	/**
	 * Atomically moves requested node under / behind / in front of target node.
	 *
	 * @param int node id
	 * @param int target node id
	 * @param int move direction
	 *
	 * @throws Nette\InvalidArgumentException if trying to move non-existing node or target node does not exist
	 * @throws LogicException if requested move does not make any sense for current structure
	 */
	public function moveNode($id, $targetId, $direction = self::MOVE_UNDER) {
		if($id == $targetId) throw new \LogicException("Cannot move node to itself");

		$this->db->query('LOCK TABLES %n READ', $this->table);

		$query = $this->db->query(
			"SELECT [lft] AS [nodeLeft], ".
				"[rgt] AS [nodeRight], ".
				"[level] AS [nodeLevel], ".
				"[rgt]-[lft]+1 AS [nodeSize] ".
			"FROM %n", $this->table, " ".
			"WHERE [id] = %i", $id);

		$query->setType('nodeLeft', Dibi::FIELD_INTEGER);
		$query->setType('nodeRight', Dibi::FIELD_INTEGER);
		$query->setType('nodeLevel', Dibi::FIELD_INTEGER);
		$query->setType('nodeSize', Dibi::FIELD_INTEGER);
		$result = $query->fetch();

		if($result === false)  {
			$this->db->query("UNLOCK TABLES");
			throw new Nette\InvalidArgumentException("Node with id '$id' not found in table '$this->table'");
		}

		$query2 = $this->db->query(
			"SELECT [lft] AS [targetLeft], ".
				"[rgt] AS [targetRight], ".
				"[level] AS [targetLevel], ".
				($direction != self::MOVE_BEHIND ? 0 : "[rgt]-[lft]+1")." AS [targetSize] ".
			"FROM %n", $this->table, " ".
			"WHERE [id] = %i", $targetId);

		$query2->setType('targetLeft', Dibi::FIELD_INTEGER);
		$query2->setType('targetRight', Dibi::FIELD_INTEGER);
		$query2->setType('targetLevel', Dibi::FIELD_INTEGER);
		$query2->setType('targetSize', Dibi::FIELD_INTEGER);
		$result2 = $query2->fetch();

		if($result2 === false)  {
			$this->db->query("UNLOCK TABLES");
			throw new Nette\InvalidArgumentException("Node with id '$targetId' not found in table '$this->table'");
		}

		if(($result2["targetLeft"] >= $result["nodeLeft"]) && ($result2["targetLeft"] <= $result["nodeRight"])) {
			$this->db->query("UNLOCK TABLES");
			throw new \LogicException("Cannot move node with id '$id' under/before/after it's child '$targetId'");
		}

		$this->db->begin();

		$mv = $direction == self::MOVE_IN_FRONT_OF ? $result2["targetLeft"] - 1
			:( $direction == self::MOVE_BEHIND ? $result2["targetRight"] : $result2["targetLeft"]);

		$this->db->query(
			'UPDATE %n', $this->table, 'SET [lft] = [lft] + %i', $result['nodeSize'],
			'WHERE [lft] > %i', $mv
		);

		$this->db->query(
			'UPDATE %n', $this->table, 'SET [rgt] = [rgt] + %i', $result['nodeSize'],
			'WHERE [rgt] > %i', $mv
		);

		$mvConst = $direction == self::MOVE_UNDER ? 1 : 0;
		$nSize = ($result["nodeLeft"] > $result2["targetLeft"]) ? $result["nodeSize"] : 0;
		$sum = 0 - $result["nodeLeft"] - $nSize + $result2["targetLeft"] + $result2["targetSize"] + $mvConst;

		$this->db->query(
				"UPDATE %n", $this->table, "SET [lft] = [lft] + %i,", $sum,
					  "[rgt] = [rgt] + %i,", $sum,
					  "[level] = [level] + %i",
					  $mvConst + ($result["nodeLevel"] > $result2["targetLevel"] ? 0 - $result["nodeLevel"] + $result2["targetLevel"] : $result2["targetLevel"] - $result["nodeLevel"]),
				"WHERE [lft] >= %i", $result["nodeLeft"] + $nSize,
					  "AND [rgt] <= %i", $result["nodeRight"] + $nSize);

		$this->db->query(
			'UPDATE %n', $this->table, 'SET [lft] = [lft] - %i', $result['nodeSize'],
			'WHERE [lft] > %i', $result['nodeRight'] + $nSize
		);

		$this->db->query(
			'UPDATE %n', $this->table, 'SET [rgt] = [rgt] - %i', $result['nodeSize'],
			'WHERE [rgt] > %i', $result['nodeRight'] + $nSize
		);

		$this->db->commit();
		$this->db->query("UNLOCK TABLES");

		$this->dirty = TRUE;
	}

	/**
	 * Loads node data from DB
	 * 
	 * @return void
	 */
	protected function loadFromDb() {
		$dibiResult = $this->db->query('SELECT * FROM %n', $this->table, 'ORDER BY [lft]');

		if(isset($this->nodeFactory))
			$dibiResult->setRowFactory($this->nodeFactory);
		else
			$dibiResult->setRowClass($this->nodeClass);

		$this->nodes = $dibiResult->fetchAssoc('id');
		$this->dirty = FALSE;
	}

}