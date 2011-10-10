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

namespace vBuilder\Latte\Macros;

use vBuilder,
		Nette,
		Nette\Latte\MacroNode,
		Nette\Latte\ParseException;

/**
 * System macros
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 2, 2011
 */
class SystemMacros extends Nette\Latte\Macros\MacroSet {

	/**
	 * Installs redactions macros to parser
	 * 
	 * @param Nette\Latte\Parser $parser
	 * @return RedactionMacros
	 */
	static function install(Nette\Latte\Parser $parser) {
		$me = new static($parser);

		// TODO: Presunout webFilesGenerator do framworku!
		foreach(array('js', 'css') as $lang) {
			$me->addMacro('add' . ucfirst($lang), '$context->webFilesGenerator->addFile(%node.args, \'' . $lang . '\');');
		}
		
		return $me;
	}

}