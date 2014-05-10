<?php
// This file is fake boostrap for our scripts.
// It searches for real bootstrap in /app directory

// Note: we don't want to use __DIR__ because composer automatically creates
// symlinks for scripts (__DIR__ returns actual path).
$searchPath = array(
	dirname(getcwd() . DIRECTORY_SEPARATOR . $argv[0])
		. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app'
		. DIRECTORY_SEPARATOR . 'bootstrap.php'
);

foreach($searchPath as $path) {
	if(file_exists($path))
		return require $path;
}

echo "\nBootstrap not found\n\n";
exit(1);
