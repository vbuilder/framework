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

namespace vBuilder\Config;

use vBuilder,
	Nette,
	DibiConnection;

/**
 * Database based config with global / user settings with file defaults
 * in /app/defaults.neon
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 18, 2011
 */
class DbUserConfig extends DbConfigScope implements IConfig {

	/** @var int|null user id */
	private $userId;

	public static function createService($configFile, DibiConnection $dbConnection, vBuilder\Security\User $userService) {
		$userConfig = new self($dbConnection, $userService->isLoggedIn() ? $userService->getId() : null, $configFile);

		$userService->addObserver(
			$userService::EVENT_ON_LOGGED_IN, function ($userService) use ($userConfig) {
				$userConfig->setUserId($userService->getId());
			});

		$userService->addObserver(
			$userService::EVENT_ON_LOGGED_OUT, function ($userService) use ($userConfig) {
				$userConfig->setUserId(NULL);
			});

		return $userConfig;
	}

	/**
	 * Constructor
	 *
	 * @param DibiConnection database connection
	 * @param int|null user id, if null only global config
	 * will be loaded
	 */
	function __construct(DibiConnection $dbConnection, $user = null, $configFile = null) {
		$defaults = $configFile !== null && (is_array($configFile) || file_exists($configFile))
				  ? new FileConfigScope((array) $configFile)
				  : null;

		$global = new DbConfigScope($dbConnection, 'global', $defaults);

		if(is_object($user)) $this->userId = $user->getId();
		else $this->userId = $user;

		parent::__construct($dbConnection, $this->userId !== null ? 'user('.$this->userId.')' : null, $global);
	}

	/**
	 * Returns user id of user whoose config it is loaded
	 *
	 * @return int|null user id or null if it is global config only
	 */
	function getUserId() {
		return $this->userId;
	}

	/**
	 * Sets user id and loads it's config
	 *
	 * @param int user id
	 */
	function setUserId($userId) {
		$this->userId = $userId;
		$this->setScopeName($this->userId !== null ? 'user('.$this->userId.')' : null);
	}

	/**
	 * Shortcut to get global scope
	 */
	function getGlobalScope() {
		if($this->userId !== null) return $this->getFallbackScope();
		else return $this;
	}

	/**
	 * Cascadading save
	 */
	public function save() {
		parent::save();

		$ptr = $this->getFallbackScope();
		while($ptr) {
			$ptr->save();
			$ptr = $ptr->getFallbackScope();
		}
	}

}
