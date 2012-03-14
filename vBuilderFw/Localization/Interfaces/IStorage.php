<?php

/**
 * This file is part of vBuilder Framework (vBuilder FW).
 * 
 * Copyright (c) 2012 V3Net.cz, s.r.o <info@v3net.cz>
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

namespace vBuilder\Localization;

use Nette;

/**
 * Localization storage interface
 *
 * Based on Nella project translator edited for better integration
 * @author	Patrik Votoček
 *
 * @see http://nella-project.org
 * @see https://raw.github.com/nella/framework/master/Nella/Localization/IStorage.php
 */
interface IStorage
{
	/**
	 * @param Dictionary
	 * @param string
	 */
	public function save(Dictionary $dictionary, $lang);
	
	/**
	 * @param string
	 * @return Dictionary
	 */
	public function load($lang, Dictionary $dictionary);
}