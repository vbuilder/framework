<?php
/**
 * Test: Test of ORM repository
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
	 vBuilder\Orm\Repository,
	 vBuilder\Orm\ActiveEntity,
	 vBuilder\Test\Assert; 

dibi::query(
	"CREATE TEMPORARY TABLE [TestEntityTable] (".
	"	[id] int(11) NOT NULL AUTO_INCREMENT,".
   "	[name] varchar(255),".
		  
	"	PRIMARY KEY([id])".
	");"
);

dibi::insert('TestEntityTable', array('id' => 1, 'name' => 'A'))->execute();
dibi::insert('TestEntityTable', array('id' => 2, 'name' => 'B'))->execute();

/**
 * @Table(name="TestEntityTable")
 *
 * @Column(id, id, type="integer", generatedValue)
 * @Column(name, type="string")
 */
class TestEntity extends ActiveEntity {
	
}

// Test nacteni entity
$e1 = Repository::get('vBuilder\Orm\EntityTest\TestEntity', 1);
Assert::true($e1->exists());
Assert::equal('A', $e1->name);

// Test nacteni entity, co neni v DB
$e2 = Repository::get('vBuilder\Orm\EntityTest\TestEntity', 123);
Assert::false($e2->exists());

// Test neexistujici entity
try {
	$e2 = Repository::get('vBuilder\Orm\EntityTest\BlaBla', 2);
	Assert::fail('Non existing entity test failed (Expected exception)');
} catch(\Exception $exception) {
	Assert::exception('vBuilder\Orm\EntityException', null, vBuilder\Orm\EntityException::ENTITY_TYPE_NOT_DEFINED, $exception);
}

// Warning: Pro dibi::$numOfQueries musi byt aktivovan profiler
$num = dibi::$numOfQueries;
$all = Repository::findAll('vBuilder\Orm\EntityTest\TestEntity')->orderBy('id')->fetchAll();
Assert::equal(2, count($all));
Assert::equal('A', $all[0]->name);
Assert::equal('B', $all[1]->name);
Assert::equal(1, dibi::$numOfQueries - $num); // Pocet dotazu (kontrola, ze se vse nacetlo jednim selectem)

// Test, ze se zaznamy z listingu ulozi a nejsou nacitany znova pri getu
$num = dibi::$numOfQueries;
$e2 = Repository::get('vBuilder\Orm\EntityTest\TestEntity', 2);
Assert::equal('B', $e2->name);
Assert::equal($num, dibi::$numOfQueries);

debug('My var', $all);