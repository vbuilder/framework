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

namespace vBuilder\DI\Extensions;

use Nette;

/**
 * vBuilder configuration extension
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 30, 2012
 */
class vBuilderExtension extends Nette\DI\CompilerExtension {

	public function setCompiler(Nette\DI\Compiler $compiler, $name) {
		parent::setCompiler($compiler, $name);

		// We have to set this BEFORE loadConfiguration(), because this
		// variable is already used in our config.neon
		$libraryPath = realpath(__DIR__ . '/../../../');
		$this->getContainerBuilder()->parameters['vBuilderFwDir'] = $libraryPath;

		return $this;
	}


	public function loadConfiguration() {
		$container = $this->getContainerBuilder();

		// Default language fallback list
		if(!isset($container->parameters['languages']))
			$container->parameters['languages'] = array('cs');

		// Default language is the first on the fallback list
		if(!isset($container->parameters['lang']))
			$container->parameters['lang'] = reset($container->parameters['languages']);

		if(!isset($container->parameters['translationBar']))
			$container->parameters['translationBar'] = array();

		if(!isset($container->parameters['translationBar']['enabled']))
			$container->parameters['translationBar']['enabled'] = TRUE;
	}

	public function beforeCompile() {
		$container = $this->getContainerBuilder();

		// Translator gets the language from container parameters
		$container->getDefinition('translator')
			->addSetup('$service->lang = $this->?[\'lang\']', array('parameters'))
			->addSetup('if(!$this->?[\'productionMode\']) { $service->setLogger($this->getByType(\'vBuilder\Localization\TranslationLogger\')); }', array('parameters'))
			->addSetup('if($this->parameters[?][?] === TRUE && !$this->parameters[\'productionMode\'] && !$this->parameters[\'consoleMode\']) { Nette\Diagnostics\Debugger::getBar()->addPanel(?); }', array(
				'translationBar', 'enabled',
				new Nette\DI\Statement('vBuilder\Diagnostics\TranslationBar')
			));

		// Register TranslationBar on application startup if requested
		$container->getDefinition('application')
			->addSetup('if($this->parameters[?][?] === TRUE && !$this->parameters[\'productionMode\']) { $service->onStartup[] = \'vBuilder\Diagnostics\TranslationBar::register\'; }', array('translationBar', 'enabled'))
			->addSetup(
				'$container = $this; $service->onRequest[] = function () use ($container) {' .
				' call_user_func_array(?, array_merge(func_get_args(), array($container)) );' .
				' }'
				, array('vBuilder\Application\ConstructionMode::onApplicationRequest')
			);

		// Detect language on HTTP request
		$container->getDefinition('httpRequest')
			->addSetup('if(($lang = $service->detectLanguage(?*)) != NULL) { $this->parameters[\'lang\'] = $lang; if($this->isCreated(\'translator\')) $translator->setLang($lang); }', array(array($container->expand('%languages%'))));

		// Nette automatically adds Nette\Security\Diagnostics\UserPanel to User service setup
		// we want to use our own
		$container->getDefinition('user')->setup = array_filter($container->getDefinition('user')->setup, function ($item) {
			return $item->entity != 'Nette\Diagnostics\Debugger::getBar()->addPanel(?)';
		});

		// Our implementation of Diagnostics\UserPanel
		$container->getDefinition('user')->addSetup('Nette\Diagnostics\Debugger::getBar()->addPanel(new vBuilder\Security\Diagnostics\UserPanel(?, $service))', array(
			$container->expand('%vendorDir%/nette')
		));
	}

}