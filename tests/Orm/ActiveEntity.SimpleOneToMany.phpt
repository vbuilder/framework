<?php
/**
 * Test: Test of ORM ActiveEntity OneToMany relation (table based)
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
 * @Column(id, name="id", type="integer", generatedValue)
 * @Column(name="name", type="string")
 * @Column(name="roles", type="OneToMany", table="TestEntityTableList", joinUsing="id")
 * @Column(name="complex", type="OneToMany", table="TestEntityTableList2", joinUsing="id")
 */
class TestEntity extends ActiveEntity { }

// =============================================================================

$e1 = new TestEntity(1);
Assert::same(array("foo", "bar"), $e1->getRoles());

// Prazdny join ---------------------------------------
$e2 = new TestEntity(2);
Assert::same(array(), $e2->getRoles()); 

// Komplexnni join ------------------------------------
Assert::same(array(array('a' => 'foo', 'b' => 'bar')), $e2->getComplex()); 

// Kontrola mazani spolu s entitou --------------------
$e1->delete();
Assert::same(0, count(dibi::select('*')->from('TestEntityTableList')->fetchAll()));

// Pridani novych polozek -----------------------------
$expectedRoles = array("foo", "bar");
$e2->setRoles($expectedRoles);
$e2->save();

$actual = dibi::select('*')->from('TestEntityTableList')->fetchAll();
foreach($actual as $curr) {	
	if(in_array($curr["name"], $expectedRoles))
		$expectedRoles = array_diff($expectedRoles, array($curr["name"]));
	else
		Assert::fail("Unexpected joined entity '".$curr["name"]."' found");
}
Assert::same(0, count($expectedRoles));

// Zmena polozek (complex) ------------------------------------
$expected = array(array('a' => 'foo2', 'b' => 'bar2'), array('a' => 'foo3', 'b' => 'bar3'));
$e2->setComplex($expected);
$e2->save();

$actual = dibi::select('*')->from('TestEntityTableList2')->fetchAll();
foreach($actual as $curr) {
	$found = false;
	foreach($expected as $key=>$curr2) {
		$same = true;
		foreach($curr2 as $col=>$val) {
			if($val != $curr[$col]) {
				$same = false;
				break;
			}
		}
		
		if($same) {
			unset($expected[$key]);
			$found = true;
			break;
		}
	}
	
	if(!$found)
		Assert::fail("Unexpected joined entity found");
}
Assert::same(0, count($expected));

// Ulozeni pri vytvareni nove polozky ----------------------------
$expected = array(123, 345);
$e3 = new TestEntity;
$e3->name = "NewEntity";
$e3->setRoles($expected);
$e3->save();

$actual = dibi::select('*')->from('TestEntityTableList')->where("[id] = %s", $e3->id)->fetchAll();

foreach($actual as $curr) {	
	if(in_array($curr["name"], $expected))
		$expected = array_diff($expected, array($curr["name"]));
	else
		Assert::fail("Unexpected joined entity '".$curr["name"]."' found");
}
Assert::same(0, count($expected));
