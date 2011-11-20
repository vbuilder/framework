<?php

/**
 * Test of sorting iterator
 *
 * @author Adam Staněk (V3lbloud)
 * @since Nov 16, 2011
 *
 * @package    vBuilder\Utils
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

use vBuilder\Test\Assert,
	vBuilder\Utils\SortingIterator,
	vBuilder\Utils\GatheringSortingIterator;

require __DIR__.'/../bootstrap.php';

// Obycejne razeni s callbackem ----------------------------

$data = array(1, 8, 5, 4, 10);
$it = new ArrayIterator($data);
$it2 = new SortingIterator($it, 'strcmp');

Assert::same(array(1, 10, 4, 5, 8), iterator_to_array($it2));

// Test na razeni podle externiho zdroje -------------------
$it3 = new GatheringSortingIterator($it, function ($data) {
	return array(5, 1, 4, 3, 2);
});

Assert::same(array(8, 10, 4, 5, 1), iterator_to_array($it3));

// Test na razeni vnoreneho pole podle nejakeho prvku ------
$data2 = array(
	array('name' => 'pepicek', 'value' => 140),
	array('name' => 'foo', 'value' => 112),
	array('name' => 'bar', 'value' => 113),
	array('name' => 'brekeke', 'value' => 10)
);

$it4 = new SortingIterator($data2, function ($item1, $item2) {
	return $item1['value'] >= $item2['value'];
});

Assert::same(array(
	array('name' => 'brekeke', 'value' => 10),
	array('name' => 'foo', 'value' => 112),
	array('name' => 'bar', 'value' => 113),
	array('name' => 'pepicek', 'value' => 140)
), iterator_to_array($it4));
