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

require_once __DIR__ . '/Loaders/RobotLoader.php';

/**
 * Base framework configurator
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 2, 2011
 */
class Configurator extends Nette\Configurator {
	
	/**
	 * @return vBuilder\Config\IConfig
	 */
	public static function createServiceConfig(Nette\DI\Container $container) {
		// TODO: Je tam treba vyresit ta zmena uzivatele, zamenena celeho service, jako
		// to bylo do ted je hovadina
		return new Config\DbUserConfig($container);
	}
	
	/**
	 * @return Nette\Loaders\RobotLoader
	 */
	public static function createServiceRobotLoader(Nette\DI\Container $container, array $options = NULL) {
		$loader = new Loaders\RobotLoader;
		$loader->autoRebuild = isset($options['autoRebuild']) ? $options['autoRebuild']
					  : !$container->params['productionMode'];
		$loader->setCacheStorage($container->cacheStorage);
		if(isset($options['directory'])) {
			$loader->addDirectory($options['directory']);
		} else {
			foreach(array('appDir', 'libsDir') as $var) {
				if(isset($container->params[$var])) {
					$loader->addDirectory($container->params[$var]);
				}
			}
		}
		$loader->register();
		return $loader;
	}
	
}
