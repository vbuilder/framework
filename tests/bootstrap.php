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

/** THIS FILE IS CALLED AT BEGINING OF EVERY TEST *****************************/

// absolute filesystem path to test dir
if(!defined('TEST_DIR')) define('TEST_DIR', __DIR__);

// absolute filesystem path to the libraries
if(!defined('LIBS_DIR')) define('LIBS_DIR', TEST_DIR . '/../..');

// absolute filesystem path to the app
if(!defined('APP_DIR')) define('APP_DIR', TEST_DIR . '/../../../app');

if(!defined('TEMP_DIR')) define('TEMP_DIR', APP_DIR . '/../temp/test');

require_once LIBS_DIR . '/nette/Nette/loader.php';
require_once LIBS_DIR . '/nette/tests/Test/TestHelpers.php';

require_once __DIR__ . '/../vBuilderFw/bootstrap.php';
require_once __DIR__ . '/TestLib/TestScreen.php';
require_once __DIR__ . '/TestLib/BlueScreen.php';
require_once __DIR__ . '/TestLib/GreenScreen.php';
require_once __DIR__ . '/TestLib/Assert.php';
require_once __DIR__ . '/TestLib/TestException.php';

// Configurator
if(!isset($configurator)) {
	require_once __DIR__ . '/TestConfigurator.php';
	$configurator = new vBuilder\TestConfigurator;
	Nette\Environment::setConfigurator($configurator);
	$context = $configurator->container;
}

// configure environment
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', TRUE);
ini_set('html_errors', FALSE);
ini_set('log_errors', FALSE);

// create temporary directory and clear its content
// opravdu je to nutne?
if(!file_exists(TEMP_DIR) || !file_exists(TEMP_DIR . '/cache')) {
	TestHelpers::purge(TEMP_DIR);
}

// Pouze pro CLI, webove testy maji ladenku
if(!defined('HTTP_TEST') || !HTTP_TEST) {
	// catch unexpected errors/warnings/notices
	class TestErrorException extends Exception {}

	set_error_handler(function($severity, $message, $file, $line) {
		if (($severity & error_reporting()) === $severity) {
			throw new TestErrorException("$message in $file:$line");
		}
		return FALSE;
	});

}

$_SERVER = array_intersect_key($_SERVER, array_flip(array('PHP_SELF', 'SCRIPT_NAME', 'SERVER_ADDR', 'SERVER_SOFTWARE', 'HTTP_HOST', 'DOCUMENT_ROOT', 'OS')));
$_SERVER['REQUEST_TIME'] = 1234567890;
$_ENV = $_GET = $_POST = array();

if (PHP_SAPI !== 'cli') {
	header('Content-Type: text/html; charset=utf-8');
}

if (extension_loaded('xdebug')) {
	xdebug_disable();
	TestHelpers::startCodeCoverage(__DIR__ . '/coverage.dat');
}



// Load configuration from config.neon file
Nette\Environment::loadConfig();