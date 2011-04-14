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

namespace vBuilder\Orm;

use vBuilder, Nette;

/**
 * Basic Entity class for providing data access to DB row by default getters/setters.
 * 
 * This layer is supposed to:
 * - Holding data for it's child implementations and providing EntityData object
 * - Loading entity metadata
 * - Providing default set* and get* functions for all entity fields 
 * - Providing getters and setters to all set* and get* functions
 * - Cache field data by fieldCache for default implementations
 * - Convert data types
 * 
 * In default implementation enity structure is defined by annotations. So
 *  you simply provide @Table and @Column in docblock before your class.
 *  This behavior can be overloaded to for example loading structure from
 *  config or some script. See function Entity::loadMetadata and interface IMetadata. 
 *
 * Syntax for annotations is:
 * <code>
 *  /**
 *    * My entity
 *    * 
 *    * @Table(name="table_name")
 *    *
 *    * @Column(id, pk, type="integer")
 *    * @Column(lang, pk)
 *    * @Column(text)
 *    * /
 *  class MyEntity extends vBuilder\Orm\Entity { }
 * </code>
 * 
 * Annotations **are** case sensitive.
 * 
 * The entity can be created with array of all data or it can be created only with
 *  ID keys for lazy loading. If no argument is given the empty entity is created.
 * <code>
 *   class MyEntity extends vBuilder\Orm\Entity { }
 *  
 *   $e = new MyEntity; // Empty entity
 *   $e = new MyEntity(array('id' => 1, 'text' => 'Lorem ipsum')); // With data
 *   $e = new MyEntity(1); // With id = 1 (if id is name of id column)
 * 
 *   // With id = 1, lang = 'cs' if id and lang are ID columns and are defined in that order
 *   $e = new MyEntity(1, 'cs'); 
 * </code>
 * 
 * With lazyness comes hand to hand with data sharing and caching. This is provided
 *  by EntityData layer. Here is the code for better understanding:
 * <code>
 *  $e = new MyEntity(1); // No data loaded (Lazyness)
 *  echo $e->id; // No data loaded, echo 1
 *  
 *  $e->text = 'A';
 *  echo $e->text; // No data loaded, echo A
 * 
 * 
 *  echo $e->name; // Data load performed, echo some name
 * 
 *  $e2 = new MyEntity(1); // No data loaded (Lazyness)
 *  echo $e->id; // No data loaded, echo 1
 *  echo $e->name; // No data loaded (cached from previous entity)
 * 
 *  // ----
 *  $e3 = new MyEntity(2);
 *  $e4 = new MyEntity(2);
 * 
 *  echo $e4->name; // Perform load, echo Lorem ipsum
 *  echo $e3->name; // echo Lorem ipsum
 *  $e4->name = 'Something else';
 *  $e4->text = 'A';
 * 
 *  echo $e4->name; // echo Something else
 *  echo $e3->name; // echo Lorem ipsum
 * 
 *  $e3->text = 'B';
 * 
 *  // And now the interesting part :-)
 *  $e4->save();
 *  echo $e3->name; // echo Something else, because data of Entity with id 2 changed
 *  echo $e3->text; // echo B, because data are overloaded
 * </code>
 * 
 * When subclassing be sure to **NOT write a constructor** in your class
 *  or don't forget to call PARENT one in your implementation with all arguments.
 *  Here is an example:
 *
 * <code>
 * class MyEntity extends vBuilder\Orm\Entity
 *
 *  public function __construct(array $data) {
 *   call_user_func_array(array('parent', '__construct'), func_get_args()); 
 * 
 *   // YOUR CODE GOES HERE
 *  }
 *
 * }
 * </code>
 * 
 * The class providing default getters and setters for data based on data types.
 *  So look in the function Entity::__dataTypeMapper where there is all type resolving logic.
 *  Long story short there will be performed look up for all classes implementing **IDataType**
 *  interface (for matter of speed they are supposed to be in **vBuilder\Orm\DataTypes** namespace
 *   - other will be omitted) and resolved some primitive types such as string and int.
 *  If data type is not recognized, then the exception will be throwed.
 *
 * **The data type names are case-sensitive!**
 *
 * Defined primitives:
 *	- String
 *	- Integer
 *
 * **Warning: When adding new data type class you have to clear RobotLoader cache!**
 *
 * Another important thing is, that when subclassing your function takes precedence.
 *  So you can always implement your getters. But caching of your own implementation is not
 *  posible by design. For caching of your getters please maintain pattern bellow.
 * 
 * **Please note, that caching for getters which supposed to return some instance is
 *  strongly recommended for value comparation ===. On the other hand caching of primitives
 *  as strings is not necessary at all (if there is no heavy alg. for that).**
 * 
 * In this example is also implementation of setter. The cache clearing is automatic,
 * so you don't have to worry about it and simply assign the value. The data types
 * with write access also have this functionality because of passing variable by reference.
 *
 * <code>
 * class MyClass extends vBuilder\Orm\Entity {
 *
 *  ...
 *
 *  public function getMyField() {
 *   if(($cached = $this->fieldCache("id")) !== null) return $cached;
 *
 *   $value = someHeavyGathering();
 *
 *   return $this->fieldCache("id", $value);
 *  }
 *
 *  public function setMyField($value) {
 *   $this->data->myField = $value;
 *  }
 *
 *  ...
 *
 * }
 * </code>
 * 
 * As you can see in example above. You can access all fields by **$this->data** variable.
 * 
 * You can also extend entity classes with appending defined behavior. Behavior is 
 *  class implementing vBuilder\Orm\IBehavior interface. It modify entity behavior
 *  in situations such as saving or loading data from DB. It is general approach how to 
 *  apply some routine workflow to entities without repeating same code over and over.
 *  Because PHP currently don't support multiple inheritence you should easily workaround
 *  this problem by using entity behaviors. All methods of behavior class can be
 *  automatically called from entity itself.
 * 
 * Please note, that all behaviors have to be in **vBuilder\Orm\Behaviors** namespace.
 * 
 * <code>
 *  class MyBehavior implements vBuilder\Orm\IBehavior {
 *
 *			private $entity;
 * 
 *			public function __construct(Entity &$entity, array $args = array()) {
 *				echo "Behavior applied (".implode($args, ', ').")";
 *				echo $arg['arg1'];
 *		
 *				$this->entity = &$entity;
 *				$entity->onPreSave[] = \callback($this, 'preSave');
 *			}
 * 
 *			public function preSave() {
 *				// Some pre-save changes
 *				$this->entity->id = 123;
 *			}
 *
 *			public function behave() {
 *				// Some custom function 
 *			} 
 *
 *	 }
 *
 *  /**
 *    * My entity
 *    * 
 *    * @Table(name="table_name")
 *    *
 *    * @Behavior(Secure)
 *    * @Behavior(MyBehavior, arg1 = "foo", arg2 = "bar")
 *    *
 *    * @Column(id, pk, type="integer")
 *    * @Column(text)
 *    * /
 *  class MyEntity extends vBuilder\Orm\Entity { }
 * 
 *  $e = new MyEntity;
 *  $e->behave();
 * 
 * </code>
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 17, 2011
 */
