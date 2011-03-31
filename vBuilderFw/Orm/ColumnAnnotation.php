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

/**
 * Class for parsing @Column annotation
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 17, 2011
 */
class ColumnAnnotation extends Nette\Reflection\Annotation {
	
	private $metadata = array();
	
	public function __construct(array $values) {		
		foreach($values as $k => $v) {
			if(is_numeric($k)) $this->metadata[$v] = true;
			else $this->metadata[$k] = $v;
		}
	}
	
	public function getMetadata() {
		return $this->metadata;
	}
	
	/**
	 * Returns string reprezentation
	 * @return string
	 */
	public function __toString() {
		return '';
	}
	
}
