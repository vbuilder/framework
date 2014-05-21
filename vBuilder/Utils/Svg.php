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
 * Svg library
 *
 * @author Adam Staněk (velbloud)
 * @since Nov 16, 2012
 */
class Svg {
	
	/**
	 * Computes optimum height of SVG image scaled to width
	 * while maintaining original aspect ratio
	 *
	 * @param string filename
	 * @param int width
	 *
	 * @return int|null height (null if height cannot be determined)
	 * @throws Nette\InvalidArgumentException if file does not exist or width is invalid
	 * @throws Nette\IOException if file can't be read
	 */
	public static function computeHeight($filename, $desiredWidth) {
		if(!is_file($filename)) throw new Nette\InvalidArgumentException("File '$filename' does not exist");
		if(!is_readable($filename)) throw new Nette\InvalidArgumentException("File '$filename' cannot be read");
		
		$desiredWidth = (int) $desiredWidth;
		if($desiredWidth <= 0) throw new Nette\InvalidArgumentException("Invalid width given");
		
		$svg = new \SimpleXMLElement(file_get_contents($filename));
		
		if(isset($svg['width']) && isset($svg['height'])) {
			$w = (int) $svg['width'];
			$h = (int) $svg['height'];
			
			return (int) (($desiredWidth / $w) * $h);
		}
		
		// TODO:
		/* elseif(isset($svg['viewBox'])) {
			
		} */
		
		return NULL;
	}

}