class Entity extends vBuilder\Object {
	
	/** @var EntityData raw unmodified data from DB */
	protected $data;
	
	/** @var IEntityMetadata metadata for current instance */
	protected $metadata;
	
	/** @var array of cached data **/
	private $cachedData;
	
	/** @var array of cached metadata of all objects */
	private static $_metadata = array();
	
	/** @var array of cached info about methods of all objects */
	private static $_methods = array();
	
	/** @var array of applied behaviors to this entity */
	private $behaviors = array();
	
	/** @var array for caching IDataType implementation classes */
	private static $_dataTypesImplementations;
		
	/** @var array for caching IBehavior implementation classes */
	private static $_behaviorImplementations;
	
	/**
	 * Constructor of Entity.
	 * 
	 * Takes array of data as parameter or ID value (or more IDs if defined
	 * as separate parameters). 
	 * 
	 * @param array|mixed data row
	 */
	public function __construct($data = array()) {
		// Nactu metadata
		$this->metadata = static::getMetadata();
				
		// Prebirani primary id
		if(!is_array($data)) {
			$data = array();
			
			$numargs = \func_num_args();
			$idFields = $this->metadata->getIdFields();
						
			if(count($idFields) < count($numargs)) {
				$class = \get_called_class();
				$realNum = count($idFields);
				throw new \InvalidArgumentException("Invalid arguments for inicialization of '$class'. $numargs arguments given but only $realNum expected.");
			}
				
			for($i = 0; $i < $numargs; $i++) 
				$data[$idFields[$i]] = \func_get_arg($i);
		}
		
		// Vytvorim data container
		$this->data = new EntityData($this->metadata, $data);		
		$this->data->onFieldChanged[] = callback($this, 'clearCache');
		
		// Chovani
		foreach($this->metadata->getBehaviors() as $behaviorName)
			$this->addBehavior($behaviorName, $this->metadata->getBehaviorArgs($behaviorName));
	}
	
