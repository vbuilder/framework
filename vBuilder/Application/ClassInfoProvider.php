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

namespace vBuilder\Application;

use vBuilder,
	vBuilder\Utils\Strings,
	Nette,
	Composer;

/**
 * Provider of class information
 *
 * @author Adam Staněk (velbloud)
 * @since Apr 14, 2013
 */
class ClassInfoProvider extends Nette\Object {

	/** @var Nette\DI\Container DI context container */
	protected $context;

	public function __construct(Nette\DI\Container $context) {
		$this->context = $context;
	}

	/**
	 * Returns all child classes of specified parent.
	 * Results are cached along with robot cache.
	 *
	 * @param string parent class name
	 * @return array of fully qualified class names (with namespace)
	 */
	public function getAllChildrenOf($parentClassName) {
		$cacheKey = array();
		$cacheKey[] = 'childrenOf';
		$cacheKey[] = $parentClassName;

		return $this->getAllClassesHelper($cacheKey, function ($className) use ($parentClassName) {
			return is_subclass_of($className, $parentClassName);
		});
	}

	/**
	 * Returns all classes implementing specified interface
	 *
	 * @param string interface name
	 * @return array of fully qualified class names (with namespace)
	 */
	public function getAllClassesImplementing($interfaceName) {
		$cacheKey = array();
		$cacheKey[] = 'implementing';
		$cacheKey[] = $interfaceName;

		return $this->getAllClassesHelper($cacheKey, function ($className) use ($interfaceName) {
			$class = new Nette\Reflection\ClassType($className);

			return $class->implementsInterface($interfaceName) && $interfaceName != $className;
		});
	}

	private function getAllClassesHelper($cacheKey, $cb) {
		$cache = new Nette\Caching\Cache($this->context->cacheStorage, 'Nette.RobotLoader');

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
		$loaders = (array) spl_autoload_functions();

		$classes = array();
		foreach($loaders as $loader) {
			if(!is_array($loader)) continue;
			if($loader[0] instanceof Nette\Loaders\RobotLoader) {
				foreach($loader[0]->getIndexedClasses() as $class => $file) {
					if($file && !in_array($class, $classes))
						$classes[] = $class;
				}

			} elseif($loader[0] instanceof Composer\Autoload\ClassLoader) {
				foreach($loader[0]->getClassMap() as $class => $file) {
					if($file && !in_array($class, $classes))
						$classes[] = $class;
				}
			}
		}

		if(count($classes) == 0)
			$classes = get_declared_classes();

		return $classes;
	}

}