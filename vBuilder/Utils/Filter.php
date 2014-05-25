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


class FilterException extends \Exception { }

/**
 * Library for filter strings
 *
 * @author Adam Staněk (velbloud)
 * @since Jan 3, 2013
 */
class Filter {

	/**
	 * Returns all identifiers used in the filter expression
	 *
	 * @param string
	 * @return array
	 */
	public static function getIdentifiers($str) {
		$ids = array();
		$stack = array(self::parse($str));

		while(count($stack)) {
			$curr = array_pop($stack);
			if(is_array($curr)) {
				for($i = 1; $i < count($curr); $i++) {
					if(is_array($curr[$i]))
						$stack[] = $curr[$i];

					elseif(preg_match('/^[a-z]+$/i', $curr[$i]) && !in_array($curr[$i], $ids))
						$ids[] = $curr[$i];
				}
				
			}
		}

		return $ids;
	}

	/**
	 * Returns simplified formated filter string
	 *
	 * @param string
	 * @return string
	 */
	public static function format($str) {
		$parsed = self::parse($str);
		return count($parsed) > 1 ? self::formatHelper($parsed) : $parsed[0];
	}

	/**
	 * Returns formated filter string as SQL clauses
	 *
	 * @param string
	 * @return string
	 */
	public static function formatSql($str) {
		$parsed = self::parse($str);
		return count($parsed) > 1 ? self::formatHelper($parsed, array(
			'|' => 'OR',
			'&' => 'AND',
			'!=' => '<>',
			'~=' => 'LIKE'
		)) : $parsed[0];	
	}

	/**
	 * Formatting helper
	 *
	 * @param array
	 * @param array
	 * @return string
	 */
	private static function formatHelper(array $parsed, array $operatorTr = array()) {
		$operator = array_shift($parsed);
		if(isset($operatorTr[$operator])) $operator = $operatorTr[$operator];
		$str = "";

		foreach($parsed as $operand) {
			if($str != "") $str .= " $operator ";

			if(is_array($operand))
				$str .= self::getOperatorPriority($operand[0]) != self::getOperatorPriority($operator) ? "(" . self::formatHelper($operand, $operatorTr) . ")" : self::formatHelper($operand, $operatorTr);
			else
				$str .= $operand;
		}

		return $str;
	}

	/**
	 * Returns parsed filter and performs caching for later
	 *
	 * @param string
	 * @return array
	 */
	private static function parse($str) {
		static $cached = array();
		if(!isset($cached[$str])) {
			$postfix = self::parseString("($str)");
			$cached[$str] = count($postfix) == 1 ? self::parseExpression($postfix[0]) : self::parsePostfix($postfix);
		}

		return $cached[$str];
	}

	/**
	 * Parses single equation
	 *
	 * @param string
	 *
	 * @param array
	 */
	private static function parseExpression($str) {
		$re_id 	 = '[a-z]+';							// Identifiers

		// TODO: floating point numbers
		$re_n_o  = '!?= | \\<\\> | \\>=? | \\<=?';		// Number operators
		$re_n    = '[0-9]+';							// Number operands

		$re_s_o  = '[!~]?= | ~ | \\<\\>';				// String operators
		$re_s_dq = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';	// Double quotes string with escapes
		$re_s_sq = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";	// Single quotes string with escapes

		$result = preg_match("/($re_id)\s* ((($re_n_o)\s*($re_n)) | (($re_s_o)\s*($re_s_dq|$re_s_sq)))/isx", $str, $matches);
		if(!$result) throw new FilterException("Invalid expression: $str");

		// Strings
		if(isset($matches[6])) {
			return array($matches[7] == '<>' ? '!=' : ($matches[7] == '~' ? '~=' : $matches[7]), $matches[1], strtr($matches[8], '"', "'"));
		}

		// Numbers
		elseif(isset($matches[2])) {
			return array($matches[4] == '<>' ? '!=' : $matches[4], $matches[1], $matches[5]);
		}

		// Fallback (never gonna happen)
		return $str;
	}

