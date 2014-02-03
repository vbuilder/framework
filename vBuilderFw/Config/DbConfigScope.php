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

namespace vBuilder\Config;

use Nette, dibi;

/**
 * Database based configuration scope
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 18, 2011
 */
class DbConfigScope extends ConfigScope {

	const TABLE_NAME = 'config';

	/** @var string|null scope name, if null no load/save is performed */
	private $scopeName;

	/** @var Nette\DI\Container DI */
	protected $context;

	/** @var DibiConnection DB connection */
	protected $db;

	/** @var array of cached scope data */
	static private $cache = array();

	/**
	 * Constructor
	 *
	 * @param Nette\DI\Container DI
	 * @param string|null scope name, if null this scope won't perform any load
	 * @param ConfigScope|null reference to fallback config scope or null if there isn't one
	 */
	function __construct(Nette\DI\Container $context, $name, $fallback = null) {
		$this->context = $context;
		$this->db = $this->context->database->connection;

		// Scope name
		if($name !== null) $this->setScopeName($name);

		parent::__construct($fallback);
	}

	/**
	 * Returns scope name of current instance
	 *
	 * @return string|null
	 */
	protected function getScopeName() {
		return $this->scopeName;
	}

	/**
	 * Sets scope name and purge all data from previous scope
	 * @param type $newScopeName
	 * @return type
	 */
	protected function setScopeName($newScopeName) {
		if($this->scopeName == $newScopeName) return ;
		$this->scopeName = $newScopeName;
		$this->hasChanged = false;

		if($this->scopeName !== null) {
			if(!isset(self::$cache[$this->scopeName])) {
				self::$cache[$this->scopeName] = array();
				$this->isLoaded = false;

			} else
				$this->isLoaded = true;

			$this->data = &self::$cache[$this->scopeName];
		} else {
			$this->data = array();
			$this->isLoaded = true;
		}

		$this->reset($this->data); // Reset DAO
	}

	/**
	 * Load function.
	 */
	protected function load() {
		// Has to be the first thing or it will be endless loop because of
		// load => has => get => isLoaded? => load
		$this->isLoaded = true;

		if($this->scopeName !== null) {
			$alreadyWrittenInto = count($this->data) > 0;

			$results = $this->db->query('SELECT * FROM ['.self::TABLE_NAME.'] WHERE [scope] = %s', $this->scopeName)->fetchAll();
			foreach($results as $curr) {
				if(!$alreadyWrittenInto || !$this->has($curr['key'])) {
					$this->set($curr['key'], $curr['value']);
				}
			}
		}
	}

	/**
	 * Save function.
	 */
	public function save() {
		if(!$this->hasChanged) return ;
		if($this->scopeName === null)
			throw new \LogicException('You cannot save null scope');


		$dict = $this->saveHelper($this->data);

		$data = array();
		foreach($dict as $key => $value) {
			$data[] = array(
				 'key' => $key,
				 'scope' => $this->scopeName,
				 'value' => $value
			);
		}

		$this->db->begin();
		$this->db->query('DELETE FROM ['.self::TABLE_NAME.'] WHERE [scope] = %s', $this->scopeName);
		if(count($data) > 0) $this->db->query('INSERT INTO ['.self::TABLE_NAME.'] %ex', $data);
		$this->db->commit();


		// TODO: Save only changed records and handle removes.
		/*
		if(count($data)) {
			$this->db->begin();

			foreach($dict as $key => $value) {
				$data = array(
				 'key' => $key,
				 'scope' => $this->scopeName,
				 'value' => $value
				);

				$this->db->query('INSERT INTO ['.self::TABLE_NAME.']', $data,
						  'ON DUPLICATE KEY UPDATE [value] = %s', $data['value']);
			}

			$this->db->commit();
		} */

		$this->hasChanged = false;
	}

	/**
	 * Save helper which creates dictionary in format
	 * key.somesubkey = value
	 *
	 * @param array reference to data
	 * @param string parent key
	 * @return array
	 */
	private function saveHelper(&$data, $parentKey = "") {
		$dict = array();

		foreach($data as $key => &$value)	{
			$absKey = $parentKey != "" ? "$parentKey.$key" : $key;

			if(is_array($value)) {
				$dict = array_merge($dict, $this->saveHelper($value, $absKey));
			} else
				$dict[$absKey] = $value;
		}

		return $dict;
	}

}
