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
use Nette\Diagnostics\Debugger as Debug,
	 Nette\Utils\Html,
	 Nette\Environment;

/**
 * Prints out debug message to standard output
 * 
 * @param string message
 * @param mixed|null optional variable to dump along with message
 */
function debug($msg, $var = null) {
	if(Debug::$productionMode == Debug::PRODUCTION)
		return;
	
	if(Environment::isConsole()) {
		echo $msg . "\n";
		
		for($i = 1; $i < func_num_args(); $i++) Debug::dump(func_get_arg($i));
	} else {
		if(!headers_sent()) header('Content-type: text/html; charset=utf-8');
		
		$msgEl = Html::el('div', array('class' => 'vBuilderDebugMsg'))->setText($msg);
		
		echo $msgEl->startTag();
		echo $msgEl[0];
		
		for($i = 1; $i < func_num_args(); $i++) Debug::dump(func_get_arg($i));
		
		echo $msgEl->endTag();
	}
}
