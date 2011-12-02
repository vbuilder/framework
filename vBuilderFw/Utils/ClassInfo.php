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

namespace vBuilder\Utils;

use vBuilder, Nette;

/**
 * Class info routines
 *
 * @author Adam Staněk (velbloud)
 * @since Dec 2, 2011
 */
class Classinfo {
	
	/**
	 * Returns all child classes of specified parent.
	 * Results are cached along with robot cache.
	 * 
	 * @param string parent class name
	 * @return array of fully qualified class names (with namespace)
	 */
	public static function getAllChildrenOf($parentClassName) {
		$cacheKey = array();
		$cacheKey[] = 'childrenOf';
		$cacheKey[] = $parentClassName;
		
		return self::getAllClassesHelper($cacheKey, function ($className) use ($parentClassName) {
			return is_subclass_of($className, $parentClassName);
		});
	}
	
	/**
	 * Returns all classes implementing specified interface
	 * 
	 * @param string interface name
	 * @return array of fully qualified class names (with namespace)
	 */
	public static function getAllClassesImplementing($interfaceName) {
		$cacheKey = array();
		$cacheKey[] = 'implementing';
		$cacheKey[] = $interfaceName;
		
		return self::getAllClassesHelper($cacheKey, function ($className) use ($interfaceName) {
			$class = new Nette\Reflection\ClassType($className);

			return $class->implementsInterface($interfaceName) && $interfaceName != $className;
		});
	}
	
	private static function getAllClassesHelper($cacheKey, $cb) {
		// TODO: Cache from DI
		$cacheStorage = new Nette\Caching\Storages\FileStorage(TEMP_DIR);
		$cache = new Nette\Caching\Cache($cacheStorage, 'Nette.RobotLoader');
		
		if(isset($cache[$cacheKey])) {
			return $cache[$cacheKey];
		} else {
			$children = array();
			$classes = self::getIndexedClasses();
			foreach($classes as $className) {
				if($cb($className)) {
					$children[] = $className;
				}
			}
			
			$cache->save($cacheKey, $children);
			return $children;
		}
	}
	
	/**
	 * Returns array of all indexed classes
	 *
	 * @return array of fully qualified class names (names with namespace)
	 */
	public static function getIndexedClasses() {
		$loaders = Nette\Loaders\AutoLoader::getLoaders();
		$classes = array();
		if(count($loaders) > 0) {
			foreach($loaders as $loader) {
				if($loader instanceof Nette\Loaders\RobotLoader) {
					$classes = \array_keys($loader->getIndexedClasses());
					break;
				}
			}
		}

		if(count($classes) == 0)
			$classes = get_declared_classes();
			
		return $classes;
	}
	
	
	
}