<?php
/**
 * Test: Test of save of OneToMany relation (entity based)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jul 4, 2011
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

use vBuilder, Nette,
	 vBuilder\Test\Assert; 

// =============================================================================

$e1 = new TestEntity($context);
$e1->name = 'C';

Assert::true($e1->getRoles() instanceOf vBuilder\Orm\EntityCollection);

try {
	$e1->getRoles()->add(null);
	Assert::fail('Exception expected');
} catch(\Exception $e) {
	Assert::exception('InvalidArgumentException', null, $e);
}

$re1 = new OneToManyEntity($context);
$re1->name = 'Red';
$e1->getRoles()->add($re1);

$re2 = new OneToManyEntity($context);
$re2->name = 'Green';
$e1->getRoles()->add($re2);

$re3 = new OneToManyEntity($context);
$re3->name = 'Blue';
$e1->getRoles()->add($re3);

$e1->save();

$found = false;
$entities = $db->query("SELECT * FROM [".TestEntity::getMetadata()->getTableName()."]")->fetchAll();
foreach($entities as $curr) {
	if($curr['name'] == $e1->name) {
		$found = $curr['id'];
		break;
	}
}

if($found === false) Assert::fail('Main entity save failed');
$relatedEntities = $db->query("SELECT * FROM [".OneToManyEntity::getMetadata()->getTableName()."] WHERE [id] = %i", $found)->fetchAll();
Assert::arrayEqual(array(
	 array('id' => 3, 'name' => 'Red'),
	 array('id' => 3, 'name' => 'Green'),
	 array('id' => 3, 'name' => 'Blue')
), $relatedEntities);