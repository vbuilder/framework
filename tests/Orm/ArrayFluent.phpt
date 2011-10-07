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

use vBuilder,
		vBuilder\Orm\ArrayFluent,
		vBuilder\Test\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @Table(name="some_table")
 *
 * @Column(foo)
 * @Column(bar)
 */
class TestEntity extends vBuilder\Orm\Entity {
	
}

$data = array(
		new TestEntity(array('foo' => 1, 'bar' => 2), $context),
		new TestEntity(array('foo' => 1, 'bar' => 3), $context),
		new TestEntity(array('foo' => 1, 'bar' => 3), $context),
		new TestEntity(array('foo' => 2, 'bar' => 3), $context),
		new TestEntity(array('foo' => 2, 'bar' => 4), $context)
);

$fluent = new ArrayFluent($data, $context);
$fluent->where('[foo] = %s', 1);

$tmp = array();
foreach($fluent as $curr) {
	$tmp[] = array($curr->foo, $curr->bar);
}

Assert::arrayEqual(array(
		array(1, 2),
		array(1, 3),
		array(1, 3)
), $tmp);

$fluent->where('[bar] = %s', 3);

$tmp = array();
foreach($fluent as $curr) {
	$tmp[] = array($curr->foo, $curr->bar);
}

Assert::arrayEqual(array(
		array(1, 3),
		array(1, 3)
), $tmp);
