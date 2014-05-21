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
 * Routines for color generation
 *
 * @author Adam Staněk (velbloud)
 * @since Apr 13, 2012
 */
class ColorGenerator {

	const DEFAULT_PALETTE = -1;

	private static $defaultPalette;
	private static $palette = array(
		'default_text_color' => array(
			'#4572A7', 
			'#AA4643', 
			'#89A54E', 
			'#80699B', 
			'#3D96AE', 
			'#DB843D'),

		'google_chart_color' => array(
			'#3366cc',
			'#dc3912',
			'#ff9900',
			'#109618',
			'#990099',
			'#0099c6',
			'#dd4477',
			'#66aa00',
			'#b82e2e',
			'#316395',
			'#994499',
			'#22aa99',
			'#aaaa11',
			'#6633cc',
			'#e67300',
			'#8b0707',
			'#651067',
			'#329262',
			'#5574a6',
			'#3b3eac',
			'#b77322',
			'#16d620',
			'#b91383',
			'#f4359e',
			'#9c5935',
			'#a9c413',
			'#2a778d',
			'#668d1c',
			'#bea413',
			'#0c5922',
			'#743411'
		)
	);

	/**
	 * Installs new color palette for generator
	 *
	 * @param string palette name
	 * @param array colors in hexadecimal format (including hash)
	 * @param bool if true, palette will be installed as new default
	 */
	public static function installPalette($name, array $colors, $asDefault = false) {
		if(count($colors) == 0)
			throw new Nette\InvalidArgumentException("Palette has to contain at least one color");
			
		if($asDefault) self::$defaultPalette = $name;
		
		self::$palette[$name] = $colors;
	}
	
	/**
	 * Returns color for value. Function guarantees that same value, will get always the same color
	 * in palette.
	 *
	 * @param mixed value
	 * @param string name of palette
	 *
	 * @return color in hexadecimal format (including hash)
	 */
	public static function getColorForValue($value, $palette = self::DEFAULT_PALETTE) {
		
		if(is_integer($value)) $number = (int) $value;
		elseif(is_float($value)) $number = floor($value);
		else {
			$hash = md5($value);
			$number = intval(mb_substr($hash, -6, 6), 16);
		}
		
		if($palette == self::DEFAULT_PALETTE) $palette = self::getDefaultPaletteName();
		if(!isset(self::$palette[$palette]))
			throw new Nette\InvalidStateException("Palette named " . var_export($palette, true) . ' does not exist');
		
		return self::$palette[$palette][$number % count(self::$palette[$palette])];
	} 


	private static function getDefaultPaletteName() {
		return isset(self::$defaultPalette) ? self::$defaultPalette : 'default_text_color';
	}


}