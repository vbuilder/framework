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
	Traversable;

/**
 * LDAP routines
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class Ldap {

	/**
	 * Parses and simplifies LDAP entries into associative array
	 *
	 * @param array
	 * @return array
	 */
	public static function entriesToStructure(array $data) {
		$parsed = array();

		if(isset($data['dn']))
			$parsed['dn'] = $data['dn'];

		$assoc = false;
		for($i = 0; $i < $data['count']; $i++) {
			if(is_string($data[$i]) && isset($data[$data[$i]])) {
				$key = $data[$i];
				$value = $data[$data[$i]];
				$assoc = true;
			} else {
				$key = $i;
				$value = $data[$i];
			}

			$parsed[$key] = is_array($value) ? self::entriesToStructure($value) : $value;
		}

		return !$assoc && $data['count'] == 1 ? $parsed[0] : $parsed;
	}

}