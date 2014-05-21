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
 * Event dispatcher
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 3, 2014
 */
class Dispatcher extends Nette\Object {

	const SOURCE         = 0;
	const EVENT 	 	 = 1;
	const SUBSCRIBER 	 = 2;
	const INVOKE_COUNTER = 3;

	const ALL_EVENTS = NULL;

	/** @var bool should we perform checks if event wasn't triggered before subscription? */
	private $lateChecking;

	/** @var array of triggered events */
	private $eventLog = array();
	private $events = array();

	public function __construct(Nette\DI\Container $context) {
		$this->lateChecking = $context->parameters['debugMode'];
	}


	public function subscribe($source, $event, $subscriber = NULL, $ignoreLateCheck = FALSE) {

		if(func_num_args() == 2) {
			$subscriber = $event;
			$event = self::ALL_EVENTS;

		} else {
			$this->checkEvent($event);
		}

		// Source has to be an object or class name
		$this->checkSource($source);

		// Subscriber has to be an object or callable
		if(!is_object($subscriber) && !is_callable($subscriber))
			throw new Nette\InvalidArgumentException("Invalid subscriber given");

		// Check if any matching event was not triggered before
		if($this->lateChecking && !$ignoreLateCheck) {
			foreach($this->events as $item) {
				if($this->match($item, $source, $event) && $item[self::INVOKE_COUNTER] > 0)
					throw new Nette\InvalidStateException("Event already fired");
			}
		}

		// Subscribe
		$this->events[] = array(
			self::SOURCE => $source,
			self::EVENT  => $event,
			self::SUBSCRIBER => $subscriber,
			self::INVOKE_COUNTER => 0
		);
	}

	public function notify($sender, $event) {
		$this->checkEvent($event);
		$this->checkSource($sender);

		$args = array_slice(func_get_args(), 2);
		array_unshift($args, $sender);
		$result = NULL;

		foreach($this->events as &$item) {
			if($this->match($item, $sender, $event)) {
				$item[self::INVOKE_COUNTER]++;

				// Closures
				if(is_callable($item[self::SUBSCRIBER])) {
					$result = call_user_func_array($item[self::SUBSCRIBER], $args);
				}

				// Nette callback
				elseif($item[self::SUBSCRIBER] instanceof Nette\Callback || $item[self::SUBSCRIBER] instanceof Nette\Utils\Callback) {
					$result = $item[self::SUBSCRIBER]->invokeArgs($args);
				}

				// Generic objects
				else {
					$result = call_user_func_array(array($item[self::SUBSCRIBER], $event), $args);
				}

				if($result !== NULL) break;
			}
		}

		return $result;
	}

	protected function checkSource($source) {
		if(!is_object($source)) {
			if(!is_scalar($source))
				throw new Nette\InvalidArgumentException("Invalid source given");
			elseif(!class_exists($source))
				throw new Nette\InvalidArgumentException("Class name " . var_export($source, true) . " does not exist");
		}
	}

	protected function checkEvent($event) {
		if($event != self::ALL_EVENTS && (!is_scalar($event) || !preg_match('/[a-z0-9_\\-]+/i', $event)))
			throw new Nette\InvalidArgumentException("Invalid event name given");
	}

	protected function match($item, $source, $event) {
		if($item[self::SOURCE] != $source) {
			
			// Pokud je vzor konkretni objekt
			if(is_object($item[self::SOURCE]))
				return false;

			// Pokud dany zdroj neni vzorem ani jeho potomkem
			if(!is_a($source, $item[self::SOURCE], true))
				return false;
		}

		if($item[self::EVENT] != self::ALL_EVENTS) {
			if($item[self::EVENT] != $event)
				return false;
		}

		return true;
	}

}