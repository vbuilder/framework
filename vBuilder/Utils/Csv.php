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
 * Csv library
 *
 * @author Adam Staněk (velbloud)
 * @since Nov 9, 2012
 */
class Csv {

	/**
	 * Converts 2 dimensional data into CSV formatted string
	 * 
	 * @param array|Traversable data
	 * @param column separator
	 * @param line ending
	 */
	public static function fromData($data, $separator = ',', $lineEnding = "\n") {
		if(!is_array($data) && !($data instanceof Traversable))
			throw new Nette\InvalidArgumentException("Data has to be 2 dimensional array or Traversable object, " . (is_object($data) ? get_class($data) : gettype($data)) . " given.");

		$output = "";
		$lineSep = false;

		foreach($data as $line) {
			if(!is_array($line) && !($line instanceof Traversable))
				throw new Nette\InvalidArgumentException("Data has to be 2 dimensional array or Traversable object, " . (is_object($line) ? get_class($line) : gettype($line)) . " has been read as line.");

			if($lineSep) $output .= $lineEnding;
			else $lineSep = true;

			$fieldSep = false;
			foreach($line as $field) {
				$needQuote = false;
				for($i = 0; $i < strlen($field); $i++) {
					if(in_array($field[$i], array($separator, "\"", "\r", "\n"))) {
						$needQuote = true;
						break;
					}
				}

				if($fieldSep) $output .= $separator;
				else $fieldSep = true;

				$output .= ($needQuote) ? "\"".str_replace("\"", "\"\"", $field)."\"" : $field;
			}
		}

		return $output;
	}

}