<?php

Nette\Environment::getConfigurator()->defaultServices['vBuilder\Config\IConfig']
		  = array('vBuilder\Config\DbUserConfig', 'createUserConfig');
