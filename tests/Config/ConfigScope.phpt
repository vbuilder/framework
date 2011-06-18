<?php

/**
 * Test of Configuration scope
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jun 17, 2011
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

namespace vBuilder\Config\UnitTests;

require __DIR__.'/../bootstrap.php';

use vBuilder,
	 vBuilder\Config\ConfigScope,
	 vBuilder\Test\Assert;

class ConfigTestScope2 extends ConfigScope {

	function load() {
		$this->data = array(
			 'nested' => array(
				  'a' => 'A',
				  'c' => array(
						'c0', 'c1', 'c2'
				  ),
				  'd' => null
			 ),
			 'somedefault' => 'defaultvalue'
		);
		
		$this->isLoaded = true;
	}

	function save() { }
	
	function getInternalData() {
		if(!$this->isLoaded) $this->load();
		return $this->data;
	}
	
}

class ConfigTestScope extends ConfigScope {

	function load() {
		$this->data = array(
			 1 => 123,
			 'foo' => 'bar',
			 'nested' => array(
				  'a' => 'AAA',
				  'b' => array(
						'b0', 'b1', 'b2'
				  )
			 )
		);
		
		$this->isLoaded = true;
	}
	
	function save() { }
	
	function getInternalData() {
		if(!$this->isLoaded) $this->load();
		return $this->data;
	}

}

$dao = new ConfigTestScope(new ConfigTestScope2);

// Jednoduche gettery
Assert::same(123, $dao->get(1));
Assert::same('bar', $dao->get('foo'));
Assert::same('AAA', $dao->get('nested.a'));
Assert::same('b0', $dao->get('nested.b.0')); 

// Fallback
Assert::same('defaultvalue', $dao->get('somedefault'));
Assert::same('c0', $dao->get('nested.c.0'));

// Null / Has
Assert::same(null, $dao->get('nested.d', 'lalala'));
Assert::true($dao->has('nested.d'));
Assert::same('lalala', $dao->get('nested.e', 'lalala')); 
Assert::false($dao->has('nested.e'));

// Set
$tmp = $dao->getInternalData();
$tmp2 = $dao->getFallbackScope()->getInternalData();
$tmp['nested']['c'][0] = 'CCCCCoo?';
Assert::same('c0', $dao->get('nested.c.0'));
$dao->set('nested.c.0', $tmp['nested']['c'][0]);
Assert::arrayEqual($tmp, $dao->getInternalData());
Assert::arrayEqual($tmp2, $dao->getFallbackScope()->getInternalData());
Assert::same($tmp['nested']['c'][0], $dao->get('nested.c.0'));
$tmp['x'][1][2][3] = 'x123';
$dao->set('x.1.2.3', $tmp['x'][1][2][3]);
Assert::same($tmp['x'][1][2][3], $dao->get('x.1.2.3'));

// Unset (remove)
$dao->remove('x.1.2.3');
unset($tmp['x']);
Assert::arrayEqual($tmp, $dao->getInternalData());
$dao->remove('nested.b.0');
unset($tmp['nested']['b'][0]);
Assert::arrayEqual($tmp, $dao->getInternalData());