	/** 
	 * Returns metadata object. This function is for public calling.
	 * It has to be final, because it takes care about metadata caching.
	 * 
	 * If you need to overload metadata logic use getMetadataInternal()
	 * 
	 * PHP 5.3 REQUIRED for late static binding!
	 * 
	 * @return IMetaData
	 */
	final public static function & getMetadata() {		
		$className = \get_called_class();
		if(isset(self::$_metadata[$className])) return self::$_metadata[$className];
		
		self::$_metadata[$className] = static::getMetadataInternal();
		
		if(self::$_metadata[$className]->getTableName() == "")
			  throw new \LogicException("Entity '$className' does not have table name defined");
		
		if(count(self::$_metadata[$className]->getFields()) == 0)
			throw new \LogicException("Entity '$className' does not have defined any fields");
		
		return self::$_metadata[$className];
	}
	
	/** 
	 * Returns metadata object. This function is meant to be overloaded for supporting
	 * different metadata structures (loading from config etc.).
	 * 
	 * PHP 5.3 REQUIRED for late static binding!
	 * 
	 * @return IMetaData
	 */
	protected static function & getMetadataInternal() {
		// Reflection teto tridy
		$thisReflection = new Nette\Reflection\ClassReflection(__CLASS__);
		
		// Nalezeni primeho potomka
		$reflection = new Nette\Reflection\ClassReflection(\get_called_class());
		while($reflection !== null && ($parentReflection = $reflection->getParentClass()) != $thisReflection && !Nette\String::startsWith($parentReflection->getName(), 'vBuilder\Orm'))
			$reflection = $parentReflection;
		
		$metadata = new AnnotationMetadata($reflection);
		
		return $metadata;
	}
	
	/**
	 * Default getter implementation. Looks up cache and runs data type mapping.
	 * 
	 * @param string field name
	 * @return mixed 
	 */
	final protected function & defaultGetter($fieldName) {
		// Mrknu, jeslti uz to nemam nacachovany
		if(($cached = $this->fieldCache($fieldName)) !== null) return $cached;

		// @ Indirect modification (Kdyz se predava referenci)
		@$v = $this->fieldCache($fieldName, $this->__dataTypeMapper($fieldName, $this->data->$fieldName));
		return $v;
	}
	
	/**
	 * Helper function for getting cached value or saving it.
	 *
	 * @param string field name
	 * @param mixed value, if null functions only returns existing one
	 *
	 * @return field data or null if field is not cached yet
	 */
	final protected function & fieldCache($fieldName, &$value = null) {
		if($value !== null) {
			$this->cachedData[$fieldName] = $value;
			return $this->cachedData[$fieldName];
		}

		if(isset($this->cachedData[$fieldName])) return $this->cachedData[$fieldName];

		return $value;
	}
	
	/**
	 * Flush cache for field
	 * 
	 * Have to be public because of the events
	 * 
	 * @param string field name
	 */
	final public function clearCache($fieldName) {
		unset($this->cachedData[$fieldName]);
	}
	
	/**
	 * Returns true if any of entity fields has been changed
	 * 
	 * @return bool
	 */
	public function hasChanged() {
		return count($this->data->getChangedData()) > 0;
	}
	
