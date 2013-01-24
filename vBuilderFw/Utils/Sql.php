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

namespace vBuilder\Utils;

use Nette,
	DibiConnection;

/**
 * SQL routines
 *
 * @author Adam Staněk (velbloud)
 * @since Jan 22, 2013
 */
class Sql {

	/**
	 * Returns array of possible values allowed by field set
	 * 
	 * @param  DibiConnection connection
	 * @param  string table name
	 * @param  string field (column) name
	 * @return array
	 */
	public static function getFieldSet(DibiConnection $conn, $table, $field) {
		$setColumn = $conn->query("SHOW COLUMNS FROM [$table] LIKE %s", $field)->fetch();
		
		if($setColumn === FALSE)
			throw new Nette\InvalidStateException("No '$field' column in the table '$table'");
		
		return self::parseSetValues($setColumn['Type']);
	}

	/**
	 * Alters table so that existing field set will contain all existing
	 * and new values.
	 * 
	 * @param  DibiConnection connection
	 * @param  string table name
	 * @param  string field (column) name
	 * @param  array with set values
	 * @param  boolean true if we don't want to remove existing set values with occurence
	 */
	public static function alterFieldSet(DibiConnection $conn, $table, $field, array $newValues, $safeRemove = true) {
	
		if(count($newValues) == 0)
			throw new Nette\InvalidArgumentException("No flags given. Array needs to contain at least one flag");
	
		$conn->query("LOCK TABLES", $table, "WRITE");
		$setColumn = $conn->query("SHOW COLUMNS FROM [$table] LIKE %s", $field)->fetch();
		
		if($setColumn === FALSE)
			throw new Nette\InvalidStateException("No '$field' column in the table '$table'");
		
		if($safeRemove) {
			$oldValues = self::parseSetValues($setColumn['Type']);
			$valuesToDelete = array_diff($oldValues, $newValues);
			if(count($valuesToDelete) > 0) {
				$fluent = $conn->select(false)->from($table);
				foreach($valuesToDelete as $f) $fluent->select("COUNT(IF(FIND_IN_SET(%s, [$field]) > 0, 1, NULL))", $f)->as("[$f]");
				
				foreach($fluent->fetch() as $f => $usage) {
					if($usage > 0) {
						$newValues[] = $f;
					}
				}
			}
		}
		
		$default =  $setColumn['Default'] && in_array($setColumn['Default'], $newValues)
				? $setColumn['Default'] : NULL;
		
		$query = "ALTER TABLE [$table] MODIFY [$field] SET%l";
		if($setColumn['Null'] == 'NO') $query .= ' NOT NULL';
		$query .= ' DEFAULT %s';
				
		$conn->query($query, $newValues, $default);
				
		$conn->query("UNLOCK TABLES");
	}
	
	/**
	 * Parses SQL column type string to array of set values
	 * 
	 * @param  string
	 * @return array
	 */
	private static function parseSetValues($setStr) {
		$setStr = mb_substr($setStr, 5, -2);
		return preg_split("/','/", $setStr);
	}

}