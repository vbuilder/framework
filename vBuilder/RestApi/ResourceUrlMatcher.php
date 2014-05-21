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

namespace vBuilder\RestApi;

use vBuilder,
	Nette,
	Nette\Utils\Strings;

/**
 * Resource URL matcher
 *
 * This class is responsible for matching URL paths of resource against current request.
 *
 * Class is heavily based on Nette\Application\Routers\Route
 * and bend for this single purpose.
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 12, 2014
 */
class ResourceUrlMatcher extends Nette\Object {

	/** flag */
	const CASE_SENSITIVE = 256;

	/** @var int */
	public static $defaultFlags = 0;

	/** @var string */
	private $mask;

	/** @var array */
	private $sequence;

	/** @var array of [value & fixity, filterIn, filterOut] */
	private $metadata = array();

	/** @var int */
	private $flags;

	/** @var string  regular expression pattern */
	private $re;

	/** key used in {@link Route::$styles} or metadata {@link Route::__construct} */
	const VALUE = 'value';
	const PATTERN = 'pattern';
	const FILTER_IN = 'filterIn';
	const FILTER_OUT = 'filterOut';

	/** @internal fixity types - how to handle default value? {@link Route::$metadata} */
	const OPTIONAL = 0,
		PATH_OPTIONAL = 1,
		CONSTANT = 2;

	/** @var array */
	public static $styles = array(
		'#' => array( // default style for path parameters
			self::PATTERN => '[^/]+',
			self::FILTER_IN => 'rawurldecode',
			self::FILTER_OUT => array(__CLASS__, 'param2path')
		),
	);

	// -------------------------------------------------------------------------

	public function __construct($mask, $flags = 0) {
		$this->setMask($mask);
		$this->flags = $flags | static::$defaultFlags;
	}

	public function matches($urlPath) {

		if (!$matches = Strings::match($urlPath, $this->re)) {
			// stop, not matched
			return NULL;
		}

		// deletes numeric keys, restore '-' chars
		$params = array();
		foreach ($matches as $k => $v) {
			if (is_string($k) && $v !== '') {
				$params[str_replace('___', '-', $k)] = $v; // trick
			}
		}

		// 4) APPLY FILTERS & FIXITY
		foreach ($this->metadata as $name => $meta) {
			if (isset($params[$name])) {
				if (!is_scalar($params[$name])) {

				} elseif (isset($meta[self::FILTER_IN])) { // applies filterIn only to scalar parameters
					$params[$name] = call_user_func($meta[self::FILTER_IN], (string) $params[$name]);
				}
			}
		}

		if (isset($this->metadata[NULL][self::FILTER_IN])) {
			$params = call_user_func($this->metadata[NULL][self::FILTER_IN], $params);
			if ($params === NULL) {
				return NULL;
			}
		}

		return $params;
	}

	public function constructPath(&$params) {

		$metadata = $this->metadata;
		foreach ($metadata as $name => $meta) {
			if (!isset($params[$name])) {
				continue; // retains NULL values
			}

			if (isset($meta[self::FILTER_OUT])) {
				$params[$name] = call_user_func($meta[self::FILTER_OUT], $params[$name]);
			}

			if (isset($meta[self::PATTERN]) && !preg_match($meta[self::PATTERN], rawurldecode($params[$name]))) {
				throw new Nette\InvalidArgumentException("Value does not match pattern for parameter '$name'");
			}
		}

		$sequence = $this->sequence;
		$brackets = array();
		$required = NULL; // NULL for auto-optional
		$url = '';
		$i = count($sequence) - 1;
		do {
			$url = $sequence[$i] . $url;
			if ($i === 0) {
				break;
			}
			$i--;

			$name = $sequence[$i]; $i--; // parameter name

			if ($name === ']') { // opening optional part
				$brackets[] = $url;

			} elseif ($name[0] === '[') { // closing optional part
				$tmp = array_pop($brackets);
				if ($required < count($brackets) + 1) { // is this level optional?
					if ($name !== '[!') { // and not "required"-optional
						$url = $tmp;
					}
				} else {
					$required = count($brackets);
				}

			} elseif ($name[0] === '?') { // "foo" parameter
				continue;

			} elseif (isset($params[$name]) && $params[$name] != '') { // intentionally ==
				$required = count($brackets); // make this level required
				$url = $params[$name] . $url;
				unset($params[$name]);

			} elseif (isset($metadata[$name]['fixity'])) { // has default value?
				if ($required === NULL && !$brackets) { // auto-optional
					$url = '';
				} else {
					$url = $metadata[$name]['defOut'] . $url;
				}

			} else {
				throw new Nette\InvalidArgumentException("Missing parameter '$name'");
				// return NULL; // missing parameter '$name'
			}
		} while (TRUE);

		return $url;
	}

