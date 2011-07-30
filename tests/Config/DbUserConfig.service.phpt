<?php

/**
 * Test of user configuration service refresh
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jun 20, 2011
 *
 * @package    vBuilder\Config
 * @subpackage UnitTests
 *
 * This file is part of vBuilder Framework (vBuilder FW).
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vbuilder.cz
 * 
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU global Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU global Public License for more details.

 * You should have received a copy of the GNU global Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

use Nette\Environment,
	 vBuilder\Test\Assert;

require __DIR__.'/../bootstrap.php';

Environment::getContext()->addService('vBuilder\Config\IConfig',
		  array('vBuilder\Config\DbUserConfig', 'createUserConfig'));

Environment::getUser()->setAuthenticator(new Nette\Security\SimpleAuthenticator(array('user' => 'password')));

// Singleton
$config = Environment::getService('vBuilder\Config\IConfig');
$config2 = Environment::getService('vBuilder\Config\IConfig');
Assert::true($config === $config2);

// Refresh po loginu noveho uzivatele
Environment::getUser()->login('user', 'password');
$config2 = Environment::getService('vBuilder\Config\IConfig');
Assert::false($config === $config2);

// Refresh po logoutu uzivatele
Environment::getUser()->logout(true);
$config3 = Environment::getService('vBuilder\Config\IConfig');
Assert::false($config3 === $config2);