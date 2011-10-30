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

use vBuilder,
		Nette;

/**
 * Routines to correctly determine production / debug mode.
 * 
 * Please do not use this class for detection if you are not member of V3Net.cz.
 * This detector is meant for internal purposes only. Integrated Nette detector
 * might be a better solution for you.
 * 
 * @internal
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 30, 2011
 */
class ProductionModeDetector {
	
	/** @var array of addtional developer machines (IPs) */
	public static $developerHosts = array(
			
			'178.248.250.48'	// V3Net.cz - VPN Gateway
			
	);
	
	/**
	 * Returns true, if viewer is classified as normal user (non-developer).
	 * If missing appropriate headers, null is returned.
	 * 
	 * @return bool|null
	 */
	static public function isInProductionMode() {
		if(isset($_SERVER['REMOTE_ADDR'])) {
			// Prozatim nas zajima jen remote strana
			// TODO: doplnit i o zbytek
			$addr = $_SERVER['REMOTE_ADDR'];

			// Lokalni site
			$oct = explode('.', $addr);
			if (!($addr !== '::1' && (count($oct) !== 4 || ($oct[0] !== '10' && $oct[0] !== '127' && ($oct[0] !== '172' || $oct[1] < 16 || $oct[1] > 31)
								&& ($oct[0] !== '169' || $oct[1] !== '254') && ($oct[0] !== '192' || $oct[1] !== '168')))
							) ) {
				
				return false;


			// VPN Gateway
			} elseif(in_array($addr, self::$developerHosts)) {
				
				return false;
			}

			// Cokoliv nelokalniho povazujeme za bezneho navstevnika => production
			return true;
		}  
		
		return null;
	}
	
}
