<?php

// The Nette Tester command-line runner can be
// invoked through command: ../vendor/bin/tester .

include __DIR__ . '/autoload.php';

// configure environment
Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');


// create temporary directory
define('TEMP_DIR', __DIR__ . '/../temp/' . getmypid());
@mkdir(dirname(TEMP_DIR)); // @ - directory may already exist
Tester\Helpers::purge(TEMP_DIR);


function before(\Closure $function = NULL)
{
	static $val;
	if (!func_num_args()) {
		return ($val ? $val() : NULL);
	}
	$val = $function;
}


function test(\Closure $function)
{
	before();
	$function();
}
