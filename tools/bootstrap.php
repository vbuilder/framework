<?php
/**
 * Fake bootstrap script
 */

$realBootstrapPath = __DIR__ . '/../../../tools/bootstrap.php';

if(file_exists($realBootstrapPath)) {
	include $realBootstrapPath;
	
} else {
	echo "\n\033[1;31m!!! ERROR !!!\033[0m No tools support. Missing bootstrap script.\nHalting.\n\n";
	exit(1);
}

