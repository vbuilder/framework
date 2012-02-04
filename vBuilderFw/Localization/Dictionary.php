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
 * Dictionary
 *
 * Based on Nella project translator edited for better integration
 * @author	Patrik Votoček
 *
 * @see http://nella-project.org
 * @see https://github.com/nella/framework/blob/master/Nella/Localization/Dictionary.php
 *
 * @property-read string $dir
 * @property string $pluralForm
 * @property array $metadata
 * @property-read \ArrayIterator $iterator
 */
class Dictionary extends Nette\FreezableObject implements \IteratorAggregate, \Serializable
{
	const STATUS_SAVED = TRUE,
		STATUS_TRANSLATED = FALSE,
		STATUS_UNTRANSLATED = NULL;

	/** @var string */
	private $dir;
	/** @var IStorage */
	private $storage;
	/** @var string */
	private $lang;
	/** @var string */
	private $pluralForm;
	/** @var array */
	private $metadata;
	/** @var array */
	private $dictionary;

	/**
	 * @param string
	 * @param string
	 */
	public function __construct($dir, IStorage $storage)
	{
		$this->dir = $dir;
		$this->storage = $storage;
		$this->metadata = $this->dictionary = array();
	}

	/**
	 * @return string
	 */
	public function getDir()
	{
		return $this->dir;
	}

	/**
	 * @return string
	 */
	public function getPluralForm()
	{
		return $this->pluralForm;
	}

	/**
	 * @param string
	 * @return Dictionary
	 */
	public function setPluralForm($pluralForm)
	{
		$this->pluralForm = $pluralForm;
		return $this;
	}

	/**
	 * @internal
	 * @return array
	 */
	public function getMetadata()
	{
		return $this->metadata;
	}

	/**
	 * @internal
	 * @param array
	 * @return Dictionary
	 */
	public function setMetadata(array $metadata = array())
	{
		$this->updating();

		$this->metadata = $metadata;
		return $this;
	}

	/**
	 * @param string
	 * @param array
	 * @param bool
	 * @return Dictionary
	 */
	public function addTranslation($message, array $translation = array(), $status = self::STATUS_SAVED)
	{
		$this->dictionary[$message] = array(
			'status' => $status,
			'translation' => $translation,
		);

		return $this;
	}

	/**
	 * @param string
	 * @return bool
	 */
	public function hasTranslation($message)
	{
		return isset($this->dictionary[$message]);
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->dictionary);
	}

	/**
	 * @param string
	 * @throws \Nette\InvalidStateException
	 */
	public function init($lang)
	{
		$this->updating();

		$this->lang = $lang;
		$this->storage->load($this->lang, $this);

		$this->freeze();
	}

	/**
	 * @param string
	 * @param int
	 * @return string
	 * @throws \Nette\InvalidStateException
	 */
	public function translate($message, $count = NULL)
	{
		if (!$this->isFrozen()) {
			throw new \Nette\InvalidStateException("Dictionary not initialized");
		}

		if (!$this->hasTranslation($message)) {
			return NULL;
		}

		$translation = $this->dictionary[$message]['translation'];
		$plural = $this->parsePluralForm($count);

		return isset($translation[$plural]) ? $translation[$plural] : $translation[0];
	}

	/**
	 * @param int
	 * @return int
	 */
	protected function parsePluralForm($form)
	{
		if (!isset($this->pluralForm) || $form === NULL) {
			return 0;
		}

		eval($x = preg_replace('/([a-z]+)/', '$$1', "n=$form;".$this->pluralForm.";"));

		return $plural;
	}

	/**
	 * @return Dictionary
	 */
	public function save()
	{
		$this->storage->save($this, $this->lang);
		return $this;
	}

	/**
	 * @return string
	 */
	public function serialize()
	{
		return serialize(array(
			'metadata' => $this->metadata,
			'pluralForm' => $this->pluralForm,
			'dicionary' => $this->dictionary,
		));
	}

	/**
	 * @param string
	 */
	public function unserialize($serialized)
	{
		$data = unserialize($serialized);
		$this->metadata = $data['metadata'];
		$this->pluralForm = $data['pluralForm'];
		$this->dictionary = $data['dictionary'];
	}
}