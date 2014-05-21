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

namespace vBuilder\Events;

use vBuilder,
	Nette;

/**
 * Basic implementation of observable object
 * Use "inject: true" in your service configuration
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 3, 2014
 */
class Observable extends Nette\Object {

	/*** @var EventDispatcher */
	private $_eventDispatcher;

	public function addObserver($event, $observer = NULL) {
		return $this->_eventDispatcher->subscribe($this, $event, $observer);
	}

	protected function notifyObservers($event) {
		$args = func_get_args();
		array_unshift($args, $this);

		return call_user_func_array(array($this->_eventDispatcher, 'notify'), $args);
	}

	public function injectEventDispatcher(Dispatcher $dispatcher) {
		$this->_eventDispatcher = $dispatcher;
	}

	public function getEventDispatcher() {
		return $this->_eventDispatcher;
	}

}