<?php
/**
 * Test: Test of saving OneToOne related entities
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

use vBuilder, Nette, dibi,
	 vBuilder\Orm\Repository,
	 vBuilder\Test\Assert;

require __DIR__ . '/Relation.OneToOne.inc.php';

dibi::query('TRUNCATE TABLE [TestEntity_profile]');
dibi::query('TRUNCATE TABLE [TestEntity_address]');

// =============================================================================

$e1 = new TestEntity;
$e1->name = 'Jan';
$e1->surname = 'Noha';

$o1 = new OneToOneEntity;
$o1->street = 'Horni 12';
$o1->city = 'Domazlice';

$e1->address = $o1;
$e1->save();

Assert::arrayEqual(array(array(
	 'id' => 1,
	 'name' => 'Jan',
	 'surname' => 'Noha',
	 'address' => 1
)), dibi::query("SELECT * FROM [TestEntity_profile]")->fetchAll());

Assert::arrayEqual(array(array(
	 'id' => 1,
	 'street' => 'Horni 12',
	 'city' => 'Domazlice'
)), dibi::query("SELECT * FROM [TestEntity_address]")->fetchAll());

// Změna na jinou OneToOne entitu se zachováním původní ************************

$o2 = new OneToOneEntity;
$o2->street = 'Spodní 13';
$o2->city = 'Domazlice';

$e1->address = $o2;
$e1->save();

Assert::arrayEqual(array(array(
	 'id' => 1,
	 'name' => 'Jan',
	 'surname' => 'Noha',
	 'address' => 2
)), dibi::query("SELECT * FROM [TestEntity_profile]")->fetchAll());

Assert::arrayEqual(array(array(
	 'id' => 1,
	 'street' => 'Horni 12',
	 'city' => 'Domazlice'
), array(
	 'id' => 2,
	 'street' => 'Spodní 13',
	 'city' => 'Domazlice'
)), dibi::query("SELECT * FROM [TestEntity_address]")->fetchAll());

// Ulozeni OneToOne entity skrze hlavni entitu *********************************

$e1->address->street = 'Spodní 14';
$e1->address->save();
//$e1->save();

Assert::arrayEqual(array(array(
	 'id' => 1,
	 'name' => 'Jan',
	 'surname' => 'Noha',
	 'address' => 2
)), dibi::query("SELECT * FROM [TestEntity_profile]")->fetchAll());

Assert::arrayEqual(array(array(
	 'id' => 1,
	 'street' => 'Horni 12',
	 'city' => 'Domazlice'
), array(
	 'id' => 2,
	 'street' => 'Spodní 14',
	 'city' => 'Domazlice'
)), dibi::query("SELECT * FROM [TestEntity_address]")->fetchAll());

// Akceptace a ulozeni NULL hodnoty ********************************************

$e1->address = null;
$e1->save();

Assert::arrayEqual(array(array(
	 'id' => 1,
	 'name' => 'Jan',
	 'surname' => 'Noha',
	 'address' => null
)), dibi::query("SELECT * FROM [TestEntity_profile]")->fetchAll());