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

		if(!isset($container->parameters['underConstruction']))
			$container->parameters['underConstruction'] = FALSE;
	}

	public function beforeCompile() {
		$container = $this->getContainerBuilder();
		// Note: we can make parameter conditions here,
		//    because container is regenerated everytime parameters are changed.

		// Translator gets the language from container parameters
		$container->getDefinition('translator')
			->addSetup('$service->lang = $this->?[\'lang\']', array('parameters'));

		// Register TranslationBar on application startup if requested
		if(!$container->parameters['productionMode'] && $container->parameters['translationBar']['enabled']) {
			$container->getDefinition('application')
				->addSetup('$service->onStartup[] = ?', array('vBuilder\Diagnostics\TranslationBar::register'));

			$container->getDefinition('translator')
				->addSetup('$service->setLogger($this->getByType(?))', array('vBuilder\Localization\TranslationLogger'));

			if(!$container->parameters['consoleMode']) {
				$container->getDefinition('translator')
					->addSetup('Nette\Diagnostics\Debugger::getBar()->addPanel(?)', array(new Nette\DI\Statement('vBuilder\Diagnostics\TranslationBar')));
			}
		}

		// Support for construction mode
		if($container->parameters['productionMode']) {
			$container->getDefinition('application')
				->addSetup('$service->onRequest[] = array(?, ?)', array('@app.constructionMode', 'onApplicationRequest'))
				->addSetup('$service->onResponse[] = array(?, ?)', array('@app.constructionMode', 'onApplicationResponse'));
		}

		// Detect language on HTTP request
		$container->getDefinition('httpRequest')
			->addSetup('if(($lang = $service->detectLanguage(?*)) != NULL) { $this->parameters[\'lang\'] = $lang; if($this->isCreated(\'translator\')) $translator->setLang($lang); }', array(array($container->expand('%languages%'))));

		// ---------------------------------------------------------------------
		// Web files generator (Css, Js, ...)
		// ---------------------------------------------------------------------

		$container->addDefinition('webFilesGenerator')
			->setClass('vBuilder\\Application\\WebFilesGenerator')
			->addSetup(
				'Tracy\Debugger::getBar()->addPanel(?)',
				array(new Nette\DI\Statement('vBuilder\Diagnostics\WebFilesBar'))
			);


		// ---------------------------------------------------------------------
		// UserPanel fix
		// ---------------------------------------------------------------------

		// Nette automatically adds Nette\Security\Diagnostics\UserPanel to User service setup
		// we want to use our own
		$container->getDefinition('user')->setup = array_filter($container->getDefinition('user')->setup, function ($item) {
			return $item->entity != 'Tracy\Debugger::getBar()->addPanel(?)';
		});

		// Our implementation of Diagnostics\UserPanel
		$container->getDefinition('user')->addSetup('Tracy\Debugger::getBar()->addPanel(new vBuilder\Security\Diagnostics\UserPanel(?, $service))', array(
			$container->expand('%pkg.nette.security.dir%')
		));

		// ---------------------------------------------------------------------
		// Template factory
		// ---------------------------------------------------------------------

		$templateFactoryDef = $container->getDefinition('nette.templateFactory');

		if($templateFactoryDef->getFactory()) {
			$templateFactoryDef->setFactory(
				'vBuilder\\Application\\UI\TemplateFactory',
				(array) $templateFactoryDef->getFactory()->arguments
			);
		}

		$templateFactoryDef->setClass(
			'vBuilder\\Application\\UI\TemplateFactory',
			$templateFactoryDef->getParameters()
		);

		// ---------------------------------------------------------------------
		// Templating helpers (filters)
		// ---------------------------------------------------------------------

		// Classes with helpers
		$helperClasses = array(
			'vBuilder\\Latte\\Helpers\\FormatHelpers',
			'vBuilder\\Latte\\Helpers\\DateFormatHelpers'
		);

		// Specific helpers
		$helpers = array(
			array('translate', array('@translator', 'translate')),
			array('printf', 'sprintf')
		);

		// Find all helpers in helper classes
		foreach($helperClasses as $className) {
			$refl = new \ReflectionClass($className);
			$methods = $refl->getMethods(\ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC);
			foreach($methods as $method) {
				$helpers[] = array(
					$method->getName(),
					$className . '::' . $method->getName()
				);
			}
		}

		// Register helpers
		foreach($helpers as $params) {
			$container->getDefinition('nette.latteFactory')->addSetup(
				'$service->addFilter(?, ?)',
				$params
			);
		}
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class) {
		$initialize = $class->methods['initialize'];

		$formExtensions = array(
			'addIntegerPicker' => 'vBuilder\Application\UI\Form\IntegerPicker::addToContainer',
			'addCaptcha' => 'vBuilder\Forms\Controls\Captcha::addToContainer',
			'addBootstrapSelect' => 'vBuilder\Forms\Controls\BootstrapSelect::addToContainer'
		);

		foreach($formExtensions as $alias => $target) {
			$initialize->addBody(
				'Nette\Forms\Container::extensionMethod(' .
					var_export($alias, TRUE) . ', ' .
					var_export($target, TRUE) .
				');'
			);
		}
	}

}