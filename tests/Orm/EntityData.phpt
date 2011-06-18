<?php
/**
 * Test: Test of ORM entity data holder
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
	 vBuilder\Orm\EntityData,
	 vBuilder\Orm\IEntityMetadata,
	 vBuilder\Test\Assert; 

// Testovacii vyjimka
class TestException extends \LogicException { }

// Metadata pro testovaci entitu
class TestEntityMetadata implements IEntityMetadata {
	
	public function getTableName() {
		return "some_table";
	}
	
	public function getBehaviors() { return array(); }
	
	public function getBehaviorArgs($behaviorName) { return array(); }
	
	public function getIdFields() {
		return array("id");
	}
	
	public function getFields() {
		return array("id", "name", "number");
	}

	public function hasField($name) {
		return in_array($name, $this->getFields());
	}
	
	public function getFieldColumn($name) {
		return $name;
	}
	
	public function getFieldType($name) {
		if($name == "id") return "integer";
		elseif($name == "name") return "string";
		elseif($name == "number") return "integer";
		else throw new \InvalidArgumentException("Field '$name' is not defined");
	}

	public function isFieldGenerated($name) {
		if(!$this->hasField($name)) throw new \InvalidArgumentException("Field '$name' is not defined");
		
		return ($name == "id");
	}
	
	public function getFieldJoinPairs($name) { return null; }
	public function getFieldTableName($name) { return null; }
	public function getFieldEntityName($name) { return null; }
	public function getFieldMappedBy($name) { return null; }
	
}

// Testovaci entita
class TestEntityData extends EntityData {
	
	function __construct(array $data = array()) {
		$metadata = new TestEntityMetadata();
		
		parent::__construct($metadata, $data);
	}
	
}

// =============================================================================
$ed = new TestEntityData;

// Test na nenacteny field
Assert::null($ed->id);

// Test setteru/getteru ----------------------------------
$ed->id = 123;
Assert::same($ed->id, 123);


// Test na neexistuji field (getter) ---------------------
try {
	$ed->foobar;
	
	Assert::fail('Expected exception');
} catch(\Exception $exception) {
	Assert::exception('Nette\\MemberAccessException', null, null, $exception);
} 

// Test na neexistuji field (setter) ---------------------
try {
	$ed->foobar = uniqid();
	
	Assert::fail('Expected exception');
} catch(\Exception $exception) {
	Assert::exception('Nette\\MemberAccessException', null, null, $exception);
}

// Kontrola onFieldChanged eventu ------------------------
$changedFields = array();
$ed->onFieldChanged[] = function($fieldName) {
	global $changedFields;
	$changedFields[] = $fieldName;
};

$ed->name = 'Lorem ipsum';
Assert::equal($changedFields, array('name'));

// Test revertu ------------------------------------------
$changedFields = array();
$ed->performRollback();
Assert::equal($changedFields, array('id', 'name'));
Assert::null($ed->id);
Assert::null($ed->name);

$ed->onNeedToFetch[] = function() {
	throw new TestException('Loaded called');
};

// Test onNeedToFetch eventu -----------------------------
try {
	$ed->id;
	Assert::fail('Expected exception');
} catch(\Exception $exception) {
	Assert::exception(__NAMESPACE__ . '\\TestException', null, null, $exception);
}

// Test lazy-loadingu ------------------------------------
$ed->id = 345;
try {
	$ed->id; // Nacteni vyvola diky povesenemu handleru vyjimku
} catch(TestException $exception) {
	Assert::fail("Lazy loading test failed");
}

// Test lazy-changed eventu ------------------------------
// Ulozeni stejne hodnoty by nemelo vyvolat onFieldChanged
$changedFields = array();
$ed->id = 345;
Assert::equal($changedFields, array());

// Test data sharingu ruznych instanci entity ------------
$e = new TestEntityData(array('id' => 1, 'name' => 'Lorem ipsum', 'number' => 13));
$e2 = new TestEntityData(array('id' => 1));

Assert::same($e2->name, $e->name);

// Test zmena sdilenych dat
$e->name = 'Neco jineho';
Assert::same($e->name, 'Neco jineho');
Assert::same($e2->name, 'Lorem ipsum');

// Test ulozeni dat => update sdilenych
$e->performSaveMerge();
Assert::same($e->name, 'Neco jineho');
Assert::same($e2->name, 'Neco jineho');

// Test zmeny ID a relokace dat --------------------------
$e->id = 2;
$e->name = 'Novy zaznam';
$e->performSaveMerge();
Assert::same($e2->id, 1);
Assert::same($e2->name, 'Neco jineho');
Assert::same($e->id, 2);
Assert::same($e->name, 'Novy zaznam');
Assert::same($e->number, 13);

// Test zaznamu bez repozitare ---------------------------
$e3 = new TestEntityData(array('name' => 'A', 'number' => 13));
Assert::same($e3->number, 13);

// Umisteni zaznamu do repozitare (simulace auto-increment ID)
$e3->mergeData(array('id' => 3));
$e3->performSaveMerge();
$e4 = new TestEntityData(array('id' => 3));
Assert::same($e4->number, $e3->number);

// Test udrzovani dat
$e3->number = 14;
$e3->performSaveMerge();
Assert::same($e4->number, $e3->number);
$e4->number = 15;
$e4->performSaveMerge();
Assert::same($e3->number, $e4->number);

// Test odpojeni dat
$e3->id = null;
$e3->number = 16;
$e3->performSaveMerge();
Assert::same($e3->number, 16);
Assert::same($e4->number, 15);