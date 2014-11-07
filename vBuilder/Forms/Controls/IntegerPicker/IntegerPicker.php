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
 *
 * vStore is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vStore bundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Application\UI\Form;

use Nette,
	Nette\Utils\Html;

use vBuilder\Application\WebFilesGenerator;

/**
 * Shop products listing
 *
 * @author Jirka Vebr
 * @since Aug 16, 2011
 */
class IntegerPicker extends Nette\Forms\Controls\TextInput {

	const POSITIVE = ':positive';

	/**
	 * Appends this control to a form container.
	 *
	 * @return IntegerPicker
	 */
	public static function addToContainer(Nette\Forms\Container $fContainer, $name, $label = NULL) {
		return $fContainer[$name] = new static($label);
	}

	/**
	 * Adds static files when connected to form (and presenter).
	 *
	 * @param  IComponent
	 * @return void
	 */
	protected function attached($parent) {
		parent::attached($parent);

		if($parent instanceof Nette\Application\UI\Presenter) {
			$context = $parent->getContext();
			$context->webFilesGenerator->addFile(__DIR__ . '/Css/integer.picker.css', WebFilesGenerator::STYLESHEET);
			$context->webFilesGenerator->addFile(__DIR__ . '/Js/integer.picker.js', WebFilesGenerator::JAVASCRIPT);
		}
	}

	/**
	 * Sets up monitoring for Presenter
	 *
	 * @return void
	 */
	protected function validateParent(Nette\ComponentModel\IContainer $parent) {
		parent::validateParent($parent);
		$this->monitor('Nette\Application\UI\Presenter');
	}

	/**
	 * Creates control HTML element
	 *
	 * @return void
	 */
	public function getControl() {

		$el = Html::el('div')->class('input-group');
		$controlEl = parent::getControl();

		// @see https://developer.apple.com/library/ios/documentation/StringsTextFonts/Conceptual/TextAndWebiPhoneOS/KeyboardManagement/KeyboardManagement.html
		$controlEl->pattern("[0-9]*");

		$el->add($addonEl = Html::el('span')->class('input-group-btn'));
		$addonEl->add($lessEl = Html::el('button')->class('btn btn-default integerPickerLess')->type('button'));
		$lessEl->{'data-integerpicker-id'} = $controlEl->id;
		$lessEl->add(Html::el('span')->class('glyphicon glyphicon-chevron-down'));

		$el->add($controlEl);

		$el->add($addonEl = Html::el('span')->class('input-group-btn'));
		$addonEl->add($moreEl = Html::el('button')->class('btn btn-default integerPickerMore')->type('button'));
		$moreEl->{'data-integerpicker-id'} = $controlEl->id;
		$moreEl->add(Html::el('span')->class('glyphicon glyphicon-chevron-up'));

		$controlEl->class[] = 'form-control integerPicker';

		return $el;
	}

	public static function validatePositive(IntegerPicker $control) {
		$val = $control->getValue();
		return ctype_digit($val) && $val > 0;
	}
}
