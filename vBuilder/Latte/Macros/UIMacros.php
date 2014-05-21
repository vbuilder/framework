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

use Nette,
	Nette\Latte;


/**
 * Overrided Latte UIMacros for auto template extending
 */
class UIMacros extends Nette\Latte\Macros\UIMacros {

	private static $autoExtend;

	public static function installWithAutoExtend(Latte\Compiler $compiler, $autoExtend = NULL) {
		self::$autoExtend = $autoExtend;
		parent::install($compiler);
	}

	/**
	 * Finishes template parsing.
	 * @return array(prolog, epilog)
	 */
	public function finalize() {
		list($prolog, $epilog) = parent::finalize();

		// TODO: Some escaping
		if(self::$autoExtend != NULL) {
			$escaped = '"' . self::$autoExtend . '"';
			$prolog = preg_replace('/\\$_l->extends\s*=[^\\?]+\\?[^\\:]+\\:[^;]+;/', '$_l->extends = ' . $escaped . ';', $prolog);
			self::$autoExtend = NULL;
		}

		return array($prolog, $epilog);
	}

}