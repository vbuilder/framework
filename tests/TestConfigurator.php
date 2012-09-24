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

namespace vBuilder;

use Nette;

/**
 * Test configurator
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 2, 2011
 */
class TestConfigurator {
			
	/**
	 * @return DibiConnection
	 */
	public static function createServiceConnection($params, Nette\DI\Container $container) {
		$config = $container->params['database'];
		if(!isset($params['database']) || empty($params['database']))
			throw new Nette\InvalidArgumentException("Database name has to be set.");
		
		if(isset($params['testDatabase'])) {
			if($params['testDatabase'] == $params['database'])
				throw new \LogicException("It's not possible to use same database for application and for test. Please change testDatabase directive in your config file.");

			$appDb = $params['database'];
			$testDb = $params['testDatabase'];
			
			unset($params['testDatabase']);
		} else {
			$appDb = $params['database'];
			$testDb = 'test';
		}
		
		$connection = new \DibiConnection($params);
		
		// Vytvorim testovaci tabulky podle hlavni database
		$tables = $connection->query('SHOW TABLES');
		foreach($tables as $curr) {
			$tableName = reset($curr);			
			$connection->query("CREATE TEMPORARY TABLE [$testDb.$tableName] LIKE [$tableName]");
		}
		
		// Zmenim databazi na testovaci
		$connection->query("USE [$testDb]");
		
		return $connection;
	}
	
	/**
	 * @return vBuilder\Config\IConfig
	 */
	public static function createServiceConfig(Nette\DI\Container $container) {
		$configDefaults = Config\DbUserConfig::getDefaultsFilepath();
		if(!file_exists($configDefaults))
			throw new Nette\InvalidStateException("Missing '$configDefaults' config file");

		return new Config\FileConfigScope(array($configDefaults));
	}	
	
}