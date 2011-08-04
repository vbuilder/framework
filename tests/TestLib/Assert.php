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

use TestCase, Exception;

/**
 * Assert class based on Nette Assert (c & p because of fucking private methods)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 3, 2011
 */
class Assert {

	/**
	 * Returns message and file and line from call stack.
	 * @param  string
	 * @return void
	 */
	private static function doFail($message) {
		global $bluescreen;

		$trace = debug_backtrace();
		if(defined('HTTP_TEST') && HTTP_TEST) array_pop($trace);
		$last = end($trace);
		
		if(isset($last['line'])) {
			if($bluescreen = BlueScreen::getInstance()) {
				$bluescreen->setMessage($message);
				$bluescreen->setFileBrowser($last['file'], $last['line']);
			}
			
			$message .= " on line $last[line]";
		}
		
		if($bluescreen = BlueScreen::getInstance()) {
			$bluescreen->render();
		} else {
			echo "\n$message";
		}
		
		exit(TestCase::CODE_FAIL); 
	}
	
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
		} else {
			return self::netteException($class, $message, $actual);
		}
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
			if($bluescreen = BlueScreen::getInstance()) {
				$bluescreen->setAssertVariables($expected, $actual);
			} else {
				echo "EXPECTED: \n";
				dump($expected);

				echo "ACTUAL: \n";
				dump($actual);
			}

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
		if($maxDepth < 0)
			return true;

		// Predpoklada se, ze kdyz je Traversable, tak je i Countable
		if(count($expected) != count($actual))
			return false;

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

				if(!$found)
					return false;
			}
		}

		return true;
	}

	// <editor-fold defaultstate="collapsed" desc="Nette methods">
	
	/**
	 * Checks assertion.
	 * @param  mixed  expected
	 * @param  mixed  actual
	 * @return void
	 */
	public static function same($expected, $actual) {
		if($actual !== $expected) {
			if($bluescreen = BlueScreen::getInstance())
				$bluescreen->setAssertVariables($expected, $actual);
			
			self::log($expected, $actual);
			self::doFail('Failed asserting that '.self::dump($actual).' is identical to expected '.self::dump($expected));
		}
	}

	/**
	 * Checks assertion.
	 * @param  mixed  expected
	 * @param  mixed  actual
	 * @return void
	 */
	public static function equal($expected, $actual) {
		if($actual != $expected) {
			if($bluescreen = BlueScreen::getInstance())
				$bluescreen->setAssertVariables($expected, $actual);
			
			self::log($expected, $actual);
			self::doFail('Failed asserting that '.self::dump($actual).' is equal to expected '.self::dump($expected));
		}
	}

	/**
	 * Checks exception assertion.
	 * @param  string class
	 * @param  string message
	 * @param  Exception
	 * @return void
	 */
	protected static function netteException($class, $message, $actual) {
		if(!($actual instanceof $class)) {
			self::doFail('Failed asserting that '.get_class($actual)." is an instance of class $class");
		}
		if($message) {
			self::match($message, $actual->getMessage());
		}
	}

	/**
	 * Checks TRUE assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function true($actual) {
		if($actual !== TRUE) {
			if($bluescreen = BlueScreen::getInstance())
				$bluescreen->setAssertVariables(true, $actual);
			
			self::doFail('Failed asserting that '.self::dump($actual).' is TRUE');
		}
	}

	/**
	 * Checks FALSE assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function false($actual) {
		if($actual !== FALSE) {
			if($bluescreen = BlueScreen::getInstance())
				$bluescreen->setAssertVariables(false, $actual);
			
			self::doFail('Failed asserting that '.self::dump($actual).' is FALSE');
		}
	}

	/**
	 * Checks NULL assertion.
	 * @param  mixed  actual
	 * @return void
	 */
	public static function null($actual) {
		if($actual !== NULL) {
			if($bluescreen = BlueScreen::getInstance())
				$bluescreen->setAssertVariables(null, $actual);
			
			self::doFail('Failed asserting that '.self::dump($actual).' is NULL');
		}
	}

	/**
	 * Checks if the function throws exception.
	 * @param  callback
	 * @param  string class
	 * @param  string message
	 * @return void
	 */
	public static function throws($function, $class, $message) {
		try {
			call_user_func($function);
			self::doFail('Expected exception');
		} catch(Exception $e) {
			Assert::exception($class, $message, $e);
		}
	}

	/**
	 * Failed assertion
	 * @return void
	 */
	public static function fail($message) {
		self::doFail($message);
	}

	/**
	 * Initializes shutdown handler.
	 * @return void
	 */
	public static function handler($handler) {
		ob_start();
		register_shutdown_function($handler);
	}

	/**
	 * Compares results using mask:
	 *   %a%    one or more of anything except the end of line characters
	 *   %a?%   zero or more of anything except the end of line characters
	 *   %A%    one or more of anything including the end of line characters
	 *   %A?%   zero or more of anything including the end of line characters
	 *   %s%    one or more white space characters except the end of line characters
	 *   %s?%   zero or more white space characters except the end of line characters
	 *   %S%    one or more of characters except the white space
	 *   %S?%   zero or more of characters except the white space
	 *   %c%    a single character of any sort (except the end of line)
	 *   %d%    one or more digits
	 *   %d?%   zero or more digits
	 *   %i%    signed integer value
	 *   %f%    floating point number
	 *   %h%    one or more HEX digits
	 *   %ns%   PHP namespace
	 *   %[..]% reg-exp
	 * @param  string
	 * @param  string
	 * @return bool
	 */
	public static function match($expected, $actual) {
		$expected = rtrim(preg_replace("#[\t ]+\n#", "\n", str_replace("\r\n", "\n", $expected)));
		$actual = rtrim(preg_replace("#[\t ]+\n#", "\n", str_replace("\r\n", "\n", $actual)));

		$re = strtr($expected, array(
			 '%a%' => '[^\r\n]+', // one or more of anything except the end of line characters
			 '%a?%' => '[^\r\n]*', // zero or more of anything except the end of line characters
			 '%A%' => '.+', // one or more of anything including the end of line characters
			 '%A?%' => '.*', // zero or more of anything including the end of line characters
			 '%s%' => '[\t ]+', // one or more white space characters except the end of line characters
			 '%s?%' => '[\t ]*', // zero or more white space characters except the end of line characters
			 '%S%' => '\S+', // one or more of characters except the white space
			 '%S?%' => '\S*', // zero or more of characters except the white space
			 '%c%' => '[^\r\n]', // a single character of any sort (except the end of line)
			 '%d%' => '[0-9]+', // one or more digits
			 '%d?%' => '[0-9]*', // zero or more digits
			 '%i%' => '[+-]?[0-9]+', // signed integer value
			 '%f%' => '[+-]?\.?\d+\.?\d*(?:[Ee][+-]?\d+)?', // floating point number
			 '%h%' => '[0-9a-fA-F]+', // one or more HEX digits
			 '%ns%' => '(?:[_0-9a-zA-Z\\\\]+\\\\|N)?', // PHP namespace
			 '%ds%' => '[\\\\/]', // directory separator
			 '%[^' => '[^', // reg-exp
			 '%[' => '[', // reg-exp
			 ']%' => ']+', // reg-exp

			 '.' => '\.', '\\' => '\\\\', '+' => '\+', '*' => '\*', '?' => '\?', '[' => '\[', '^' => '\^', // preg quote
			 ']' => '\]', '$' => '\$', '(' => '\(', ')' => '\)', '{' => '\{', '}' => '\}', '=' => '\=', '!' => '\!',
			 '>' => '\>', '<' => '\<', '|' => '\|', ':' => '\:', '-' => '\-', "\x00" => '\000', '#' => '\#',
				  ));

		$old = ini_set('pcre.backtrack_limit', '1000000');
		$res = preg_match("#^$re$#s", $actual);
		ini_set('pcre.backtrack_limit', $old);
		if($res === FALSE || preg_last_error()) {
			throw new Exception("Error while executing regular expression.");
		}
		if(!$res) {
			self::log($expected, $actual);
			self::doFail('Failed asserting that '.self::dump($actual).' matches expected '.self::dump($expected));
		}
	}



	/**
	 * Dumps information about a variable in readable format.
	 * @param  mixed  variable to dump
	 * @return void
	 */
	private static function dump($var) {
		static $tableUtf, $tableBin, $reBinary = '#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';
		if($tableUtf === NULL) {
			foreach(range("\x00", "\xFF") as $ch) {
				if(ord($ch) < 32 && strpos("\r\n\t", $ch) === FALSE) {
					$tableUtf[$ch] = $tableBin[$ch] = '\\x'.str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
				} elseif(ord($ch) < 127) {
					$tableUtf[$ch] = $tableBin[$ch] = $ch;
				} else {
					$tableUtf[$ch] = $ch;
					$tableBin[$ch] = '\\x'.dechex(ord($ch));
				}
			}
			$tableBin["\\"] = '\\\\';
			$tableBin["\r"] = '\\r';
			$tableBin["\n"] = '\\n';
			$tableBin["\t"] = '\\t';
			$tableUtf['\\x'] = $tableBin['\\x'] = '\\\\x';
		}

		if(is_bool($var)) {
			return $var ? 'TRUE' : 'FALSE';
		} elseif($var === NULL) {
			return "NULL";
		} elseif(is_int($var)) {
			return "$var";
		} elseif(is_float($var)) {
			return "$var";
		} elseif(is_string($var)) {
			if($cut = @iconv_strlen($var, 'UTF-8') > 100) {
				$var = iconv_substr($var, 0, 100, 'UTF-8');
			} elseif($cut = strlen($var) > 100) {
				$var = substr($var, 0, 100);
			}
			return '"'.strtr($var, preg_match($reBinary, $var) || preg_last_error() ? $tableBin
									 : $tableUtf).'"'.($cut ? ' ...' : '');
		} elseif(is_array($var)) {
			return "array(".count($var).")";
		} elseif($var instanceof Exception) {
			return 'Exception '.get_class($var).': '.($var->getCode() ? '#'.$var->getCode().' '
						  : '').$var->getMessage();
		} elseif(is_object($var)) {
			$arr = (array) $var;
			return "object(".get_class($var).") (".count($arr).")";
		} elseif(is_resource($var)) {
			return "resource(".get_resource_type($var).")";
		} else {
			return "unknown type";
		}
	}

	/**
	 * Logs big variables to file.
	 * @param  mixed
	 * @param  mixed
	 * @return void
	 */
	private static function log($expected, $actual) {
		// Webove testy nemaji prava k zapisu
		if(defined('HTTP_TEST') && HTTP_TEST) return ;
		
		$trace = debug_backtrace();
		$item = end($trace);
		// in case of shutdown handler, we want to skip inner-code blocks
		// and debugging calls (e.g. those of Nette\Diagnostics\Debugger)
		// to get correct path to test file (which is the only purpose of this)
		while(!isset($item['file']) || substr($item['file'], -5) !== '.phpt') {
			$item = prev($trace);
			if($item === FALSE) {
				return;
			}
		}
		$file = dirname($item['file']).'/output/'.basename($item['file'], '.phpt');

		if(is_object($expected) || is_array($expected) || (is_string($expected) && strlen($expected) > 100)) {
			@mkdir(dirname($file)); // @ - directory may already exist
			file_put_contents($file.'.expected', is_string($expected) ? $expected : var_export($expected, TRUE));
		}

		if(is_object($actual) || is_array($actual) || (is_string($actual) && strlen($actual) > 100)) {
			@mkdir(dirname($file)); // @ - directory may already exist
			file_put_contents($file.'.actual', is_string($actual) ? $actual : var_export($actual, TRUE));
		}
	}
	
	// </editor-fold>

}
