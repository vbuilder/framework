<?php
/**
 * Test: Versionable behavior of ORM active entity
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

$db = $context->connection;

class TestException extends \LogicException { }

$db->query(
	"CREATE TEMPORARY TABLE [TestEntityTable] (".
	"	[id] int(11) NOT NULL,".
	"	[revision] int(11) NOT NULL,".
   "	[name] varchar(255),".
		  
	"	KEY([id], [revision])".
	");"
);

/**
 * @Table(name="TestEntityTable")
 * 
 * @Behavior(Versionable, idCol = "id", revisionCol = "revision")
 * 
 * @Column(id, pk, type="integer")
 * @Column(revision, pk, type="integer")
 * @Column(name, type="string")
 */
class TestEntity extends ActiveEntity { }

// =============================================================================

// Test ulozeni nove entity a auto-id
$te = new TestEntity($context);
$te->name = 'Lorem ipsum';
$te->save();
Assert::arrayEqual(array(array("id" => 1, "revision" => 1, "name" => "Lorem ipsum")), $db->query('SELECT * FROM [TestEntityTable]')->fetchAll());

// Test, ze se nevytvari nova verze, kdyz se nezmenila data
$te->save();
Assert::arrayEqual(array(array("id" => 1, "revision" => 1, "name" => "Lorem ipsum")), $db->query('SELECT * FROM [TestEntityTable]')->fetchAll());

// Test inkrementace revize a ponechani stare (zmena znamenka)
$te->name = 'Lorem ipsum 2';
$te->save();
Assert::arrayEqual(array(
				array("id" => 1, "revision" => -1, "name" => "Lorem ipsum"),
				array("id" => 1, "revision" => 2, "name" => "Lorem ipsum 2")
		  ), $db->query('SELECT * FROM [TestEntityTable] ORDER BY [revision]')->fetchAll());

// Test, ze dalsi zaznam neovlivnuje cisla revizi
$te2 = new TestEntity($context);
$te2->name = 'Some other record';
$te2->save();
Assert::arrayEqual(array(
				array("id" => 1, "revision" => -1, "name" => "Lorem ipsum"),
				array("id" => 1, "revision" => 2, "name" => "Lorem ipsum 2"),
				array("id" => 2, "revision" => 1, "name" => "Some other record")
		  ), $db->query('SELECT * FROM [TestEntityTable] ORDER BY [revision]')->fetchAll());

// Test, ze se zmena revize neprovede v pripade, ze je vyvolana chyba pri ukladani
// (napr. vztaznou entitou, nebo nejakym eventem)
$te2->name = 'Changed title';
$te2->onPostSave[] = function ($entity) {
	throw new TestException('Pre save called');
};

try {
	$te2->save();
	
	Assert::fail('Pre save event not emitted (expected exception)');
} catch(\Exception $e) {
	Assert::exception(__NAMESPACE__ . '\\TestException', null, $e);
}

// Nesmi se zmenit ani data, ale hlavne ani revize
Assert::arrayEqual(array(
	 array("id" => 2, "revision" => 1, "name" => "Some other record")
), $db->query('SELECT * FROM [TestEntityTable] WHERE [id] = %i', $te2->id, 'ORDER BY [revision]')->fetchAll());