	protected function setMask($mask) {
		$this->mask = $mask;

		$metadata = array();

		// PARSE MASK
		// <parameter-name[=default] [pattern] [#class]> or [ or ] or ?...
		$parts = Strings::split($mask, '/<([^># ]+)() *([^>#]*)(#?[^>\[\]]*)>|(\[!?|\]|\s*\?.*)/');

		$i = count($parts) - 1;

		// PARSE PATH PART OF MASK
		$brackets = 0; // optional level
		$re = '';
		$sequence = array();
		$autoOptional = TRUE;
		do {
			array_unshift($sequence, $parts[$i]);
			$re = preg_quote($parts[$i], '#') . $re;
			if ($i === 0) {
				break;
			}
			$i--;

			$part = $parts[$i]; // [ or ]
			if ($part === '[' || $part === ']' || $part === '[!') {
				$brackets += $part[0] === '[' ? -1 : 1;
				if ($brackets < 0) {
					throw new Nette\InvalidArgumentException("Unexpected '$part' in mask '$mask'.");
				}
				array_unshift($sequence, $part);
				$re = ($part[0] === '[' ? '(?:' : ')?') . $re;
				$i -= 5;
				continue;
			}

			$class = $parts[$i]; $i--; // validation class
			$pattern = trim($parts[$i]); $i--; // validation condition (as regexp)
			$default = $parts[$i]; $i--; // default value
			$name = $parts[$i]; $i--; // parameter name
			array_unshift($sequence, $name);

			if ($name[0] === '?') { // "foo" parameter
				$name = substr($name, 1);
				$re = $pattern ? '(?:' . preg_quote($name, '#') . "|$pattern)$re" : preg_quote($name, '#') . $re;
				$sequence[1] = $name . $sequence[1];
				continue;
			}

			// check name (limitation by regexp)
			if (preg_match('#[^a-z0-9_-]#i', $name)) {
				throw new Nette\InvalidArgumentException("Parameter name must be alphanumeric string due to limitations of PCRE, '$name' given.");
			}

			// pattern, condition & metadata
			if ($class !== '') {
				if (!isset(static::$styles[$class])) {
					throw new Nette\InvalidStateException("Parameter '$name' has '$class' flag, but Route::\$styles['$class'] is not set.");
				}
				$meta = static::$styles[$class];

			} elseif (isset(static::$styles[$name])) {
				$meta = static::$styles[$name];

			} else {
				$meta = static::$styles['#'];
			}

			if (isset($metadata[$name])) {
				$meta = $metadata[$name] + $meta;
			}

			if ($pattern == '' && isset($meta[self::PATTERN])) {
				$pattern = $meta[self::PATTERN];
			}

			$meta[self::PATTERN] = "#(?:$pattern)\\z#A" . ($this->flags & self::CASE_SENSITIVE ? '' : 'iu');

			// include in expression
			$re = '(?P<' . str_replace('-', '___', $name) . '>(?U)' . $pattern . ')' . $re; // str_replace is dirty trick to enable '-' in parameter name
			if ($brackets) { // is in brackets?
				if (!isset($meta[self::VALUE])) {
					$meta[self::VALUE] = $meta['defOut'] = NULL;
				}
				$meta['fixity'] = self::PATH_OPTIONAL;

			} elseif (!$autoOptional) {
				unset($meta['fixity']);

			} elseif (isset($meta['fixity'])) { // auto-optional
				$re = '(?:' . $re . ')?';
				$meta['fixity'] = self::PATH_OPTIONAL;

			} else {
				$autoOptional = FALSE;
			}

			$metadata[$name] = $meta;
		} while (TRUE);

		if ($brackets) {
			throw new Nette\InvalidArgumentException("Missing closing ']' in mask '$mask'.");
		}

		$this->re = '#' . $re . '/?\z#A' . ($this->flags & self::CASE_SENSITIVE ? '' : 'iu');
		$this->metadata = $metadata;
		$this->sequence = $sequence;
	}

	/**
	 * Url encode.
	 * @param  string
	 * @return string
	 */
	private static function param2path($s)
	{
		return str_replace('%2F', '/', rawurlencode($s));
	}


}