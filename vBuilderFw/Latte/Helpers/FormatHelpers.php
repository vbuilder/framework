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

namespace vBuilder\Latte\Helpers;

/**
 * Latte template helpers for formating output
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 14, 2011
 */
class FormatHelpers {

	public static function currency($value, $decimals = false) {
		return str_replace(" ", "\xc2\xa0", number_format($value, $decimals ? 2 : 0, ",", " "))."\xc2\xa0Kč";
	}
	
	public static function fileSize($bytes) {
		$unim = array("B", "KB", "MB", "GB", "TB", "PB");
		$c = 0;

		while(($bytes >= 1000) && ($c < count($unim) - 1)) {
			$c++;
			$bytes = $bytes / 1000;
		}

		return number_format($bytes, ($c ? 1 : 0), ",", " ")." ".$unim[$c];
	}

}