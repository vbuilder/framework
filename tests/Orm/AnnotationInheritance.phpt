<?php
/**
 * Test: Test of ORM entity annotations inheritance
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

namespace vBuilder\Test\AnnotationInheritance;

require __DIR__ . '/../bootstrap.php';


use /*vBuilder, Nette,
	 vBuilder\Orm\Entity,*/
	 vBuilder\Test\Assert;

/**
 * @Table(name="x")
 * 
 * @Column(id, pk, type="Integer")
 * @Column(title)
 */
class FirstEntity extends \vBuilder\Orm\Entity {
	
}



/**
 * @Column(name)
 * @Column(dude, type="integer")
 */
class SecondEntity extends FirstEntity {
	
}

Assert::arrayEqual(array(
	'name', 'dude', 'id', 'title'
), SecondEntity::getMetadata()->getFields());