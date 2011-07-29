<?php

/**
 * This file is part of vBuilder CMS.
 *
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 *
 * For more information visit http://www.vbuilder.cz
 *
 * vBuilder is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vBuilder. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Loaders;

use vBuilder,
	 Nette;

/**
 * Nette robot loader extended by onRebuild event
 *
 * @author Adam Staněk (velbloud)
 * @since Jul 28, 2011
 */
class RobotLoader extends Nette\Loaders\RobotLoader {
	
	/** @var array of callbacks */
	public $onRebuild = array();
	
	public function rebuild() {
		parent::rebuild();
		
		$this->onRebuild();
	}
	
	/**
	 * Returns all child classes of specified parent.
	 * Results are cached along with robot cache.
	 * 
	 * @param string parent class name
	 * @return array of fully qualified class names (with namespace)
	 */
	public function getAllChildrenOf($parentClassName) {
		$cacheKey = $this->getKey();
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
		$cacheKey = $this->getKey();
		$cacheKey[] = 'implementing';
		$cacheKey[] = $interfaceName;
		
		return $this->getAllClassesHelper($cacheKey, function ($className) use ($interfaceName) {
			$class = new Nette\Reflection\ClassType($className);

			return $class->implementsInterface($interfaceName) && $interfaceName != $className;
		});
	}
	
	private function getAllClassesHelper($cacheKey, $cb) {
		$cache = $this->getCache();
		
		if(isset($cache[$cacheKey])) {
			return $cache[$cacheKey];
		} else {
			$children = array();
			$classes = array_keys($this->getIndexedClasses());
			foreach($classes as $className) {
				if($cb($className)) {
					$children[] = $className;
				}
			}
			
			$cache->save($cacheKey, $children);
			return $children;
		}
	}
	
}
