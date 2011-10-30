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

namespace vBuilder\Application\UI;

use vBuilder,
		Nette,
		Nette\Application\UI\PresenterComponent;

/**
 * Nette link extended of component getter
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 30, 2011
 */
class Link extends Nette\Application\UI\Link {
	
	protected $_component;
	
	/**
	 * Link specification.
	 * @param  PresenterComponent
	 * @param  string
	 * @param  array
	 */
	public function __construct(PresenterComponent $component, $destination, array $params) {
		$this->_component = $component;

		parent::__construct($component, $destination, $params);
	}
	
	/**
	 * Gets anchored presenter component
	 * 
	 * @retunr PresenterComponent
	 */
	public function getComponent() {
		return $this->_component;
	}
	
	/**
	 * Sets anchored presenter component
	 * 
	 * @param PresenterComponent $component 
	 */
	public function setComponent(PresenterComponent $component) {
		$this->_component = $component;
	}
	
}

