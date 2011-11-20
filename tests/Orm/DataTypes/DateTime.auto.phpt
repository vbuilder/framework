<?php
/**
 * Test: Test of auto date of creation / modification
 *
 * @author Adam Staněk (V3lbloud)
 * @since Nov 19, 2011
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

require __DIR__ . '/../../bootstrap.php';

use vBuilder, Nette, dibi,
	 vBuilder\Orm\ActiveEntity,
	 vBuilder\Test\Assert; 

$db = $context->connection;
$db->query(
	"CREATE TEMPORARY TABLE [TestEntityTable] (".
	"	[id] int(11) NOT NULL AUTO_INCREMENT,".
    "	[name] varchar(255),".
	"	[created] datetime,".
	"	[modified] datetime,".
	   	  
	"	PRIMARY KEY([id])".
	");"
);

/**
 * @Table(name="TestEntityTable")
 *
 * @Column(id, id, type="integer", generatedValue)
 * @Column(name, type="string")
 * @Column(created, type="CreatedDateTime")
 * @Column(modified, type="ModifiedDateTime")
 */
class TestEntity extends ActiveEntity { }

// -------------------------------------------------------------------

$now = new \DateTime;

$e1 = $context->repository->create(__NAMESPACE__ . '\\TestEntity');
$e1->name = 'Hawkey';
$e1->save();

// Pri vytvoreni se musi ulozit i aktualni datum a cas vytvoreni zaznamu
$c1 = $e1->created;
Assert::true($now->diff($e1->created)->format('%s') < 180);
Assert::same($e1->created->format('Y-m-d H:i:s'), $db->select('created')->from(TestEntity::getMetadata()->getTableName())->fetchSingle());

// Pri zalozeni noveho zaznamu se musi aktualizovat i datum modifikace => melo by byt stejne s datem vytvoreni
$m1 = $e1->modified;
Assert::equal($e1->created, $e1->modified);
Assert::same($e1->modified->format('Y-m-d H:i:s'), $db->select('modified')->from(TestEntity::getMetadata()->getTableName())->fetchSingle());

// -------------------------------------------------------------------

$e1->name = 'Trapper';
sleep(1); $e1->save();

// Pri zmene se cas vytvoreni nesmi zmenit
$c2 = $e1->created;
Assert::equal($c1, $e1->created);
Assert::same($c1->format('Y-m-d H:i:s'), $db->select('created')->from(TestEntity::getMetadata()->getTableName())->fetchSingle());

// Cas modifikace musi byt vetsi
$m2 = $e1->modified;
Assert::true($m1->getTimestamp() < $e1->modified->getTimestamp());
Assert::same($e1->modified->format('Y-m-d H:i:s'), $db->select('modified')->from(TestEntity::getMetadata()->getTableName())->fetchSingle());

// -------------------------------------------------------------------

sleep(1); $e1->save();

// Pokud se zadna data nezmeni, nesmi se zmenit ani datum modifikace / vytvoreni
Assert::equal($c2, $e1->created);
Assert::same($c2->format('Y-m-d H:i:s'), $db->select('created')->from(TestEntity::getMetadata()->getTableName())->fetchSingle());

Assert::equal($m2, $e1->modified);
Assert::same($e1->modified->format('Y-m-d H:i:s'), $db->select('modified')->from(TestEntity::getMetadata()->getTableName())->fetchSingle());
