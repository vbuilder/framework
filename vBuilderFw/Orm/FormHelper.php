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

use vBuilder, Nette, dibi, Nette\Forms\Controls;

class FormHelper extends vBuilder\Object {
	
	/**
	 * Fills the form fields with entity data
	 * @param Nette\Application\UI\Form $form
	 * @param vBuilder\Orm\Entity $entity 
	 * @param bool true for setDefaults, false for setValues
	 */
	public static function loadFromEntity(Nette\Application\UI\Form $form, vBuilder\Orm\Entity $entity, $defaults = true) {
		$data = $entity->getData();
		$method = $defaults ? 'setDefaultValue' : 'setValue';
		foreach ($form->getControls() as $control) {
			if (static::isControlSupported($control)) {
				$name = $control->getName();
				if (isset($entity->{$name})) {
					$form[$name]->{$method}($entity->{$name});
				}
			}
		}
	}
	
	/**
	 * Fills the entity with form data
	 * @param Nette\Application\UI\Form $form
	 * @param vBuilder\Orm\Entity $entity 
	 */
	public static function fillInEntity(Nette\Application\UI\Form $form, vBuilder\Orm\Entity $entity) {
		$values = $form->values;
		foreach ($values as $field => $val) {
			$entity->{$field} = $val;
		}
	}
	
	/**
	 * We are not interested in submit buttons, file inputs, ...
	 * @param Nette\Forms\IControl $control
	 * @return bool
	 */
	protected static function isControlSupported(Nette\Forms\IControl $control) {
		return ($control instanceof Controls\TextBase  || 
				$control instanceof Controls\RadioList ||
				$control instanceof Controls\SelectBox ||
				$control instanceof Controls\Checkbox);
	}
}