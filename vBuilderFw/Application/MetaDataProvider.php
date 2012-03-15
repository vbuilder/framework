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

namespace vBuilder\Application;

use vBuilder,
	vBuilder\Utils\Strings,
	Nette;

/**
 * Provider of page metadata
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 7, 2011
 */
class MetaDataProvider extends Nette\FreezableObject {

	const MAX_LENGTH_TITLE = 63;
	const MAX_LENGTH_DESCRIPTION = 160;
	const MAX_LENGTH_KEYWORDS = 256;
	
	const MAX_NUM_KEYWORDS = 10;

	/** @var string page title */
	protected $_title;
	
	/** @var string page title suffix */
	protected $_titleSuffix;
	
	/** @var string page title suffix separator */
	protected $_titleSuffixSeparator = ' - ';
	
	/** @var bool automatically convert first title letter to upper case? */
	protected $_titleAutoCapitalize = true;
	
	/** @var string page description */
	protected $_description;
	
	/** @var string page author's name */
	protected $_author;
	
	/** @var array of page keywords */
	protected $_keywords = array();

	/** @var string page robots setting */
	protected $_robots;

	/** @var Nette\DI\IContainer DI context container */
	protected $context;

	public function __construct(Nette\DI\IContainer $context) {
		$this->context = $context;
	}

	/**
	 * Sets page title. Title should not be empty nor longer than MAX_LENGTH_TITLE chars,
	 * the E_USER_NOTICE is triggered otherwise.
	 *
	 * @param string|false page title
	 *
	 * @return MetaDataProvider fluent
	 */
	public function setTitle($title) {
		$this->updating();
		if($title === false) { $this->_title = false; return $this; }
	
		$title = Strings::simplify($title);
	
		if($title == '')
			trigger_error('Title should not be empty.');
			
		// SEO restraint
		if(mb_strlen($title) > self::MAX_LENGTH_TITLE) 
			trigger_error('Title should not be longer than ' . self::MAX_LENGTH_TITLE . ' chars. The given title ' . var_export($title, true) . ' is ' . mb_strlen($title) . ' chars long.');
	
		$this->_title = $this->getTitleAutoCapitalize() ? Strings::firstUpper($title) : $title;
		return $this;
	}

	/**
	 * Returns page title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->_title;
	}
	
	/**
	 * Sets if class should automatically convert
	 * first title letter to upper case?
	 *
	 * @param bool
	 *
	 * @return MetaDataProvider fluent
	 */
	public function setTitleAutoCapitalize($isEnabled) {
		$this->_titleAutoCapitalize = $isEnabled;
		return $this;
	}
	
	/**
	 * Returns true if auto capitalize option is enabled
	 *
	 * @return bool
	 */
	public function getTitleAutoCapitalize() {
		return $this->_titleAutoCapitalize;
	}
	
	
	/**
	 * Returns actual title to display.
	 * Title is determined based on title string and title suffix.
	 *
	 * @return string
	 */
	public function getEffectiveTitle() {
		$suffix = $this->getTitleSuffix()
			? $this->getTitleSuffixSeparator() . $this->getTitleSuffix()
			: '';
			
		if($this->getTitle()) {
			$combined = $this->getTitle() . $suffix;
			return mb_strlen($combined) > self::MAX_LENGTH_TITLE
				? $this->getTitle()
				: $combined;
				
		} else
			return $this->getTitleAutoCapitalize()
				? Strings::firstUpper($this->getTitleSuffix())
				: $this->getTitleSuffix();
	}
	
	/**
	 * Sets title suffix
	 *
	 * @param string separator
	 * @return MetaDataProvider fluent
	 */
	public function setTitleSuffix($suffix) {
		$this->_titleSuffix = Strings::simplify($suffix);
		return $this;
	}
	
	/**
	 * Returns title suffix
	 *
	 * @return string
	 */
	public function getTitleSuffix() {
		return $this->_titleSuffix;
	}
	
	/**
	 * Sets title suffix separator
	 *
	 * @param string separator
	 * @return MetaDataProvider fluent
	 */
	public function setTitleSuffixSeparator($separator) {
		$this->_titleSuffixSeparator = Strings::simplify($separator);
		return $this;
	}
	
	/**
	 * Returns title suffix separator
	 *
	 * @return string
	 */
	public function getTitleSuffixSeparator() {
		return $this->_titleSuffixSeparator;
	}
	
	/**
	 * Sets page description. The description is automatically stripped
	 * from all HTML tags and truncated to MAX_LENGTH_DESCRIPTION chars. 
	 *
	 * @param string|false page description
	 *
	 * @return MetaDataProvider fluent
	 */
	public function setDescription($description) {
		$this->updating();
		if($description === false) { $this->_description = false; return $this; }
	
		$description = strip_tags($description);
		$description = Strings::simplify($description);

		$this->_description = Strings::truncate($description, self::MAX_LENGTH_DESCRIPTION, '');		
		
		return $this;
	}
	
	/**
	 * Returns page description.
	 * Accepts array of keywords or comma separated string.
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->_description;
	}
	
	/**
	 * Adds another page keywords.
	 * Accepts array of keywords or comma separated string.
	 *
	 * @param array|string keywords
	 * @return MetaDataProvider fluent
	 */
	public function addKeywords($keywords) {
		$this->updating();
	
		$keywords = is_array($keywords) ? is_array() : explode(',', $keywords);
		foreach($keywords as $keyword) {
			$keyword = Strings::simplify($keyword);
			if(!in_array($keyword, $this->_keywords))
				$this->_keywords[] = $keyword;
		}
		
		return $this;
	}
	
	/**
	 * Sets page keywords
	 *
	 * @param array|string keywords
	 * @return MetaDataProvider fluent
	 */
	public function setKeywords($keywords) {
		$this->updating();
	
		$this->_keywords = array();
		
		if(!is_bool($keywords) || $keywords)
			$this->addKeywords($keywords);
		
		return $this;
	}
	
	/**
	 * Returns page keywords
	 *
	 * @return array of string
	 */
	public function getKeywords() {
		return $this->_keywords;
	}
	
	/**
	 * Returns page keywords formatted as comma separated string
	 *
	 * @return string
	 */
	public function getKeywordString() {
		return Strings::truncate(implode(',', array_slice($this->_keywords, 0, self::MAX_NUM_KEYWORDS)), self::MAX_LENGTH_KEYWORDS, '');
	}
	
	/**
	 * Sets page author
	 *
	 * @param string|false author's name
	 * @return MetaDataProvider fluent
	 */
	public function setAuthor($name) {
		$this->updating();
		if($name === false) { $this->_author = false; return $this; }
		
		$this->_author = Strings::simplify($name);
		return $this;
	}
	
	/** 
	 * Returns author's name
	 *
	 * @return string
	 */
	public function getAuthor() {
		return $this->_author;
	}
	
	/**
	 * Sets page robot setting 
	 *
	 * @param bool|string
	 * @return MetaDataProvider fluent
	 */
	public function setRobots($robots) {
		$this->updating();
		
		if(is_bool($robots)) {
			$this->_robots = $robots ? 'index, follow' : 'noindex, nofollow';
		} else {
			$this->_robots = strval($robots);
		}

		return $this;
	}
	
	/**
	 * Returns page robot setting
	 *
	 * @return string
	 */
	public function getRobots() {
		return $this->_robots;
	}
	
	// TODO: dodelat ---------------------
	
	public function getEncoding() {
		return 'utf-8';
	}
	
	public function getContentType() {
		return 'text/html';
	}

}