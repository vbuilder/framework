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

namespace vBuilder\Application\UI;

use Nette;
use Nette\Bridges\ApplicationLatte;

/**
 * Extensible template factory
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 20, 2014
 */
class TemplateFactory extends ApplicationLatte\TemplateFactory {

	private $onSetup = array();

	public function addSetup($callable) {
		if(!is_callable($callable))
			throw new InvalidArgumentException('Expected callable');

		$this->onSetup[] = $callable;
	}

	public function createTemplate(Nette\Application\UI\Control $control) {
		$template = parent::createTemplate($control);

		foreach($this->onSetup as $callable)
			call_user_func($callable, $template);

		return $template;
	}

}