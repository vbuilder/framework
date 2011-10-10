<?php

/**
 * This file is part of vStore
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vstore.cz
 * 
 * vStore is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vStore is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vStore bundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Application\UI\Form;

use vStore, Nette,
	vBuilder,
	Nette\Application\UI\Form;

/**
 * Shop products listing
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class IntegerPicker extends Nette\Forms\Controls\TextInput {
	
	protected static $registered = false;
	
	const POSITIVE = ':positive';
	
	public static function addIntegerPicker(Form $form, $name, $label = null, $cols = null, $maxLength = null) {
		$control = new static($label, $cols, $maxLength);
		return $form[$name] = $control->addRule(Form::INTEGER);
	}
	
	public static function register() {
		if (!self::$registered) {
			Form::extensionMethod('addIntegerPicker', __NAMESPACE__.'\IntegerPicker::addIntegerPicker');	
			self::$registered = true;
		}
	}
	
	public function getControl() {
		$container = Nette\Utils\Html::el('span');
		$control = parent::getControl();
		$control->class[] = 'integerPicker';
		$template = $this->getForm()->getPresenter()->createTemplate();
		$template->setFile(__DIR__.'/Templates/control.latte');
		$template->input = $control;
		$container->setHtml($template);
		return $container;
	}
	
	public static function validatePositive(IntegerPicker $control) {
		$val = $control->getValue();
		return ctype_digit($val) && $val > 0;
	}
}