	/**
	 * Magic function for getting data by object access.
	 * Looks up if there is getter defined in child class and call it.
	 * If lookup is for entity field it is called right away (for default getter).
	 *
	 * @param string variable name
	 * @return mixed data
	 * @throws \MemberAccessException if field doesn't exists or name is empty
	 */
	public function & __get($name) {
		$class = get_called_class();

		if($name === '') throw new \MemberAccessException("Cannot read a class '$class' property without name.");

		// property getter support
		$getterName = $name;
		$getterName[0] = $getterName[0] & "\xDF"; // case-sensitive checking, capitalize first character
		$getterName = 'get' . $getterName;

		// Pokud se jedna o polozku definovanou redakcnim typem, rovnou zavolam getter
		if($this->metadata->hasField($name)) {
			$d = $this->$getterName();
			return $d;

		// Jinak se podivam jestli k polozce existuje nejakej getter a zavolam ho
		} else {
			if(!isset(self::$_methods[$class])) self::$_methods[$class] = array_flip(get_class_methods($class));
			if(isset(self::$_methods[$class][$getterName])) {
				$d = $this->$getterName();
				return $d;
			}
		}

		throw new \MemberAccessException("Cannot read an undeclared property $class::\$$name.");
	}
	
	/**
	 * Magic function for setting data by object access.
	 * Looks up if there is setter defined in child class and call it.
	 * If lookup is for entity field it is called right away (for default setter).
	 *
	 * @param  string  property name
	 * @param  mixed   property value
	 * @return void
	 *
	 * @throws \MemberAccessException if field doesn't exists or name is empty
	 */
	final public function __set($name, $value) {
		$class = get_class($this);
		if($name === '') throw new \MemberAccessException("Cannot read a class '$class' property without name.");
		
		// property setter support
		$setterName = $name;
		$setterName[0] = $setterName[0] & "\xDF"; // case-sensitive checking, capitalize first character
		$setterName = 'set' . $setterName;

		// Pokud se jedna o polozku definovanou redakcnim typem, rovnou zavolam setter
		if($this->metadata->hasField($name)) {
			$this->$setterName($value);
			return ;

		// Jinak se podivam jestli k polozce existuje nejakej setter a zavolam ho
		} else {
			if(!isset(self::$_methods[$class])) self::$_methods[$class] = array_flip(get_class_methods($class));
			if(isset(self::$_methods[$class][$setterName])) {
				$this->$setterName($value);
				return ;
			}
		}

		throw new \MemberAccessException("Cannot set non-existing property $class::\$$name.");
	}
	
	/**
	 * Magic function for calling default getters if there is no
	 * getter defined in child class.
	 *
	 * Also performs checking if field exists and caching layer.
	 *
	 * @param string variable name
	 * @return mixed data
	 *
	 * @throws \MemberAccessException if field doesn't exists or name is empty
	 */
	final public function __call($name, $args) {
		// Volani metod entitniho chovani
		foreach($this->behaviors as $behaviorInstance) {
			if(method_exists($behaviorInstance, $name)) {
				return call_user_func_array(array($behaviorInstance, $name), $args); 
			}
		}
		
		if((Nette\String::startsWith($name, "get") || Nette\String::startsWith($name, "set")) && mb_strlen($name) > 3) {
			// Musim data bacha na MB kodovani
			$fieldName = \mb_substr($name, 3);
			$fieldName = \mb_strtolower(\mb_substr($fieldName, 0, 1), 'UTF-8') . \mb_substr($fieldName, 1);

			// Pokud jde o definovane pole
			if($this->metadata->hasField($fieldName)) {

				// IMPLICITNI GET
				if(Nette\String::startsWith($name, "get")) {
					return $this->defaultGetter($fieldName);
				}

				// IMPLICITNI SET
				elseif(count($args)) {
					$this->data->$fieldName = $args[0];

					return ;
				}

			}
		}

		parent::__call($name, $args);
	}
	
	/**
	 * Is field defined?
	 * @param  string  property name
	 * @return bool
	 */
	final public function __isset($name) {
		if($this->metadata->hasField($name)) return true;

		return parent::__isset($name);
	} 

