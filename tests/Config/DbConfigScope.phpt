<?php

/**
 * Test of database configuration scope
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 4, 2011
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
namespace vBuilder\Config\Tests;

use vBuilder\Test\Assert,
	 vBuilder\Config\DbConfigScope,
	 Nette;

require __DIR__.'/../bootstrap.php';

class TestDbConfigScope extends DbConfigScope {
	
	function __construct(Nette\DI\IContainer $context) {
		$globalScope = new DbConfigScope($context, 'global');		
		parent::__construct($context, null, $globalScope);
		
		// Fallback na global
		Assert::same(array('a'), $this->getKeys());
		
		// Zmena na usera s UID 1
		$this->setScopeName('user(1)');
		Assert::arrayEqual(array('a', 'b', 'd'), $this->getKeys());
		Assert::equal(11, $this->b);
		
		// Zmena na usera s UID 2
		$this->setScopeName('user(2)');
		Assert::arrayEqual(array('a', 'b', 'c'), $this->getKeys());
		Assert::equal(22, $this->b);
	}
	
}

$context->connection->loadFile(__DIR__ . '/testdata.sql');
new TestDbConfigScope($context);