<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vmanager.cz
 * 
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vManager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vManager. If not, see <http://www.gnu.org/licenses/>.
 */

require __DIR__.'/../bootstrap.php';

use vBuilder\Config\FileConfigScope;

$conf = new FileConfigScope(array(
	__DIR__.'/dummy.ini',
	__DIR__.'/dummy.neon'
));

Assert::same(true, $conf->dummyScope->bar);
Assert::same(':-/', $conf->dummyScope->baz);
Assert::same(1, $conf->dummyScope->moo);
Assert::same(':-)', $conf->anotherScope->bar);
Assert::same(true, $conf->anotherScope->moo);
Assert::same(1, $conf->anotherScope->foo);
Assert::same('baz', $conf->anotherScope->dummiestScope->bar);

try {
	$filename = __DIR__.'/nonExistent.ini';
	$conf = new FileConfigScope(array(
		$filename
	));
	Assert::fail('An InvalidStateException should have been thrown.');
} catch (\Nette\InvalidArgumentException $e) {
	Assert::exception($e, "Invalid file '$filename'.", $e);
}