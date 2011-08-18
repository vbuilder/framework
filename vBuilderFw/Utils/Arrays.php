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

/**
 * Description of Arrays
 *
 * @author Adam Staněk (velbloud)
 * @since Jul 26, 2011
 */
class Arrays {

	/**
	 * Inserts item at specified position in the array
	 * 
	 * @param array reference to array
	 * @param mixed item to insert
	 * @param int offset from 0
	 */
	static function insertAt(array &$array, $value, $offset) {
		if(is_array($array)) {
			$array = array_values($array);
			$offset = intval($offset);
			if($offset < 0 || $offset >= count($array)) {
				array_push($array, $value);
			} elseif($offset == 0) {
				array_unshift($array, $value);
			} else {
				$temp = array_slice($array, 0, $offset);
				array_push($temp, $value);
				$array = array_slice($array, $offset);
				$array = array_merge($temp, $array);
			}
		} else {
			$array = array($value);
		}
	}

}
