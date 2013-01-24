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
use Nette\Diagnostics\Debugger as Debug,
	Nette\Utils\Html,
	Nette\Environment,
	vBuilder\Diagnostics\Helpers;

/**
 * Prints out debug message to standard output
 * 
 * @param string message
 * @param mixed|null optional variable to dump along with message
 */
function debug($msg, $var = null) {
	if(Debug::$productionMode == Debug::PRODUCTION)
		return;
	
	if(!isset($_SERVER['HTTP_USER_AGENT'])) {
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

function bd() {
	foreach (func_get_args() as $m) {
		Nette\Diagnostics\Debugger::barDump($m);
	}
}

function dt(array $var) {
	$firstRow = (array) reset($var);
	
	echo '<table style="border-collapse: collapse; font-size: 9pt; background: white; margin: 10px 0;"><thead><tr>';
	echo '<td style="border: 1px solid #dddddd;">&nbsp;</td>';
	foreach(array_keys($firstRow) as $col) {
		echo "<td style=\"border: 1px solid #dddddd; padding: 3px 10px; text-align: center;\">$col</td>";
	}
	
	echo '</tr><tbody>';
	
	$index = 0;
	foreach($var as $row) {
		echo '<tr>';
		echo '<td style="border: 1px solid #dddddd; padding: 3px 10px; color: #cc0000;">#'.++$index.'</td>';
		
		foreach($row as $col) {
			echo "<td style=\"border: 1px solid #dddddd; padding: 3px 10px;\">\n";
			d($col);
			echo "</td>\n";
		}
		echo '</tr>';
	}
	
	echo '</tbody></tbody>';
}


function d10() {
	$tmp = Debug::$maxDepth;
	Debug::$maxDepth = 10;

	call_user_func_array('d', func_get_args());

	Debug::$maxDepth = $tmp;
}


function d() {

	if(Debug::$productionMode)
		return ;

	foreach (func_get_args() as $m) {
		if(!Debug::$consoleMode && ($m instanceof \DibiResult || $m instanceof \vBuilder\Orm\Fluent)) {
			if($m instanceof \DibiResult)
				dt($m->fetchAll());
			else {
				$m->test();
				$data = array();
				foreach($m->fetchAll() as $entity) {
					$data[] = $entity->data->getAllData();
				}
				
				dt($data);
			}
		} else {

			$d = '<pre class="nette-dump">' . Helpers::htmlDump($m) . '</pre>';
			if(Debug::$consoleMode) $d = htmlspecialchars_decode(strip_tags($d), ENT_NOQUOTES);
			echo $d;
		}
	}
}

function dd() {
	if(Debug::$productionMode)
		return ;

	call_user_func_array('d', func_get_args());
	die;
}

// -----------------------

/**
 * Translates the given string.
 *
 * @param string|array	message or messages
 * @return int|array	count or variables
 */
function __($message, $count = NULL)
{
	return Nette\Environment::getService('translator')
		->translate($message, $count);
}

/**
 * Translates the given string with plural.
 *
 * @deprecated
 * @param string
 * @param string
 * @param int plural form (positive number)
 * @return string
 */
function _n($single, $plural, $number)
{
	// trigger_error(__FUNCTION__ . '() is deprecated; use __(array(\$single, \$plural), \$number) instead.', E_USER_DEPRECATED);
	return Nette\Environment::getService('translator')
		->translate(array($single, $plural), $number);
}

/**
 * Translates the given string with vsprintf.
 *
 * @deprecated
 * @param string
 * @param array for vsprintf
 * @return string
 */
function _x($message, array $args)
{
	// trigger_error(__FUNCTION__ . '() is deprecated; use __(\$message, $args) instead.', E_USER_DEPRECATED);
	return Nette\Environment::getService('translator')
		->translate($message, $args);
}

/**
 * Translates the given string with plural and vsprintf.
 *
 * @deprecated
 * @param string
 * @param string
 * @param int plural form (positive number)
 * @param array for vsprintf
 * @return string
 */
function _nx($single, $plural, $number, array $args)
{
	// trigger_error(__FUNCTION__ . '() is deprecated; use __(array(\$single, \$plural), array(\$number, $args[0], $args[1], ...) instead.', E_USER_DEPRECATED);
	return Nette\Environment::getService('translator')
		->translate(array($single, $plural), array_merge(array($number), $args));
}
