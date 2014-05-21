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

namespace vBuilder;

/**
 * Static class for providing basic info about vBuilder FW
 * 
 * @author Adam Staněk (V3lbloud)
 */
class Framework {
	/**#@+ vBuilder Framework version identification */
	const NAME = 'vBuilder Framework';

	// v2.50.00pre
	const VERSION = 25000; 
	const VERSION_TAG = 'pre';

	const REVISION = '$WCREV$';
	const RELEASE_DATE = '$WCDATE$';
	/**#@-*/
	
	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct() {
		throw new \LogicException("Cannot instantiate static class " . get_class($this));
	}
	
	/**
	 * Returns version identification string in human readable format.
	 * 
	 * @param int $number
	 * @param string $tag
	 * 
	 * @return string formated string
	 */
	public static function getVersionString($number = self::VERSION, $tag = self::VERSION_TAG) {
		$major = floor($number / 10000);
		$minor = floor($number / 100) - $major * 100;
		$decimal = $number - 10000 * $major - 100 * $minor;
		
		return $decimal == 0 
			? "$major.".self::formatVersionMinor($minor).$tag
			: "$major.".self::formatVersionMinor($minor).".".self::formatVersionMinor($decimal, false).$tag;
	}
	
	/**
	 * Helps format version minor numbers.
	 * 
	 * @param int $num version minor number
	 * @param bool $shorten10 true, if you want to shorten for example version 2.10 to 2.1
	 * 
	 * @return string formated string
	 */
	private static function formatVersionMinor($num, $shorten10 = true) {
		if($num <= 0) return 0; 
		else if($num < 10) return "0$num";
		else if($num % 10 == 0 && $shorten10) return $num / 10;
		else return $num;
	}
	
}