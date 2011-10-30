<?php

/**
 * Test of user registration and login
 *
 * @author Adam Staněk (V3lbloud)
 * @since Oct 30, 2011
 *
 * @package    vBuilder\Security
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

use vBuilder\Test\Assert;

require __DIR__ . '/../bootstrap.php';

$config = $context->config->get('security.user')->toArray();

dd($config);

exit;


$db = $context->connection;
$webUser = $context->user->setNamespace('vBuilder\Security\Test');

$user = $context->repository->create('vBuilder\Security\User');
$user->username = 'nesnesitelna.veverka';
$user->password = 'chlupyZdeky112';
$user->setBypassSecurityCheck(true);
$user->save();

$webUser->login('nesnesitelna.veverka', 'chlupyZdeky112');
Assert::true($webUser->isLoggedIn());

$webUser->logout(true);
