<?php

/**
 * This file is part of vBuilder Framework (vBuilder FW).
 * 
 * Copyright (c) 2012 V3Net.cz, s.r.o <info@v3net.cz>
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

namespace vBuilder\Localization;

use Nette;

/**
 * Translator
 *
 * Based on Nella project translator edited for better integration
 * @author	Patrik Votoček
 *
 * @see http://nella-project.org
 * @see https://raw.github.com/nella/framework/master/Nella/Localization/Translator.php
 *
 * @property string $lang
 * @property-read array $dictionaries
 */
class Translator extends Nette\FreezableObject implements ITranslator
{
	/** @var array */
	protected $dictionaries = array();
	/** @var IStorage */
	private $storage;
	/** @var \Nette\Caching\Cache */
	private $cache = NULL;

	/** @var string */
	private $lang = "en";

	/**
	 * @param \Nette\Caching\IStorage
	 */
	public function __construct(Nette\Caching\IStorage $cacheStorage = NULL)
	{
		if ($cacheStorage) {
			$this->cache = new Nette\Caching\Cache($cacheStorage, "Nella.Translator");
		}
	}

	/**
	 * @return IStorage
	 */
	protected function getStorage()
	{
		if (!$this->storage) {
			$this->storage = new Storages\Gettext; // default storage
		}
		return $this->storage;
	}

	/**
	 * @param IStorage
	 * @return Translator
	 */
	public function setStorage(IStorage $storage)
	{
		$this->updating();
		$this->storage = $storage;
		return $this;
	}

	/**
	 * @param string
	 * @param string
	 * @param IStorage
	 * @throws \Nette\InvalidArgumentException
	 */
	public function addDictionary($name, $dir, $fileMask = '%dir%/lang/%lang%.mo', IStorage $storage = NULL)
	{
		if (!file_exists($dir)) {
			throw new \Nette\InvalidArgumentException("Directory '$dir' not exist");
		}

		$dir = realpath($dir);

		$storage = $storage ?: $this->getStorage();
		$this->dictionaries[$name] = new Dictionary($dir, $fileMask, $storage);
		return $this;
	}

	/**
	 * @internal
	 * @return array
	 */
	public function getDictionaries()
	{
		return $this->dictionaries;
	}

	/**
	 * @return string
	 */
	public function getLang()
	{
		return $this->lang;
	}

	/**
	 * @param string
	 * @return Translator
	 * @throws \Nette\InvalidStateException
	 */
	public function setLang($lang)
	{
		$this->updating();

		$this->lang = $lang;
		return $this;
	}

	/**
	 * @throws \Nette\InvalidStateException
	 */
	public function init()
	{
		$this->updating();

		foreach ($this->dictionaries as $dictionary) {
			$dictionary->init($this->lang);
		}

		$this->freeze();
	}

	/**
	 * @param string
	 * @param int
	 * @return string
	 */
	public function translate($message, $count = NULL)
	{
		if (!$this->isFrozen()) {
			$this->init();
		}

		$messages = (array) $message;
		$args = (array) $count;
		$form = $args ? reset($args) : NULL;
		$form = $form === NULL ? 1 : (is_int($form) ? $form : 0);
		$plural = $form == 1 ? 0 : 1;

		$message = isset($messages[$plural]) ? $messages[$plural] : $messages[0];
		foreach ($this->dictionaries as $dictionary) {
			if (($tmp = $dictionary->translate(reset($messages), $form)) !== NULL) {
				$message = $tmp;
				break;
			}
		}

		if (count($args) > 0 && reset($args) !== NULL) {
			$message = str_replace(array("%label", "%name", "%value"), array("%%label", "%%name", "%%value"), $message);
			$message = vsprintf($message, $args);
		}

		return $message;
	}
}