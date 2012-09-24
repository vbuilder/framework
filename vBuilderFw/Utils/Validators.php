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
 * Validation routines
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 2, 2012
 */
class Validators {
	
	/**
	 * Finds whether a given value is valid IN (IČ) of
	 * company / subject. Algorithm was originally written by David Grudl
	 * and extended for allowing INs without leading zeros.
	 *
	 * @param string input
	 * @return bool
	 *
	 * @author David Grudl
	 * @see http://latrine.dgx.cz/jak-overit-platne-ic-a-rodne-cislo
	 */
	static function isCzechSubjectIn($ic) {
		// "be liberal in what you receive"
		$ic = preg_replace('#\s+#', '', $ic);
		
		if(strlen($ic) > 8) return false;
		elseif(strlen($ic) < 8) $ic = str_pad($ic, 8, '0', STR_PAD_LEFT);
				
		// kontrolní součet
		$a = 0;
		for ($i = 0; $i < 7; $i++) {
			$a += $ic[$i] * (8 - $i);
		}
		
		$a = $a % 11;
		
		if ($a === 0) $c = 1;
		elseif ($a === 10) $c = 1;
		elseif ($a === 1) $c = 0;
		else $c = 11 - $a;
		
		return (int) $ic[7] === $c;
	}
	
	/**
	 * Finds whether a given value is valid national IN
	 * (rodné číslo)
	 *
	 * @param string input
	 * @return bool
	 *
	 * @author David Grudl
	 * @see http://latrine.dgx.cz/jak-overit-platne-ic-a-rodne-cislo
	 */
	static function isCzechNationalIn() {
		// "be liberal in what you receive"
		if (!preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches)) {
			return FALSE;
		}
		
		list(, $year, $month, $day, $ext, $c) = $matches;
		
		// do roku 1954 přidělovaná devítimístná RČ nelze ověřit
		if ($c === '') {
			return $year < 54;
		}
		
		// kontrolní číslice
		$mod = ($year . $month . $day . $ext) % 11;
		if ($mod === 10) $mod = 0;
		if ($mod !== (int) $c) {
			return FALSE;
		}
		
		// kontrola data
		$year += $year < 54 ? 2000 : 1900;
		
		// k měsíci může být připočteno 20, 50 nebo 70
		if ($month > 70 && $year > 2003) $month -= 70;
		elseif ($month > 50) $month -= 50;
		elseif ($month > 20 && $year > 2003) $month -= 20;
		
		if (!checkdate($month, $day, $year)) {
			return FALSE;
		}
		
		// cislo je OK
		return TRUE;
	}

}