	/**
	 * Unsetting of object properties is forbidden
	 *
	 * @param  string  property name
	 * @return void
	 *
	 * @throws \MemberAccessException
	 */
	final public function __unset($name) {
		$class = get_class($this);
		throw new \MemberAccessException("Cannot unset property {$class}::\$$name because it is not supported by class");
	}
	
	/**
	 * To string magic function for debug purposes
	 *
	 * @return string
	 */
	public function __toString() {
		return "";
	}
	
	/**
	 * The data type mapper. Resolve entity field data types and primitves.
	 *
	 * This function is only called from magic __call where it is cached.
	 * Don't call this function directly, it's only for internal use of those functions!
	 *
	 * @internal
	 *
	 * @param string field name
	 * @param mixed field data
	 * @return mixed resolved data
	 */
	final private function & __dataTypeMapper($name, &$data) {

		// Nactu vsechny implementace datovych typu, pokud je potreba
		if(self::$_dataTypesImplementations === null)
			self::searchForOrmClasses();

		$type = $this->metadata->getFieldType($name);
		if(isset(self::$_dataTypesImplementations[$type])) {
			$class = new self::$_dataTypesImplementations[$type]($data, $name, $this);
			return $class;
			
		// Zachovavani NULL hodnoty
		} elseif($data === null) {
			return $data;
			
		// Integer
		} elseif(Nette\String::compare($type, "Integer")) {
			$data = (int) $data;
			return $data;
			
		// String
		} elseif(Nette\String::compare($type, "String")) {
			$data = (String) $data;
			return $data;
		
		// OneToMany
		} elseif(Nette\String::compare($type, "OneToMany") || Nette\String::compare($type, "OneToOne")) {
			return $data;
		}

		throw new EntityException("Data type '$type' is not defined", EntityException::DATATYPE_NOT_DEFINED);

	}
	/**
	 * Register behavior to this entity 
	 * 
	 * @param string $behaviorName 
	 * @param array of arguments to pass to behavior
	 */
	final protected function addBehavior($behaviorName, array $args = array()) {
		
		// Nactu implementace chovani entit
		if(self::$_behaviorImplementations === null)
			self::searchForOrmClasses();  
		
		if(!isset(self::$_behaviorImplementations[$behaviorName]))
			throw new EntityException("Entity behavior '$behaviorName' was not defined", EntityException::ENTITY_BEHAVIOR_NOT_DEFINED);
		
		$this->behaviors[] = new self::$_behaviorImplementations[$behaviorName]($this, $args);
	}
	
	/**
	 * Searches for all data type and behavior implementations
	 */
	private static function searchForOrmClasses() {
		self::$_dataTypesImplementations = array();
		self::$_behaviorImplementations = array();

		$loaders = Nette\Loaders\AutoLoader::getLoaders();
		$classes = array();
		if(count($loaders) > 0) {
			foreach($loaders as $loader) {
				if($loader instanceof Nette\Loaders\RobotLoader) {
					$classes = \array_keys($loader->getIndexedClasses());
					break;
				}
			}
		}

		if(count($classes) == 0)
			$classes = get_declared_classes();

		foreach($classes as $className) {
			// Protoze je to vyrazne rychlejsi nez overovat interface pro vsechny
			if(Nette\String::startsWith($className, 'vBuilder\\Orm\\DataTypes')) {
				$class = new Nette\Reflection\ClassReflection($className);

				if($class->implementsInterface('vBuilder\\Orm\\IDataType'))
					self::$_dataTypesImplementations = \array_merge((array) self::$_dataTypesImplementations, \array_fill_keys($className::acceptedDataTypes(), $className));
			}
			
			elseif(Nette\String::startsWith($className, 'vBuilder\\Orm\\Behaviors')) {
				$class = new Nette\Reflection\ClassReflection($className);
				
				if($class->implementsInterface('vBuilder\\Orm\\IBehavior'))
					self::$_behaviorImplementations[mb_substr($className, 23)] = $className;
			}
		}
	}
	
}
