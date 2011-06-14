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

use dibi;

/**
 * Helper class for easier work with models based on
 * traversal tree structure.
 *
 * @author Adam Staněk (velbloud)
 * @since Jan 8, 2011
 */
class TreeTraversalDbHelper {
	
	const MOVE_UNDER = 1;
	const MOVE_FRONT = 2;
	const MOVE_AFTER = 3;

	protected $table;
	protected $fieldId;
	protected $fieldLft;
	protected $fieldRgt;
	protected $fieldLevel;

	
	/**
	 * Inserts new record to table and handle traversal indexes.
	 * It's thread safe.
	 *
	 * @param array associative array of data for new record
	 * @param mixed ID or parent record
	 * @return mixed ID of added record
	 * @throws \NotFoundException if trying to insert record under non-existing parent
	 */
	public function addRecord($dataArray, $parentId = null) {
		if($parentId === null) {
			dibi::query(
				"INSERT INTO [".$this->table."] (%n)",
					\array_merge(array($this->fieldLft, $this->fieldRgt, $this->fieldLevel), array_keys($dataArray)),
				"SELECT IFNULL(MAX([".$this->fieldRgt."]), 0) + 1, IFNULL(MAX([".$this->fieldRgt."]), 0) + 2, 0, ",
					\array_values($dataArray),
				"FROM [".$this->table."]"
			);

			return dibi::getInsertId();
		}

		// S rodicem
		else {
			dibi::query("LOCK TABLES [".$this->table."] READ");

			$query = dibi::query(
				"SELECT [".$this->fieldRgt."] AS [parentRgt], ".
				"[".$this->fieldLevel."] AS [parentLevel] ".
			"FROM [".$this->table."] ".
			"WHERE [id] = %i", $parentId);

			$query->setType('parentRgt', Dibi::FIELD_INTEGER);
			$query->setType('parentLevel', Dibi::FIELD_INTEGER);
			$result = $query->fetch();

			if($result === false) {
				dibi::query("UNLOCK TABLES");
				throw new \NotFoundException("Record with id '$parentId' which supposed to be parent record not found in table '$this->table'");
			}

			dibi::begin();

			dibi::query(
				"UPDATE [".$this->table."] SET [".$this->fieldLft."] = [".$this->fieldLft."] + 2 ".
				"WHERE [".$this->fieldLft."] > %i", $result["parentRgt"]);

			dibi::query(
				"UPDATE [".$this->table."] SET [".$this->fieldRgt."] = [".$this->fieldRgt."] + 2 ".
				"WHERE [".$this->fieldRgt."] >= %i", $result["parentRgt"]);

			dibi::query("INSERT INTO [".$this->table."] %v", \array_merge($dataArray, array(
				 $this->fieldLft => $result["parentRgt"],
				 $this->fieldRgt => $result["parentRgt"] + 1,
				 $this->fieldLevel => $result["parentLevel"] + 1,
			)));

			$id = dibi::getInsertId();

			dibi::commit();	
			dibi::query("UNLOCK TABLES");

			return $id;
		}
	}

	/**
	 * Deletes record and takes care of traversal indexes.
	 * It's thread safe.
	 *
	 * @param mixed ID of record
	 * @throws \NotFoundException if trying to delete non-existing record
	 */
	public function delRecord($resultId) {
		dibi::query("LOCK TABLES [".$this->table."] READ");

		$query = dibi::query(
			"SELECT [".$this->fieldLft."] AS [nodeLeft], ".
				"[".$this->fieldRgt."] AS [nodeRight], ".
				"[".$this->fieldRgt."]-[".$this->fieldLft."]+1 AS [nodeSize] ".  
			"FROM [".$this->table."] ".
			"WHERE [id] = %i", $resultId);

		$query->setType('nodeLeft', Dibi::FIELD_INTEGER);
		$query->setType('nodeRight', Dibi::FIELD_INTEGER);
		$query->setType('nodeSize', Dibi::FIELD_INTEGER);
		$result = $query->fetch();

		if($result === false)  {
			dibi::query("UNLOCK TABLES");
			throw new \NotFoundException("Record with id '$resultId' not found in table '$this->table'");
		}

		dibi::begin();

		dibi::query(
			"DELETE FROM [".$this->table."] WHERE [".$this->fieldLft."] >= %i", $result["nodeLeft"],
			" AND [".$this->fieldRgt."] <= %i", $result["nodeRight"]);

		dibi::query(
			"UPDATE [".$this->table."] SET [".$this->fieldLft."] = [".$this->fieldLft."] - %i", $result["nodeSize"],
			"WHERE [".$this->fieldLft."] > %i", $result["nodeRight"]);

		dibi::query(
			"UPDATE [".$this->table."] SET [".$this->fieldRgt."] = [".$this->fieldRgt."] - %i", $result["nodeSize"],
			"WHERE [".$this->fieldRgt."] > %i", $result["nodeRight"]);

		dibi::commit();
		dibi::query("UNLOCK TABLES");
	}


