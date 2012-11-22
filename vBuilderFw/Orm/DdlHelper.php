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

namespace vBuilder\Orm;

use vBuilder,
	Nette,
	DibiConnection;

/**
 * SQL DDL generator
 *
 * @warning This class is just a simple helper. It's not meant to do
 * 		any complex DDL generation. Always check the generated scripts for
 *		errors and modify them if necessary!
 *
 * All generated scripts are designed for MySQL > 5.0.
 *
 * @author Adam Staněk (velbloud)
 * @since Nov 22, 2012
 */
class DdlHelper {

	/**
	 * Generates CREATE DDL query for given entity
	 *
	 * @param IEntityMetadata metadata
	 * @return string
	 */
	static function createQuery(IEntityMetadata $metadata) {

		$ddl = "CREATE TABLE `" . $metadata->getTableName() . "` (\n\t";

		$fDdl = array();
		foreach($metadata->getFields() as $fName) {
	
			$fDdlStr = self::getFieldDefinition($metadata, $fName);		
			$fDdl[] = $fDdlStr;
		}

		if(count($metadata->getIdFields())) {
			$pkStr = 'PRIMARY KEY(`';
			foreach($metadata->getIdFields() as $fName) {
				$cName = $metadata->getFieldColumn($fName);
				$pkStr .= $cName . '`, `';
			}

			$fDdl[] = substr($pkStr, 0, -3) . ')';
		}

		$ddl .= implode($fDdl, ",\n\t") . "\n\n";
		$ddl .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;\n";

		return $ddl;
	}

	/**
	 * Generates ALTER DDL query based on
	 * current table state in given DB connection
	 *
	 * @param IEntityMetadata metadata
	 * @param DibiConnection database connection
	 *
	 * @return string|null
	 */
	static function alterQuery(IEntityMetadata $metadata, DibiConnection $db) {
		// It there is no table name => no alter query
		if($metadata->getTableName() == "")
			return NULL;

		// If table does not exist => no alter query
		if($db->query("SHOW TABLES LIKE %s", $metadata->getTableName())->fetch() === FALSE)
			return NULL;

		$columns = $db->query("SHOW COLUMNS FROM %n", $metadata->getTableName())->fetchAssoc('Field');

		$ddl = '';
		foreach($metadata->getFields() as $fName) {
			$cName = $metadata->getFieldColumn($fName);

			// New columns
			if(!isset($columns[$cName])) {
				$ddl .= "ALTER TABLE `" . $metadata->getTableName() . "` ";
				$ddl .= "ADD COLUMN ";
				$ddl .= self::getFieldDefinition($metadata, $fName);
				$ddl .= ";\n";
			}
		}

		return $ddl ?: NULL;
	}

	/**
	 * Returns full column definition (name + type + defaults, specials, ...)
	 *
	 * @param IEntityMetadata metadata
	 * @param string field name
	 *
	 * @return string
	 */
	private static function getFieldDefinition(IEntityMetadata $metadata, $fName) {
		$cName = $metadata->getFieldColumn($fName);
		$fType = $metadata->getFieldType($fName);
		$isPk = in_array($fName, $metadata->getIdFields());

		$fDdlStr = "`$cName` ";
		$fDdlStr .= self::trFieldType($fType);

		if($isPk && $metadata->isFieldGenerated($fName) && $fType == 'integer')
			$fDdlStr .= " unsigned";

		if($isPk || $fType == 'string') 
			$fDdlStr .= " NOT NULL";

		if($fType == 'string')
			$fDdlStr .= " DEFAULT ''";		

		if($isPk && $metadata->isFieldGenerated($fName) && $fType == 'integer')
			$fDdlStr .= " auto_increment";

		return $fDdlStr;
	}

	/**
	 * Translates ORM type into SQL type
	 *
	 * @param string
	 * @return string
	 */
	private static function trFieldType($type) {
		switch(strtolower($type)) {
			case "integer":
				return "int";
				
			case "float":
				return "float";
				
			case "datetime":
				return "datetime";
				
			case "date":
				return "date";
			
			case "text":
			case "redactiontemplate":
				return "text";

			default:
				return "varchar(256)";
		}
	}

}