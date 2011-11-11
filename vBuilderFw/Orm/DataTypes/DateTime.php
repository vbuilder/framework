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

namespace vBuilder\Orm\DataTypes;

use vBuilder,
		Nette,
		Nette\Utils\Strings;

/**
 * Date and time, subclassed from Nette\dibi
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jan 18, 2011
 */
class DateTime extends \DateTime implements vBuilder\Orm\IDataType {

	protected $entity;
	protected $fieldName;
	
	public function __construct($fieldName, &$entity, Nette\DI\IContainer $context) {
		$this->entity = $entity;
		$this->fieldName = $fieldName;
		
		$data = $entity->data->{$fieldName};
		if(is_numeric($data)) $data = date('Y-m-d H:i:s', $data);
		
		parent::__construct($data);
	}

	public function convertFrom(&$data) {		
		if($data instanceof \DateTime) {
			$this->entity->data->{$this->fieldName} = $data->format('Y-m-d H:i:s');
			$this->setTimestamp($data->getTimestamp());
			$this->setTimezone($data->getTimezone());
			return ;
		}
		
		elseif(is_int($data) || intval($data) == $data) {
			$this->setTimestamp(intval($data));
			$this->entity->data->{$this->fieldName} = $this->format('Y-m-d H:i:s');
			return ;
			
		} elseif(is_string($data)) {
			if(($matches = Strings::match($data, '#^[0-4]{4}-[0-9]{2}-[0-9]{2}$#')) !== false) {
				$this->setTimestamp(strtotime($data));
				$this->entity->data->{$this->fieldName} = $this->format('Y-m-d H:i:s');
				
			} else {
				throw new Nette\InvalidArgumentException("Unsupported string format " . var_export($data, true) . " for " . get_called_class());
			}
			
			return ;
		}
		
		throw new Nette\InvalidArgumentException("'".  gettype($data)."' is not supported by " . get_called_class());
	}
	
	public static function acceptedDataTypes() {
		return array("DateTime", "Timestamp");
	}

	public function  __toString() {
		return $this->format('d.m.Y H:i:s');
	}

}
