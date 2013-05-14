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

use vBuilder,
	Nette,
	Nette\Utils\Strings;

/**
 * Listener for vBuilder\Object events
 *
 * @author Adam Staněk (V3lbloud)
 * @since Oct 22, 2011
 */
class EventListener extends vBuilder\Object {
	
	/**
	 * Registers all object's events to matching methods
	 * 
	 * @param vBuilder\Object $object 
	 */
	public function register(Nette\Object $object) {
		$rc = $object->getReflection();
		$rc2 = $this->getReflection();
		$publicProperties = $rc->getProperties(\ReflectionProperty::IS_PUBLIC);
		
		foreach($publicProperties as $property) {
			$eventName = $property->getName();
			if(!preg_match('#^on[A-Z]#', $eventName)) continue;
			
			
			if($rc2->hasMethod($eventName)) {
				array_push($object->$eventName, array($this, $eventName));
			}
		}
	}
	
}
