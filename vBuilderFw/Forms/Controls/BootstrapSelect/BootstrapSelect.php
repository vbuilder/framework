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
 *
 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Forms\Controls;

use vStore, Nette,
	vBuilder,
	vBuilder\Application\WebFilesGenerator,
	Nette\Utils\Html;

/**
 * Select box based on Twitter Bootstrap
 *
 * @author Adam Staněk
 * @since Jan 2, 2014
 */
class BootstrapSelect extends Nette\Forms\Controls\TextInput {

	/** @var array */
	private $items = array();

	/**
	 * Constructor
	 *
	 * @param string
	 * @param array
	 */
	public function __construct($label = NULL, array $items = NULL) {
		parent::__construct($label);

		if ($items !== NULL) {
			$this->setItems($items);
		}
	}

	/**
	 * Appends this control to a form container.
	 *
	 * @return BootstrapSelect
	 */
	public static function addToContainer(Nette\Forms\Container $fContainer, $name, $label = NULL, $items = NULL) {
		return $fContainer[$name] = new static($label, $items);
	}

	/**
	 * Adds static files when connected to form (and presenter).
	 *
	 * @param  IComponent
	 * @return void
	 */
	protected function attached($parent) {
		$presenter = $this->lookup('Nette\Application\UI\Presenter', true);
		$context = $presenter->getContext();
		$context->webFilesGenerator->addFile(__DIR__ . '/Js/bootstrap-select.js', WebFilesGenerator::JAVASCRIPT);
		$context->webFilesGenerator->addFile(__DIR__ . '/Css/bootstrap-select.css', WebFilesGenerator::STYLESHEET);
	}

	/**
	 * Sets items from which to choose.
	 *
	 * @param  array
	 * @return self
	 */
	public function setItems(array $items) {

		$allowed = array();
		foreach ($items as $k => $v) {
			foreach ((is_array($v) ? $v : array($k => $v)) as $key => $value) {
				if (isset($allowed[$key])) {
					throw new Nette\InvalidArgumentException("Items contain duplication for key '$key'.");
				}

				$allowed[$key] = $value;
			}
		}

		$this->items = $allowed;
		return $this;
	}

	/**
	 * Returns items from which to choose.
	 *
	 * @return array
	 */
	final public function getItems() {
		return $this->items;
	}

	/**
	 * Generates control's HTML element.
	 *
	 * @return Nette\Utils\Html
	 */
	public function getControl() {
		$inputEl = parent::getControl();

		$inputGroupId = $inputEl->attrs['id'] . '-input-group';
		$inputGroup = Html::el('div')
			->class('bootstrap-select input-group')
			->id($inputGroupId);

		// Text input field
		$inputGroup->add(
			Html::el('input')
				->type('text')
				->autocomplete('off')
				->name($inputEl->attrs['name'] . '_label')
				->id($inputEl->attrs['id'])
				->class('form-control')
		);

		// Hidden field
		$inputGroup->add(
			Html::el('input')
				->type('hidden')
				->name($inputEl->attrs['name'])
				->value($inputEl->attrs['value'])
		);

		// Button group
		$inputGroup->add($buttonGroup = Html::el('div')->class('input-group-btn'));

		// Dropdown button
		$buttonGroup->add(
			Html::el('button')
				->type('button')
				->class('btn btn-default dropdown-toggle')
				->tabindex('-1')
				->add(Html::el('span')->class('caret'))
		);

		// Dropdown menu
		$buttonGroup->add(
			$menu = Html::el('ul')
				->class('pull-right dropdown-menu primary')
				->tabindex('-1')
		);

		// Dropdown menu items
		foreach($this->items as $key => $value) {
			$menu->add(
				Html::el('li')->add(Html::el('a', $this->translate($value))
					->href($key)
					// ->addAttributes(array('data-value' => $key))
					->tabindex("-1")
				)
			);
		}

		// Wrapper with script
		return Html::el('div')
			->add($inputGroup)
			->add(Html::el('script', "\$('#$inputGroupId').bootstrapSelect();")->type('text/javascript'));
	}

}