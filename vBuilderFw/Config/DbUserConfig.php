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

use Nette;

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
	
	/**
	 * Constructor
	 * 
	 * @param Nette\DI\IContainer DI
	 * @param vBuilder\Security\User|int|null user id, if null only global config
	 * will be loaded
	 */
	function __construct(Nette\DI\IContainer $context, $user = null) {		
		$defaults = static::getDefaultsFilepath() !== null && file_exists(static::getDefaultsFilepath())
				  ? new FileConfigScope(array(static::getDefaultsFilepath()))
				  : null;
		
		$global = new DbConfigScope($context, 'global', $defaults);
		
		if(is_object($user)) $this->userId = $user->getId();
		else $this->userId = $user;
		
		parent::__construct($context, $this->userId !== null ? 'user('.$this->userId.')' : null, $global);
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
	
	/**
	 * Returns absolute path to config file with default values
	 * 
	 * @return string absolute path
	 */
	public static function getDefaultsFilepath() {
		return APP_DIR . '/defaults.neon';
	}
	
}
