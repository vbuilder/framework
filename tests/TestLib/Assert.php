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

namespace vBuilder\Test;

/**
 * Assert class based on Nette Assert
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 3, 2011
 */
class Assert extends \Assert {

	/**
	 * Checks exception assertation.
	 * @param  string class
	 * @param  string message
	 * @param  int code
	 * @param  Exception
	 * @return void
	 */
	public static function exception($class, $message, $actual) {
		if(func_num_args() > 3) {
			// Nemuzu zmenit hlavicku funkce kvuli Strictu, takze to musim obejit
			// pres dynamicke parametry
			list($class, $message, $code, $actual) = func_get_args();
			
			if(is_object($code)) {
				$actual = $code;
				$code == null;
			}

			if(!($actual instanceof $class)) {
				self::fail('Failed asserting that '.get_class($actual)." is an instance of class $class");
			}

			if($message) {
				self::match($message, $actual->getMessage());
			}

			if($code) {
				if($actual->getCode() !== $code) {
					self::fail('Failed asserting that exception '.get_class($actual).' with code '.$actual->getCode().' has code '.$code);
				}
			}
			
		} else
			return parent::exception($class, $message, $actual);
	}
	
	/**
	 * Chacks if associative array is equal to another. Order doesn't matter.
	 * It also accepts Traversable so DibiRow is Ok.
	 * 
	 * @param array|Traversable $expected
	 * @param array|Traversable $actual 
	 * @param int maximum depth for chekcing of nested arrays
	 */
	public static function arrayEqual($expected, $actual, $maxDepth = 5) {
		if(!is_array($actual) && !($actual instanceof \Traversable))
			self::fail("Failed asserting that $actual is array equal to $expected: Data given are not even an array");
		
		if(!self::arrayEqualHelper($expected, $actual, $maxDepth)) {			
			echo "EXPECTED: \n";
			dump($expected);
			
			echo "ACTUAL: \n";
			dump($actual); 
			
			self::fail("Failed asserting that $actual is array equal to $expected");
		}
	}
	
	/**
	 * Chacks if associative array is equal to another. Order doesn't matter.
	 * It is helper function for arrayEqual which doesn't throw exception but return
	 * boolean instead.
	 * 
	 * @param array|Traversable $expected
	 * @param array|Traversable $actual
	 * @param int maximum depth for chekcing of nested arrays
	 * 
	 * @return bool true if equal
	 */
	private static function arrayEqualHelper($expected, $actual, $maxDepth) {
		// Pokud jsem prekrocil maximalni hloubku
		if($maxDepth < 0) return true;
		
		// Predpoklada se, ze kdyz je Traversable, tak je i Countable
		if(count($expected) != count($actual)) return false;
		
		// Zjistim, jestli pole je asociativni
		// Bacha na to, ze to nezohlednuje diry apod, ale nemelo by to vadit
		if(array_keys($expected) !== range(0, count($expected) - 1)) {
			foreach($expected as $key => $value) {
				if(!array_key_exists($key, $actual)) {
					//echo "Missing key $key\n";
					return false;
				}
								
				if(is_array($value) || $value instanceof \Traversable) {
					if(!self::arrayEqualHelper($value, $actual[$key], $maxDepth - 1))
						return false;
				} else if($value != $actual[$key]) {
					//echo var_export($value, true) . ' != ' . var_export($actual[$key], true) . "\n";
					return false;
				}
			}			
			
		// Pole neni asociativni
		} else {
			foreach($expected as $value) {				
				$found = false;
				foreach($actual as $value2) {				
					if(is_array($value) || $value instanceof \Traversable) {
						if(self::arrayEqualHelper($value, $value2, $maxDepth - 1)) {
							$found = true;
							break;
						}
					} else if($value == $value2) {
						$found = true;
						break;
					}
				}
				
				if(!$found) return false;
			}
		}
		
		return true;
	}

}
