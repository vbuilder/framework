<?php

/**
 * Test of Cron
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jun 15, 2011
 *
 * @package    vBuilder\Utils
 * @subpackage UnitTests
 *
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

use vBuilder\Utils\Cron,
	 vBuilder\CronException,
	 vBuilder\Test\Assert;

require __DIR__.'/../bootstrap.php';

class TestCron extends Cron {
	
	static $_cache;
	
	static protected function getCache() {
		
		if(!isset(static::$_cache)) {
			static::$_cache = new Nette\Caching\Storages\MemoryStorage();
		}
		
		return new Nette\Caching\Cache(static::$_cache);
		
	}
	
}

// Overload of caching storage because Cron is remembering it's last call and
// it can't collide with existing install
@Nette\Environment::getContext()->removeService('Nette\\Caching\\ICacheStorage');
Nette\Environment::getContext()->addService('Nette\\Caching\\ICacheStorage', 'Nette\\Caching\\Storages\\MemoryStorage');


$date = new \DateTime('2011-06-15 21:34:16');
TestCron::run($date);

exit;

// Test, pokud se ignoruji "jiz zaregistrovana" volani (zpatky do minulosti)
try {
	TestCron::run($date->sub(\DateInterval::createFromDateString('7 days')));
	Assert::fail('Expected exception');
} catch(Exception $e) {
	Assert::exception('vBuilder\CronException', null, $e);
}

// Test, jestli se ignoruji volani po mene jak hodine
try {
	TestCron::run($date->add(\DateInterval::createFromDateString('7 days'))->add(\DateInterval::createFromDateString('30 minutes')));
	Assert::fail('Expected exception');
} catch(Exception $e) {
	Assert::exception('vBuilder\CronException', null, $e);
}

// Test, jeslti se spravne uchovava $lastTimeCalled a na volani hourly handleru
try {
	TestCron::$hourly[] = function ($lastTimeCalled, $currentTime) {
		$d1 = new \DateTime('2011-06-16 22:04:16');
		$d2 = new \DateTime('2011-06-15 21:34:16');
		
		Assert::equal($d1, $currentTime);		
		Assert::equal($d2, $lastTimeCalled);
		
		throw new \LogicException;
	};
	
	TestCron::run($date->add(\DateInterval::createFromDateString('1 days')));
	Assert::fail('Expected exception');
} catch(Exception $e) {
	Assert::exception('\LogicException', null, $e);
}

// Denni volani
try {
	TestCron::$daily[] = function ($lastTimeCalled, $currentTime) {
		Assert::equal(null, $lastTimeCalled);
		
		throw new \LogicException;
	};
	
	TestCron::$hourly = array();
	TestCron::run(new \DateTime('2011-06-17 00:16:00'));
	Assert::fail('Expected exception');
} catch(Exception $e) {
	Assert::exception('\LogicException', null, $e);
}

// Mesicni volani
try {
	TestCron::$monthly[] = function ($lastTimeCalled, $currentTime) {
		Assert::equal(null, $lastTimeCalled);
		
		throw new \LogicException;
	};
	
	TestCron::$daily = array();
	TestCron::run(new \DateTime('2011-07-01 00:16:00'));
	Assert::fail('Expected exception');
} catch(Exception $e) {
	Assert::exception('\LogicException', null, $e);
}
