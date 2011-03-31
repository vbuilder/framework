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
 * Extended Nette\Object by static events
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 12, 2011
 */
class Object extends Nette\Object {

	/**
	 * Allowing to emit static events through public variable
	 * 
	 * @param string name
	 * @param array of args
	 * @return mixed 
	 */
	public static function __callStatic($name, $args) {
		$class = new Nette\Reflection\ClassReflection(get_called_class());
		$properties = $class->getStaticProperties();

		if(isset($properties[$name])) {
			if(is_array($list = $properties[$name]) || $list instanceof \Traversable) {
				foreach($list as $handler) {
					callback($handler)->invokeArgs($args);
				}
			}

			return NULL;
		}

		return parent::__callStatic($name, $args);
	}

}
