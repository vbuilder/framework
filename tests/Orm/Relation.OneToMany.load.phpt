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

require __DIR__ . '/Relation.OneToMany.inc.php';

use vBuilder, Nette, dibi,
	 vBuilder\Orm\Repository,
	 vBuilder\Test\Assert; 

// =============================================================================

$e1 = new TestEntity(1, $context);

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
$e2 = new TestEntity(2, $context);
Assert::arrayEqual(array(), $e2->getRoles()); 