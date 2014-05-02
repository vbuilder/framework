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

/**
 * Math library
 *
 * @author Adam Staněk (velbloud)
 * @since Jan 21, 2013
 */
class Math {

	/**
	 * Creates a distribution of total from given values.
	 * Algorithm is stable and does not change the array indexes.
	 * 		
	 * @see http://en.wikipedia.org/wiki/Largest_remainder_method
	 *
	 * @note Zero array will always come out as zero distribution
	 *			regardless of $forceCompleteDistribution parameter.
	 *
	 * @param array of values
	 * @param int number to ditribute
	 * @param bool true, if we allow unfair distribution
	 *	 			(same values of two items can get different portion just to distribute whole total,
	 *				 ex: [3, 3, 3, 0] => [34, 33, 33, 0] for 100)
	 *
	 * @return array
	 */
	public static function largestRemainderDistribution(array $data, $distributionTotal = 100, $forceCompleteDistribution = false) {
		
		$distribution = array();
		$total = array_sum($data);
		
		$hareQuota = $total / $distributionTotal;
		$remainders = array();
		foreach($data as $k => $d) {
			// Vypoctu fixni (celou) cast
			$portion = $hareQuota != 0 ? $d / $hareQuota : 0;
			$distribution[$k] = $fixed = floor($portion);
			$distributionTotal -= $fixed;
			
			// Poznamenam si zbytky
			// (array nesnese na klici desetinne cislo, tak si pomuzeme vynasobenim a oriznutim presnosti)
			$remainder = ($portion - $fixed) * 1000000;
			if(!isset($remainders[$remainder]))
				$remainders[$remainder] = array($k);
			else
				$remainders[$remainder][] = $k;
		}
		
		// Rozdelim zbyvajici cast podle velikosti zbytku
		krsort($remainders);
		foreach($remainders as $k=>$curr) {
			
			if($k != 0 && (count($curr) <= $distributionTotal || $forceCompleteDistribution)) {
				
				foreach($curr as $k) {
					if($distributionTotal-- == 0) break 2;
					$distribution[$k]++;
				}
				
			} else
				break;
		}
		
		return $distribution;
	}

}