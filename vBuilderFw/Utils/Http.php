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

use Nette;

/**
 * Http library
 *
 * @author Adam Staněk (velbloud)
 * @since Jul 17, 2012
 */
class Http {

	/**
	 * Parses HTTP status code from string
	 *
	 * @return int|null
	 */
	public static function parseStatusCode($str) {
		if(preg_match('#HTTP/1.[01] ([1-5][0-9]{2})#', $str, $matches))
			return (int) $matches[1];
	}

	/**
	 * Returns array of parsed HTTP headers from string
	 *
	 * @param string
	 * @param bool if true, all header keys are converted to lower case
	 *
	 * @return array
	 */
	public static function parseHeaders($str, $keyCaseConvert = false) {
		// PECL_HTTP
		// http://php.net/manual/en/function.http-parse-headers.php
		if(function_exists('http_parse_headers'))
			return !$keyCaseConvert ? http_parse_headers($str) : array_change_key_case(http_parse_headers($str));
			
		// Otherwise
		$retVal = array();
		$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $str));
		foreach($fields as $field) {
			if(preg_match('/([^:]+): (.+)/m', $field, $match)) {
				$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
				
				if(isset($retVal[$match[1]])) {
					$retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
				} else {
					$retVal[$match[1]] = trim($match[2]);
				}
			}
		}
		
		
		return !$keyCaseConvert ? $retVal : array_change_key_case($retVal, CASE_LOWER);
	}
	
	/**
	 * Parses date/time string formated by HTTP specification
	 *
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3
	 *
	 * @param string
	 * @param DateTime|null
	 */
	public static function parseDateTime($str) {
		
		// Format 1:
		// Sun, 06 Nov 1994 08:49:37 GMT  ; RFC 822, updated by RFC 1123
		$dt = \DateTime::createFromFormat('D, d M Y H:i:s O', $str);
		if($dt !== FALSE) return $dt;
		
		// Format 2:
		// Sunday, 06-Nov-94 08:49:37 GMT ; RFC 850, obsoleted by RFC 1036
		$dt = \DateTime::createFromFormat('l, d-M-y H:i:s O', $str);
		if($dt !== FALSE) return $dt;
		
		// Format 3:
		// Sun Nov  6 08:49:37 1994       ; ANSI C's asctime() format
		$dt = \DateTime::createFromFormat('D M j H:i:s Y', $str, new \DateTimeZone('UTC'));
		if($dt !== FALSE) return $dt;
		
		return null;
	}
	
}