	/**
	 * Moves record in structure and update traversal indexes.
	 * It's thread safe.
	 *
	 * @param int record id
	 * @param int target id
	 * @param int move method: MOVE_UNDER, MOVE_FRONT, MOVE_AFTER
	 *
	 * @throws \NotFoundException record $resultId nebo $targetId is not found
	 * @throws \LogicException if requested move is not supported
	 */
	public function moveRecord($resultId, $targetId, $method = self::MOVE_UNDER) {
		if($resultId == $targetId) throw new \LogicException("Cannot move record to it self");

		dibi::query("LOCK TABLES [".$this->table."] READ");

		$query = dibi::query(
			"SELECT [".$this->fieldLft."] AS [nodeLeft], ".
				"[".$this->fieldRgt."] AS [nodeRight], ".
				"[".$this->fieldLevel."] AS [nodeLevel], ".
				"[".$this->fieldRgt."]-[".$this->fieldLft."]+1 AS [nodeSize] ".
			"FROM [".$this->table."] ".
			"WHERE [id] = %i", $resultId);

		$query->setType('nodeLeft', Dibi::FIELD_INTEGER);
		$query->setType('nodeRight', Dibi::FIELD_INTEGER);
		$query->setType('nodeLevel', Dibi::FIELD_INTEGER);
		$query->setType('nodeSize', Dibi::FIELD_INTEGER);
		$result = $query->fetch();

		if($result === false)  {
			dibi::query("UNLOCK TABLES");
			throw new \NotFoundException("Record with id '$resultId' not found in table '$this->table'");
		}

		$query2 = dibi::query(
			"SELECT [".$this->fieldLft."] AS [targetLeft], ".
				"[".$this->fieldRgt."] AS [targetRight], ".
				"[".$this->fieldLevel."] AS [targetLevel], ".
				($method != self::MOVE_AFTER ? 0 : "[".$this->fieldRgt."]-[".$this->fieldLft."]+1")." AS [targetSize] ".
			"FROM [".$this->table."] ".
			"WHERE [id] = %i", $targetId);

		$query2->setType('targetLeft', Dibi::FIELD_INTEGER);
		$query2->setType('targetRight', Dibi::FIELD_INTEGER);
		$query2->setType('targetLevel', Dibi::FIELD_INTEGER);
		$query2->setType('targetSize', Dibi::FIELD_INTEGER);
		$result2 = $query2->fetch();

		if($result2 === false)  {
			dibi::query("UNLOCK TABLES");
			throw new \NotFoundException("Record with id '$targetId' not found in table '$this->table'");
		}

		if(($result2["targetLeft"] >= $result["nodeLeft"]) && ($result2["targetLeft"] <= $result["nodeRight"])) {
			dibi::query("UNLOCK TABLES");
			throw new \LogicException("Cannot move record with id '$resultId' under/before/after it's child '$targetId'");
		}

		dibi::begin();

		$mv = $method == self::MOVE_FRONT ? $result2["targetLeft"] - 1
			:( $method == self::MOVE_AFTER ? $result2["targetRight"] : $result2["targetLeft"]);

		dibi::query(
				"UPDATE [".$this->table."] SET [".$this->fieldLft."] = [".$this->fieldLft."] + %i", $result["nodeSize"],
				"WHERE [".$this->fieldLft."] > %i", $mv);

		dibi::query(
				"UPDATE [".$this->table."] SET [".$this->fieldRgt."] = [".$this->fieldRgt."] + %i", $result["nodeSize"],
				"WHERE [".$this->fieldRgt."] > %i", $mv);

		$mvConst = $method == self::MOVE_UNDER ? 1 : 0;
		$nSize = ($result["nodeLeft"] > $result2["targetLeft"]) ? $result["nodeSize"] : 0;
		$sum = 0 - $result["nodeLeft"] - $nSize + $result2["targetLeft"] + $result2["targetSize"] + $mvConst;

		dibi::query(
				"UPDATE [".$this->table."] SET [".$this->fieldLft."] = [".$this->fieldLft."] + %i,", $sum,
					  "[".$this->fieldRgt."] = [".$this->fieldRgt."] + %i,", $sum,
					  "[".$this->fieldLevel."] = [".$this->fieldLevel."] + %i",
					  $mvConst + ($result["nodeLevel"] > $result2["targetLevel"] ? 0 - $result["nodeLevel"] + $result2["targetLevel"] : $result2["targetLevel"] - $result["nodeLevel"]),
				"WHERE [".$this->fieldLft."] >= %i", $result["nodeLeft"] + $nSize,
					  "AND [".$this->fieldRgt."] <= %i", $result["nodeRight"] + $nSize);

		dibi::query(
				"UPDATE [".$this->table."] SET [".$this->fieldLft."] = [".$this->fieldLft."] - %i", $result["nodeSize"],
				"WHERE [".$this->fieldLft."] > %i", $result["nodeRight"] + $nSize);

		dibi::query(
				"UPDATE [".$this->table."] SET [".$this->fieldRgt."] = [".$this->fieldRgt."] - %i", $result["nodeSize"],
				"WHERE [".$this->fieldRgt."] > %i", $result["nodeRight"] + $nSize);

		dibi::commit();
		dibi::query("UNLOCK TABLES");
	}
	
	public function setFieldId($value) {
		$this->fieldId = (string) $value;
		return $this;
	}

	public function setFieldLft($value) {
		$this->fieldLft = (string) $value;
		return $this;
	}

	public function setFieldRgt($value) {
		$this->fieldRgt = (string) $value;
		return $this;
	}
	
	public function setFieldLevel($value) {
		$this->fieldLevel = (string) $value;
		return $this;
	}
	
	public function setTable($table) {
		$this->table = (string) $table;
		return $this;
	}
	
}
