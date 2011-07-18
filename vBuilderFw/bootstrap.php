<?php
require_once __DIR__ . '/Utils/shortcuts.php';

Nette\Environment::getConfigurator()->defaultServices['vBuilder\Config\IConfig']
		  = array('vBuilder\Config\DbUserConfig', 'createUserConfig');
