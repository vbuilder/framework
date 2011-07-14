<?php

/**
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


use dibi,
	 vBuilder\Orm\ActiveEntity;

dibi::query(
	"CREATE TEMPORARY TABLE [TestEntity_profile] (".
	"	[id] int(11) NOT NULL AUTO_INCREMENT,".
   "	[name] varchar(255),".
	"	[surname] varchar(255),".
	"	[address] int(11),".
		  
	"	PRIMARY KEY([id])".
	");"
);

dibi::query(
	"CREATE TEMPORARY TABLE [TestEntity_address] (".
	"	[id] int(11) NOT NULL AUTO_INCREMENT,".
   "	[street] varchar(255),".
	"	[city] varchar(255),".
		  
	"	PRIMARY KEY ([id])".
	");"
);

dibi::insert('TestEntity_profile', array('name' => 'Jan', 'surname' => 'Noha', 'address' => 1))->execute();
dibi::insert('TestEntity_address', array('street' => 'Dolní', 'city' => 'Domažlice'))->execute();
dibi::insert('TestEntity_profile', array('name' => 'Iveta', 'surname' => 'Blažková', 'address' => 2))->execute();
dibi::insert('TestEntity_address', array('street' => 'Horní', 'city' => 'Domažlice'))->execute();

/**
 * @Table(name="TestEntity_address")
 *
 * @Column(id, pk, type="integer", generatedValue)
 * @Column(street, type="string")
 * @Column(city, type="string")
 */
class OneToOneEntity extends ActiveEntity { }

/**
 * @Table(name="TestEntity_profile")
 *
 * @Column(id, pk, type="integer", generatedValue)
 * @Column(name, type="string")
 * @Column(surname, type="string")
 * @Column(address, type="OneToOne", entity="vBuilder\Orm\EntityTest\OneToOneEntity", joinOn="address=id")
 */
class TestEntity extends ActiveEntity { }

class TestException extends \LogicException { }