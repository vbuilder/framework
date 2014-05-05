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

namespace vBuilder\Diagnostics;

use Nette,
	Nette\Diagnostics\Debugger;

/**
 * Rendering helpers for Debugger.
 * Based on Nette\Diagnostics\Helpers class which is of course final...
 *
 * @since Jan 21, 2013
 */
class Helpers {

	/**
	 * Internal dump() implementation.
	 * @param  mixed  variable to dump
	 * @param  int    current recursion level
	 * @return string
	 */
	public static function htmlDump(&$var, $level = 0)
	{
		static $tableUtf, $tableBin, $reBinary = '#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';
		if ($tableUtf === NULL) {
			foreach (range("\x00", "\xFF") as $ch) {
				if (ord($ch) < 32 && strpos("\r\n\t", $ch) === FALSE) {
					$tableUtf[$ch] = $tableBin[$ch] = '\\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
				} elseif (ord($ch) < 127) {
					$tableUtf[$ch] = $tableBin[$ch] = $ch;
				} else {
					$tableUtf[$ch] = $ch; $tableBin[$ch] = '\\x' . dechex(ord($ch));
				}
			}
			$tableBin["\\"] = '\\\\';
			$tableBin["\r"] = '\\r';
			$tableBin["\n"] = '\\n';
			$tableBin["\t"] = '\\t';
			$tableUtf['\\x'] = $tableBin['\\x'] = '\\\\x';
		}

		if (is_bool($var)) {
			return '<span class="php-bool">' . ($var ? 'TRUE' : 'FALSE') . "</span>\n";

		} elseif ($var === NULL) {
			return "<span class=\"php-null\">NULL</span>\n";

		} elseif (is_int($var)) {
			return "<span class=\"php-int\">$var</span>\n";

		} elseif (is_float($var)) {
			$var = var_export($var, TRUE);
			if (strpos($var, '.') === FALSE) {
				$var .= '.0';
			}
			return "<span class=\"php-float\">$var</span>\n";

		} elseif (is_string($var)) {
			if (Debugger::$maxLen && strlen($var) > Debugger::$maxLen) {
				$s = htmlSpecialChars(substr($var, 0, Debugger::$maxLen), ENT_NOQUOTES) . ' ... ';
			} else {
				$s = htmlSpecialChars($var, ENT_NOQUOTES);
			}
			$s = strtr($s, preg_match($reBinary, $s) || preg_last_error() ? $tableBin : $tableUtf);
			$len = strlen($var);
			return "<span class=\"php-string\">\"$s\"</span>" . ($len > 1 ? " ($len)" : "") . "\n";

		} elseif (is_array($var)) {
			$s = '<span class="php-array">array</span>(' . count($var) . ") ";
			$space = str_repeat($space1 = '   ', $level);
			$brackets = range(0, count($var) - 1) === array_keys($var) ? "[]" : "{}";

			static $marker;
			if ($marker === NULL) {
				$marker = uniqid("\x00", TRUE);
			}
			if (empty($var)) {

			} elseif (isset($var[$marker])) {
				$brackets = $var[$marker];
				$s .= "$brackets[0] *RECURSION* $brackets[1]";

			} elseif ($level < Debugger::$maxDepth || !Debugger::$maxDepth) {
				$s .= "<code>$brackets[0]\n";
				$var[$marker] = $brackets;
				foreach ($var as $k => &$v) {
					if ($k === $marker) {
						continue;
					}
					$k = strtr($k, preg_match($reBinary, $k) || preg_last_error() ? $tableBin : $tableUtf);
					$k = htmlSpecialChars(preg_match('#^\w+$#', $k) ? $k : "\"$k\"");
					$s .= "$space$space1<span class=\"php-key\">$k</span> => " . self::htmlDump($v, $level + 1);
				}
				unset($var[$marker]);
				$s .= "$space$brackets[1]</code>";

			} else {
				$s .= "$brackets[0] ... $brackets[1]";
			}
			return $s . "\n";

		} elseif (is_object($var)) {
			if ($var instanceof \Closure) {
				$rc = new \ReflectionFunction($var);
				$arr = array();
				foreach ($rc->getParameters() as $param) {				
					$arr[] = '$' . $param->getName();
				}
				$arr = array('file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'parameters' => implode(', ', $arr));
			} else {
				$arr = (array) $var;
			}
			$s = '<span class="php-object">' . get_class($var) . "</span>(" . count($arr) . ") ";
			$space = str_repeat($space1 = '   ', $level);

			static $list = array();
			if (empty($arr)) {

			} elseif (in_array($var, $list, TRUE)) {
				$s .= "{ *RECURSION* }";

			} elseif ($level < Debugger::$maxDepth || !Debugger::$maxDepth || $var instanceof \Closure) {
				$s .= "<code>{\n";
				$list[] = $var;
				foreach ($arr as $k => &$v) {
					$m = '';
					if ($k[0] === "\x00") {
						$m = ' <span class="php-visibility">' . ($k[1] === '*' ? 'protected' : 'private') . '</span>';
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					
					$k = strtr($k, preg_match($reBinary, $k) || preg_last_error() ? $tableBin : $tableUtf);
					$k = htmlSpecialChars(preg_match('#^\w+$#', $k) ? $k : "\"$k\"");
					
					// -------------------------------------------------------------------

					$s .= "$space$space1<span class=\"php-key\">$k</span>$m => ";

					if($v instanceof Nette\DI\IContainer)
						$s .= "{ DI CONTAINER }\n";
					else
						$s .= self::htmlDump($v, $level + 1);

					// -------------------------------------------------------------------

				}
				array_pop($list);
				$s .= "$space}</code>";

			} else {
				$s .= "{ ... }";
			}
			return $s . "\n";

		} elseif (is_resource($var)) {
			return '<span class="php-resource">' . htmlSpecialChars(get_resource_type($var)) . " resource</span>\n";

		} else {
			return "<span>unknown type</span>\n";
		}
	}

	
}