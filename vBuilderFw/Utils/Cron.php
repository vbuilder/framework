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

use vBuilder, Nette, vBuilder\CronException;

/**
 * Class for maintaining scheduled tasks. For use simple set up cron script to run
 * Cron::run() method **every hour**. This class takes care of emitting propper triggers.
 * 
 * Class checks, that only one cron job is running at the same time,
 * so operations are atomic safe and script is also protected against DoS attacks
 * thanks to last time call checking.
 * 
 * Handlers can be registred as:
 * <code>
 * vBuilder\Utils\Cron::$daily = function ($lastTimeCalled, $currentTime) {
 *		// My daily work
 * };
 * </code>
 * 
 * Given parameters are instance of \DateTime class or null if function has not
 * been called yet.
 * 
 * **Warning: ** $lastTimeCalled is actually last time this signal was emitted,
 * not the time current handler function has been called.
 * 
 * TODO: Mozna by stalo za to, radeji posilat cas kdy byla opravdu volana ta funkce.
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 15, 2011
 */
class Cron extends vBuilder\Object {
	
	/** @var array of handlers which are called every hour */
	static public $hourly = array();
	
	/** @var array of handlers which are called every day at midnight */
	static public $daily = array();
	
	/** @var array of handlers which are called at midnight on the last day of the month */
	static public $monthly = array();
	
	/**
	 * This function is meant to be called **hourly** by cron.
	 * It calls all registred handlers and it also takes care of emiting trigger
	 * signals for longer periods of time than on hourly basis (daily, weekly, ...).
	 * 
	 * @param \DateTime forced date time (for testing purposes only)
	 */
	static public function run(\DateTime $now = null) {	
		try {
			Nette\Utils\CriticalSection::enter();
		} catch(Nette\InvalidStateException $e) {
			if($e->getMessage() == 'Critical section has already been entered.')
				throw new CronException('Cron job is already running');
			else
				throw $e;
		}
		
		try {
			$cache = Nette\Environment::getCache(str_replace('\\', '.', get_called_class()));
			if($now === null) $now = new \DateTime('now');

			// Hodinova volani =======================================================
			if(isset($cache['lastHourly'])) {
				// Pocet minut od posledni zmeny
				$offset = (intval($now->format('U')) - intval($cache['lastHourly']->format('U'))) / 60;

				if($offset < 1)
					throw new CronException('Invalid time offset. Current date is earlier than already processed.');

				if($offset < 60)
					throw new CronException('Invalid time offset. Method was called too early. Probably because of clock skew.');
			} else
				$cache['lastHourly'] = null;

			self::hourly($cache['lastHourly'], $now);
			$cache['lastHourly'] = clone $now;

			// Denni a mesicni volani ================================================
			if(intval($now->format('H')) == 0) {
				self::daily(isset($cache['lastDaily']) ? $cache['lastHourly'] : null, $now);
				$cache['lastDaily'] = clone $now;

				if(intval($now->format('d')) == 1) {
					self::monthly(isset($cache['lastMonthly']) ? $cache['lastMonthly'] : null, $now);
					$cache['lastMonthly'] = clone $now;
				}
			}
			
			Nette\Utils\CriticalSection::leave();
			
		} catch(\Exception $e) {
			Nette\Utils\CriticalSection::leave();
			throw $e;
		}
	}
	
}
