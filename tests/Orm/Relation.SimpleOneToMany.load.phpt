<?php
/**
 * Test: Test of load in OneToMany relation (table based)
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

dibi::query(
	"CREATE TEMPORARY TABLE [TestEntityTableList2] (".
	"	[id] int(11) NOT NULL,".
   "	[a] varchar(255),".
	"	[b] varchar(255),".
		  
	"	KEY (`id`)".
	");"
);

dibi::insert('TestEntityTable', array('id' => 1, 'name' => 'A'))->execute();
dibi::insert('TestEntityTable', array('id' => 2, 'name' => 'B'))->execute();
dibi::insert('TestEntityTableList', array('id' => 1, 'name' => 'foo'))->execute();
dibi::insert('TestEntityTableList', array('id' => 1, 'name' => 'bar'))->execute();
dibi::insert('TestEntityTableList2', array('id' => 2, 'a' => 'foo', 'b' => 'bar'))->execute();

/**
 * @Table(name="TestEntityTable")
 *
 * @Column(id, id, type="integer", generatedValue)
 * @Column(name, type="string")
 * @Column(roles, type="OneToMany", table="TestEntityTableList", joinUsing="id")
 * @Column(complex, type="OneToMany", table="TestEntityTableList2", joinUsing="id")
 */
class TestEntity extends ActiveEntity { }

// =============================================================================

$e1 = Repository::get(__NAMESPACE__ . '\\TestEntity', 1);
Assert::equal('A', $e1->name);
Assert::true($e1->roles instanceOf vBuilder\Orm\Collection);
Assert::arrayEqual(array("foo", "bar"), $e1->getRoles());

// Prazdny join ---------------------------------------
$e2 = new TestEntity(2);
Assert::arrayEqual(array(), $e2->getRoles()); 

// Komplexnni join ------------------------------------
Assert::arrayEqual(array(array('a' => 'foo', 'b' => 'bar')), $e2->getComplex()); 