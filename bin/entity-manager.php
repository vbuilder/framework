<?php

use vBuilder\Utils\Classinfo as ClassInfo,
	vBuilder\Utils\CliArgsParser,
	vBuilder\Utils\Strings,
	vBuilder\Orm\DdlHelper;

const ENTITY_CLASS = 'vBuilder\\Orm\\Entity';

$container = require __DIR__ . '/bootstrap.php';
$db = $container->getByType('DibiConnection');

$args = new CliArgsParser;
$args->setNumRequiredArgs(1, 1);
$args->setArgumentHelp('entity name');

$entities = $container->classInfo->getAllChildrenOf(ENTITY_CLASS);

// -----------------------------------------------------------------------------

if($args->parse()) {
	list($entityName) = $args->getArguments();

	// PHP >= 5.0.3
	if(!class_exists($entityName) || !is_subclass_of($entityName, ENTITY_CLASS)) {

		$matches = array();
		foreach($entities as $curr) {
			if(Strings::endsWith($curr, '\\' . $entityName)) {
				$matches[] = $curr;
			}
		}

		if(count($matches) == 0) {
			echo "\n\033[1;31m!!! ERROR !!!\033[0m Entity \033[1;33m$entityName\033[0m does not exist.\nHalting.\n\n";
			exit(1);
		} elseif(count($matches) == 1) {
			list($entityName) = $matches;
		} else {
			echo "\n\033[1;31m!!! ERROR !!!\033[0m Given entity name \033[1;33m$entityName\033[0m is ambiguous.\n";
			$entities = $matches;
			$entityName = NULL;
		}
	}

	if(isset($entityName)) {

		echo "\n";
		echo "Entity: \033[1;33m$entityName\033[0m\n";

		echo "\n\033[1;32mCreate syntax:\033[0m\n";
		echo DdlHelper::createQuery($entityName::getMetadata());

		$alter = DdlHelper::alterQuery($entityName::getMetadata(), $db);
		if($alter) {
			echo "\n\n\033[1;32mAlter syntax:\033[0m\n";
			echo $alter;
		}

		echo "\n";
		exit(0);
	}
}

// -----------------------------------------------------------------------------

if($args->getErrorMsg() !== FALSE) {
	echo "\n";
	$args->printUsage();
}

echo "\n";
echo "List of project entities:\n";

foreach($entities as $curr) {
	echo "\t- \033[1;33m" . str_replace('\\', '\\\\', $curr) . "\033[0m\n";
}

echo "\n";