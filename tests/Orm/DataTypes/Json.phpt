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
		vBuilder\Orm\DataTypes\Json,
		vBuilder\Test\Assert;

require __DIR__ . '/../../bootstrap.php';

/**
 * @Table(name="some_table")
 *
 * @Column(foo)
 * @Column(bar, type="Json")
 */
class TestEntity extends vBuilder\Orm\Entity {
	
}

$e1 = new TestEntity($context);
$e1->foo = 1;
Assert::null($e1->bar);

$data = array('a' => 1, 'b' => 2);
$e1->bar = $data;
Assert::true($e1->bar instanceof Json);
Assert::same($data, $e1->bar->toArray());
Assert::same(json_encode($data, true), $e1->data->bar);