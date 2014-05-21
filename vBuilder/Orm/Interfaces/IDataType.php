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

namespace vBuilder\Orm;

use vBuilder,
	 Nette;

/**
 * Interface for redaction data types
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 18, 2011
 */
interface IDataType {

	/**
	 * Constructor is mandatory and it takes reference to raw data
	 *
	 * @param string field name
	 * @param Entity instance
	 * @param Nette\DI\Container DI context
	 */
	public function __construct($fieldName, &$entity, Nette\DI\Container $context);

	/**
	 * Tries to convert value from setter
	 *
	 * @throws Nette\InvalidArgumentException if input is not convertible
	 */
	public function convertFrom(&$data);

	/**
	 * Returns array of names of all data types accepted by this class.
	 * Assumes PHP 5.3 (in earlier versions it should still work, but not if
	 * you subclass the class implementing this interface. In that case static function
	 * from first class will be loaded. Needs static late binding added in PHP 5.3)
	 *
	 * @return array of data type names
	 */
	public static function acceptedDataTypes();

	public function __toString();

}
