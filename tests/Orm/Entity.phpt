<?php
/**
 * Test: Test of ORM entity
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 18, 2011
 *
 * @package    vBuilder\Orm
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

namespace vBuilder\Orm\EntityTest;

require __DIR__ . '/../bootstrap.php';

use vBuilder, Nette,
	 vBuilder\Orm\Entity,
	 vBuilder\Test\Assert; 

/**
 * @Table(name="some_table")
 *
 * @Column(id, name="id", type="integer")
 * @Column(name="name", type="string")
 * @Column(name="someDt", type="DateTime")
 */
class TestEntity extends Entity {
	
	function getName() {
		return uniqid();
	}
	
	function getFoo() {
		if(($cached = $this->fieldCache("foo")) !== null) return $cached;

		$value = uniqid();

		return $this->fieldCache("foo", $value);
	}
	
}

$e = new TestEntity(array('id' => '123', 'someDt' => '2011-02-17 20:02:13'));

// Test necachovane polozky
if($e->getName() == $e->getName())
	Assert::fail('Failed test of non cached field');

// Test cachovane polozky
Assert::same($e->getFoo(), $e->getFoo());

// Test getteru a volani metody
Assert::same($e->getFoo(), $e->foo);

// Test defaultni getteru vs volani metody
Assert::same($e->id, $e->getId());

// Test datamapperu na primitivy (int)
Assert::same($e->id, 123);

// Test cachovani defaultniho getteru (Musim delat na nejakym neprimitivnim typu)
Assert::same($e->getSomeDt(), $e->getSomeDt());

// Test na neexistuji field pres getter/setter
try {
	$e->foobar;
	$e->foobar = uniqid();
	Assert::fail('Expected exception');
} catch(\Exception $exception) {
	Assert::exception('MemberAccessException', null, null, $exception);
}

// Test na neexistujici field pres metodu
try {
	$e->getFoobar();
	// $e->setFoobar(uniqid());
	Assert::fail('Expected exception');
} catch(\Exception $exception) {
	Assert::exception('MemberAccessException', null, null, $exception);
} 

// Test issetu
Assert::false(isset($e->foobar));
Assert::true(isset($e->id));
Assert::true(isset($e->foo));

// Unset je zakazany
try {
	unset($e->foo);

	Assert::fail('Expected exception');
} catch(\Exception $exception) { }

// Pokus o set nedefaulni polozky, ktera nema setter
try {
	$e->foo = uniqid();
	$e->setFoo(uniqid());
	
	Assert::fail('Expected exception');
} catch(\Exception $exception) {
	Assert::exception('MemberAccessException', null, null, $exception);
}

// Test setteru u cachovany polozky
$old = $e->id;
$e->id = 714;
if($old === $e->id)
	Assert::fail('Failed test of setting field value');