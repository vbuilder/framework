<?php
/**
 * Test: Test of ORM repository
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

use vBuilder, Nette,
	 vBuilder\Orm\ActiveEntity,
	 vBuilder\Orm\SessionRepository,
	 vBuilder\Test\Assert;

/**
 * @Column(id, id, type="integer", generatedValue)
 * @Column(name)
 * @Column(related, type="OneToOne", entity="vBuilder\Orm\EntityTest\RelatedEntity", joinOn="related=id")
 * @Column(related2, type="OneToMany", entity="vBuilder\Orm\EntityTest\RelatedEntity2", joinOn="id=sharedId")
 */
class TestEntity extends ActiveEntity {
	
}

/**
 * @Column(id, id, type="integer", generatedValue)
 * @Column(foo)
 */
class RelatedEntity extends ActiveEntity {
	
}

/**
 * @Column(sharedId, id)
 * @Column(value)
 */
class RelatedEntity2 extends ActiveEntity {
	
}

$repo = $context->sessionRepository;
$repo->clear();

// ----------

$e1 = $repo->create(__NAMESPACE__ . '\\TestEntity');
$e1->name = 'foo';
$e1->related = $repo->create(__NAMESPACE__ . '\\RelatedEntity');
$e1->related->foo = 123;

Assert::true($e1->related2 instanceof vBuilder\Orm\EntityCollection);

$e1a = $repo->create(__NAMESPACE__ . '\\RelatedEntity2');
$e1a->value = 'A';
$e1->related2->add($e1a);

$e1b = $repo->create(__NAMESPACE__ . '\\RelatedEntity2');
$e1b->value = 'B';
$e1->related2->add($e1b);

$e1->save();

Assert::arrayEqual(array(
		SessionRepository::NO_ID => array('id' => null, 'name' => 'foo', 'related' => SessionRepository::NO_ID)
), $repo->session[__NAMESPACE__ . '\\TestEntity']);

Assert::arrayEqual(array(
		SessionRepository::NO_ID => array('id' => null, 'foo' => 123)
), $repo->session[__NAMESPACE__ . '\\RelatedEntity']);

Assert::arrayEqual(array(
		SessionRepository::NO_ID => array('sharedId' => null, 'value' => 'A'),
		SessionRepository::NO_ID => array('sharedId' => null, 'value' => 'B')
), $repo->session[__NAMESPACE__ . '\\RelatedEntity2']);

// ----------

$e2 = $repo->get(__NAMESPACE__ . '\\TestEntity');

Assert::same($e1->name, $e2->name);
Assert::same($e1->id, $e2->id);

Assert::true($e2->related instanceof RelatedEntity);
Assert::same($e1->related->foo, $e2->related->foo);

// ----------
