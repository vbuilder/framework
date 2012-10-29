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

use Nette;

/**
 * Network library
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 24, 2012
 */
class Network {
	
	/**
	 * Converts IPv4 address into 4 byte integer
	 *
	 * @param string
	 * @return int
	 */
	public static function ip4ToInt($ipAddr) {
		$tokens = explode('.', $ipAddr);

		$byteString = pack('C*', $tokens[3], $tokens[2], $tokens[1], $tokens[0]);
		$data = unpack('I', $byteString);

		return $data[1];
	}

	/**
	 * Converts 4 byte integer into IP address string
	 *
	 * @param int
	 * @return string|NULL
	 */
	public static function intToIp4($number) {
		$byteString = pack('I', $number);
		$tokens = unpack('C*', $byteString);

		return count($tokens) != 4
					? NULL
					: implode(array_reverse($tokens), '.');
	}

}