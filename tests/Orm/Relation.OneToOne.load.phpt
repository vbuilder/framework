<?php
/**
 * Test: Test of load in OneToOne relation
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

// =============================================================================

// Test pri listingu
$e1 = $context->repository->findAll(__NAMESPACE__ . '\\TestEntity')->fetch();
Assert::equal('Jan', $e1->name);
Assert::equal(__NAMESPACE__ . '\\OneToOneEntity', get_class($e1->address));
Assert::equal('Dolní', $e1->address->street);

// Test pri primem nacteni
$e2 = $context->repository->get(__NAMESPACE__ . '\\TestEntity', 2);
Assert::equal('Iveta', $e2->name);
Assert::equal(__NAMESPACE__ . '\\OneToOneEntity', get_class($e2->address));
Assert::equal('Horní', $e2->address->street);
