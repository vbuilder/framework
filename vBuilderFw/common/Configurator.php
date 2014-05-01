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

namespace vBuilder;

use Nette,
	Nette\Caching\Cache;

/**
 * Initial system DI container generator.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class Configurator extends Nette\Configurator {

	private $extensions;

	/**
	 * Returns system DI container.
	 * @return \SystemContainer
	 */
	public function createContainer() {

		// Gather all extension info
		$extensions = $this->getExtensionInfo();

		// Add all configuration files from libraries first
		$autoConfigFiles = $extensions['configFiles'];
		if(count($autoConfigFiles)) {
			foreach(array_reverse($autoConfigFiles) as $file) {
				$found = false;
				foreach($this->files as $config) {
					if(realpath($config[0]) == $file) {
						$found = true;
						break;
					}
				}

				if(!$found)
					array_unshift($this->files, array($file, NULL));
			}
		}

		return parent::createContainer();
	}

	/**
	 * @return Nette\Loaders\RobotLoader
	 */
	public function createRobotLoader() {
		$loader = parent::createRobotLoader();

		// Gather all extension info
		$extensions = $this->getExtensionInfo();

		// Add directories to RobotLoader
		foreach($extensions['robotLoaderDirectories'] as $dirPath)
			$loader->addDirectory($dirPath);

		return $loader;
	}

	protected function getDefaultParameters() {
		$default = parent::getDefaultParameters();

		$default['appDir'] = Utils\FileSystem::normalizePath($default['wwwDir'] . '/../app');
		$default['confDir'] = $default['appDir'] . '/config';
		$default['vendorDir'] = Utils\FileSystem::normalizePath($default['wwwDir'] . '/../vendor');
		$default['libsDir'] = $default['vendorDir'];
		$default['filesDir'] = Utils\FileSystem::normalizePath($default['wwwDir'] . '/../files');
		$default['logDir'] = Utils\FileSystem::normalizePath($default['wwwDir'] . '/../log');
		$default['tempDir'] = Utils\FileSystem::normalizePath($default['wwwDir'] . '/../temp');

		return $default;
	}

	/**
	 * @param  string        error log directory
	 * @param  string        administrator email
	 * @return void
	 */
	public function enableDebugger($logDirectory = NULL, $email = NULL) {
		// Add default parameters
		return parent::enableDebugger(
			$logDirectory === NULL ? $this->parameters['logDir'] : $logDirectory,
			$email === NULL ? (isset($this->parameters['errorRecipients']) ? $this->parameters['errorRecipients'] : NULL) : $email
		);
	}

	/**
	 * Detects debug mode
	 * @param  string|array  IP addresses or computer names whitelist detection
	 * @return bool
	 */
	public static function detectDebugMode($list = NULL) {
		// Allows SetEnv and SetEnvIf from virtual host configuration
		if(isset($_SERVER["DEVELOPMENT_MODE"]))
			return (bool) $_SERVER["DEVELOPMENT_MODE"];

		return parent::detectDebugMode($list);
	}

	/**
	 * Gathers info about installed extensions
	 */
	protected function getExtensionInfo() {
		if(!$this->extensions) {
			$cache = new Cache(new Nette\Caching\Storages\FileStorage($this->getCacheDirectory()), 'vBuilder.Configurator');
			$parameters = &$this->parameters;

			$this->extensions = $cache->load('extensions', function (&$dependencies) use (&$parameters) {
				$composerLockFile = $parameters['appDir'] . '/../composer.lock';
				$dependencies[Cache::FILES] = array($composerLockFile);

				$composerLock = json_decode(file_get_contents($composerLockFile));
				$packages = array_merge(
					isset($composerLock->{"packages"}) ? $composerLock->{"packages"} : array(),
					isset($composerLock->{"packages-dev"}) ? $composerLock->{"packages-dev"} : array()
				);

				$extensions = array(
					'configFiles' => array(),
					'robotLoaderDirectories' => array()
				);

				foreach($packages as $pkg) {
					$basePath = realpath($parameters['appDir'] . '/../vendor/' . $pkg->name);
					if(!$basePath) continue;

					$configFile = $basePath . '/config.neon';
					if(file_exists($configFile)) $extensions['configFiles'][] = $configFile;

					$robotsFile = $basePath . '/netterobots.txt';
					if(file_exists($robotsFile)) $extensions['robotLoaderDirectories'][] = $basePath;
				}

				return $extensions;
			});
		}

		return $this->extensions;
	}

}