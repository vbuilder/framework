<?php
/**
 * Test: Test of load in OneToMany relation (entity based)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 27, 2011
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
	 vBuilder\Orm\Repository,
	 vBuilder\Test\Assert; 

dibi::query(
	"CREATE TEMPORARY TABLE [TestEntityTable] (".
	"	[id] int(11) NOT NULL AUTO_INCREMENT,".
   "	[name] varchar(255),".
		  
	"	PRIMARY KEY([id])".
	");"
);

dibi::query(
	"CREATE TEMPORARY TABLE [TestEntityTableList] (".
	"	[id] int(11) NOT NULL,".
   "	[name] varchar(255),".
		  
	"	KEY (`id`)".
	");"
);

dibi::insert('TestEntityTable', array('id' => 1, 'name' => 'A'))->execute();
dibi::insert('TestEntityTable', array('id' => 2, 'name' => 'B'))->execute();
dibi::insert('TestEntityTableList', array('id' => 1, 'name' => 'foo'))->execute();
dibi::insert('TestEntityTableList', array('id' => 1, 'name' => 'bar'))->execute();

/**
 * @Table(name="TestEntityTableList")
 *
 * @Column(id, id, type="integer")
 * @Column(name, id, type="string")
 */
class OneToManyEntity extends ActiveEntity { }

/**
 * @Table(name="TestEntityTable")
 *
 * @Column(id, id, type="integer", generatedValue)
 * @Column(name, type="string")
 * @Column(roles, type="OneToMany", entity="vBuilder\Orm\EntityTest\OneToManyEntity", joinUsing="id")
 */
class TestEntity extends ActiveEntity { }

// =============================================================================

$e1 = new TestEntity(1);

$expectedRoles = array("foo", "bar");
foreach($e1->getRoles() as $curr) {	
	Assert::true($curr instanceOf OneToManyEntity);
	
	if(in_array($curr->name, $expectedRoles))
		$expectedRoles = array_diff($expectedRoles, array($curr->name));
	else
		Assert::fail("Unexpected joined entity '".$curr->name."' found");
}

Assert::same(0, count($expectedRoles));

// Prazdny join ---------------------------------------
$e2 = new TestEntity(2);
Assert::arrayEqual(array(), $e2->getRoles()); 