	/**
	 * Parses given string into postfix array
	 *
	 * @param array
	 */
	private static function parseString($str) {
		$result = preg_match_all('/[\\(\\)]|[&\\|]+|[\'"]|and|or/i', $str, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

		$stack = array();
		$postfix = array();
		$offset = 0;
		$quoted = 0;
		foreach($matches as $m) {
			// UVOZOVKY
			if($m[0][0] == '"' && $quoted != 2) {
				if($m[0][1] > 0 && $str[$m[0][1] - 1] != '\\') $quoted = $quoted == 1 ? 0 : 1;
				continue;
			} elseif($m[0][0] == "'" && $quoted != 1) {
				if($m[0][1] > 0 && $str[$m[0][1] - 1] != '\\') $quoted = $quoted == 2 ? 0 : 2;
				continue;
			} elseif($quoted > 0)
				continue;

			// OPERANDY
			$operand = trim(mb_substr($str, $offset, $m[0][1] - $offset));
			$offset = $m[0][1] + mb_strlen($m[0][0]);
			if($operand != "")
				$postfix[] = $operand;

			// ZAVORKY
			if($m[0][0] == '(') {
				$stack[] = $m[0][0];

			} elseif($m[0][0] == ')') {
				while(($el = array_pop($stack)) != NULL) {
					if($el == '(') break;
					$postfix[] = $el;
				}

				if($el == NULL) throw new FilterException("Unmatched parentheses");					

			// OPERATOR
			} else {
				$operator = self::normalizeOperator($m[0][0]);
				$priority = self::getOperatorPriority($operator);

				while(count($stack) && $stack[count($stack) - 1] != '(' && (!self::isOperator($stack[count($stack) - 1]) || $priority <= self::getOperatorPriority($stack[count($stack) - 1])) )
					$postfix[] = array_pop($stack);

				$stack[] = $operator;
			}
		}

		if($quoted > 0) throw new FilterException("Unmatched quotes");

		while(($el = array_pop($stack)) != NULL) {
			if($el == '(') throw new FilterException("Unmatched parentheses");

			$postfix[] = $el;
		}

		return $postfix;
	}

	/**
	 * Parses postfix expression for evaluation and simplifies it
	 * by grouping same operators together
	 *
	 * @param array
	 * @param &int
	 *
	 * @return array of array
	 */
	private static function parsePostfix(array $postfix, &$index = NULL) {
		if($index === NULL) $index = count($postfix) - 1;
		$a = array();

		if($index >= 0) {
			// Operator
			$operator = $postfix[$index--];

			// Operandy
			for($oi = 0; $oi < 2 && $index >= 0; $oi++) {
				$o = self::isOperator($postfix[$index]) ? self::parsePostfix($postfix, $index) : $postfix[$index--];
				if(is_array($o) && $o[0] == $operator) {
					array_shift($o);
					foreach($o as $b) $a[] = $b;
				} else 
					$a[] = !is_array($o) ? self::parseExpression($o) : $o;
			}

			$a = array_reverse($a);
			array_unshift($a, $operator);
		}

		return $a;
	}

	/**
	 * Returns TRUE if given token is an operator
	 *
	 * @return bool
	 */
	private static function isOperator($token) {
		switch($token) {
			case '|':
			case '||':
			case 'OR':
			case '&':
			case '&&':
			case 'AND':
				return true;

			default:
				return false;
		}
	}

	/**
	 * Returns operator priority 
	 *
	 * @param string normalized operator
	 * @return int|NULL
	 */
	private static function getOperatorPriority($operator) {
		switch($operator) {

			case '&':
				return 2;

			case '|':
				return 1;

			default:
				return NULL;
		}
	}

	/**
	 * Returns normalized operator
	 *
	 * @param string
	 * @return string
	 */
	private static function normalizeOperator($operator) {
		$operator = mb_strtoupper($operator);

		switch($operator) {
			case '&&':
			case 'AND':
				return '&';

			case '||':
			case 'OR':
				return  '|';

			default:
				return $operator;
		}
	}

}