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

namespace vBuilder\Latte\Macros;

use vBuilder,
	Nette,
	Nette\Latte\MacroNode;

/**
 * System macros
 *
 * @author Adam Staněk (velbloud)
 * @since May 20, 2013
 */
class RegionMacros extends Nette\Latte\Macros\MacroSet {

	/**
	 * Installs redactions macros to parser
	 * 
	 * @param Nette\Latte\Parser $parser
	 * @return RedactionMacros
	 */
	static function install(Nette\Latte\Compiler $compiler) {
		$me = new static($compiler);

		// For n:region-width attribute
		$me->addMacro('region-width', array($me, 'macroRegion'), array($me, 'macroEndRegion'));
		
		return $me;
	}

	function finalize() {
		$prolog = array();
		$prolog[] = '// Region initialization';
		$prolog[] = 'if(!isset($_region)) $_region = $context->regionProvider->activeRegion;';

		// ($prolog, $epilog)
		return array(implode($prolog, "\n"), '');
	}

	function macroRegion(MacroNode $node, $writer) {
		if(!preg_match('/^region\\-(.+)$/', $node->name, $matches))
			throw new Nette\InvalidArgumentException("Invalid RegionMacro: " . $node->name);

		return $writer->write('$_region = $context->regionProvider->switchToNewRegion(array(\'' . $matches[1]. '\' => ' . $node->args . '));');
	}

	function macroEndRegion(MacroNode $node, $writer) {
		return $writer->write('$_region = $context->regionProvider->switchToParent();');
	}

}
