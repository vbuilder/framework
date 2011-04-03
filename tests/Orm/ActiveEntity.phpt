<?php
/**
 * Test: Test of ORM ActiveEntity
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

use vBuilder, Nette, dibi,
	 vBuilder\Orm\ActiveEntity,
	 vBuilder\Test\Assert; 

class TestException extends \LogicException { }

dibi::query(
	"CREATE TEMPORARY TABLE [TestEntityTable] (".
	"	[id] int(11) NOT NULL AUTO_INCREMENT,".
   "	[name] varchar(255),".
		  
	"	PRIMARY KEY([id])".
	");"
);

/**
 * @Table(name="TestEntityTable")
 *
 * @Column(id, name="id", type="integer", generatedValue)
 * @Column(name="name", type="string")
 */
class TestEntity extends ActiveEntity { }


// =============================================================================

$e = new TestEntity(array('id' => 123));
$e->onPreLoad[] = function() { throw new TestException('Data loaded'); };

// Test, lazy loadingu (existujici data nesmeji vyvolat nacteni)
try {
	$e->id;
} catch(\Exception $exception) {
	Assert::fail('Lazy load test failed');
} 

// Test lazy loadingu 2 (neexistujici data musi vyvolat nacteni)
try {
	$e->name;
	Assert::fail('Lazy load test 2 failed (Expected exception)');
} catch(\Exception $exception) {
	Assert::exception(__NAMESPACE__ . '\\TestException', null, null, $exception);
} 

// Test existence entity
$e2 = new TestEntity(array('id' => 1));
Assert::false($e2->exists());

try {
	$e2->name;
	Assert::fail('Non existing load test failed (Expected exception)');
} catch(\Exception $exception) {
	Assert::exception('vBuilder\Orm\EntityException', null, vBuilder\Orm\EntityException::NOT_FOUND, $exception);
}

// Test insertu
$e3 = new TestEntity();
$e3->name = 'A';
Assert::false($e3->exists());
$e3->save();
Assert::same($e3->id, 1);
Assert::true($e3->exists());

// Test updatu
$e3->name = 'B';
$e3->save();
Assert::same($e3->id, 1);
Assert::same($e3->name, 'B');

$e4 = new TestEntity();
$e4->name = 'Bla bla';
$e4->save();
Assert::same($e4->id, 2);

// Test mazani
$e3->delete();
$result = dibi::query("SELECT * FROM [TestEntityTable] WHERE [id] = %i", 1)->fetch();
Assert::false($result);
$result = dibi::query("SELECT * FROM [TestEntityTable] WHERE [id] = %i", 2)->fetch();
if($result === false) 
	Assert::fail("Delete test failed");