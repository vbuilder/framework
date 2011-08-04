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


use vBuilder\Orm\ActiveEntity;

$db = $context->connection;

$db->query(
	"CREATE TEMPORARY TABLE [TestEntityTable] (".
	"	[id] int(11) NOT NULL AUTO_INCREMENT,".
   "	[name] varchar(255),".
		  
	"	PRIMARY KEY([id])".
	");"
);

$db->query(
	"CREATE TEMPORARY TABLE [TestEntityTableList] (".
	"	[id] int(11) NOT NULL,".
   "	[name] varchar(255),".
		  
	"	KEY (`id`)".
	");"
);

$db->query(
	"CREATE TEMPORARY TABLE [TestEntityTableList2] (".
	"	[id] int(11) NOT NULL,".
   "	[a] varchar(255),".
	"	[b] varchar(255),".
		  
	"	KEY (`id`)".
	");"
);

$db->insert('TestEntityTable', array('id' => 1, 'name' => 'A'))->execute();
$db->insert('TestEntityTable', array('id' => 2, 'name' => 'B'))->execute();
$db->insert('TestEntityTableList', array('id' => 1, 'name' => 'foo'))->execute();
$db->insert('TestEntityTableList', array('id' => 1, 'name' => 'bar'))->execute();
$db->insert('TestEntityTableList2', array('id' => 2, 'a' => 'foo', 'b' => 'bar'))->execute();

/**
 * @Table(name="TestEntityTable")
 *
 * @Column(id, id, type="integer", generatedValue)
 * @Column(name, type="string")
 * @Column(roles, type="OneToMany", table="TestEntityTableList", joinUsing="id")
 * @Column(complex, type="OneToMany", table="TestEntityTableList2", joinUsing="id")
 */
class TestEntity extends ActiveEntity { }