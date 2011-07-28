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
	
	private $userId;
	
	/**
	 * Constructor
	 */
	function __construct($user = null) {		
		$defaults = file_exists(static::getDefaultsFilepath())
				  ? new FileConfigScope(array(static::getDefaultsFilepath()))
				  : null;
		
		if($user === null) {
			if(Nette\Environment::getUser()->isLoggedIn()) $this->userId = Nette\Environment::getUser()->getId();
		} elseif(is_object($user))
			$this->userId = $user->getId();
		
		if($this->userId !== null) {
			$global = new DbConfigScope('global', $defaults);
			parent::__construct('user('.$this->userId.')', $global);
		} else {
			parent::__construct('global', $defaults);
		}
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
	 * For service purposes we also need to register handlers
	 * when current user is changed (log in / log out have to reload config).
	 * 
	 * @internal
	 */
	public static function createUserConfig() {
		$config = new static();

		Nette\Environment::getUser()->onLoggedIn[] = callback(get_called_class(), 'onUserChanged');
		Nette\Environment::getUser()->onLoggedOut[] = callback(get_called_class(), 'onUserChanged');
		
		return $config;
	}
	
	/**
	 * When new user logs in or out, we have to refresh config
	 * 
	 * @internal
	 * 
	 * @param Nette\Http\User $user 
	 */
	public static function onUserChanged(Nette\Http\User $user) {
		Nette\Environment::getContext()->removeService('vBuilder\Config\IConfig');
		Nette\Environment::getContext()->addService('vBuilder\Config\IConfig', get_called_class());
	}
	
	public static function getDefaultsFilepath() {
		return APP_DIR . '/defaults.neon';
	}
	
}
