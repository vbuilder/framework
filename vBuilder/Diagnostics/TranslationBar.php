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

namespace vBuilder\Diagnostics;

use vBuilder,
	Nette;

/**
 * Debug bar for translations
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 17, 2014
 */
class TranslationBar implements Nette\Diagnostics\IBarPanel {

	private static $registeredRoute;

	private $httpRequest;
	private $translator;
	private $basePath;

	private $authToken;
	private $queriedTranslations;
	private $ok = TRUE;

	/**
	 * Registers translation micro presenter
	 * which handles translation saves
	 *
	 * This method is called from $app->onStartup() event
	 */
	static function register(Nette\Application\Application $app) {

		if(isset(self::$registeredRoute))
			throw new Nette\InvalidStateException(__CLASS__ . "::register called twice?");

		$routeList = $app->getRouter();

		self::$registeredRoute = new Nette\Application\Routers\Route('/<language [a-z]{2}>/vbuilder-translation-bar/<token [a-z0-9]{8}>', function (vBuilder\Localization\Translator $translator, Nette\Http\Session $session) use ($app) {
			list($request) = $app->getRequests();

			$dictionary = NULL;
			foreach($translator->getDictionaries() as $name => $dict) {
				if($name == 'translationBar') {
					$dictionary = $dict;
					break;
				}
			}
			if($dictionary === NULL) throw new Nette\InvalidStateException("Missing translationBar dictionary");
			if(!$dictionary->isFrozen()) $dictionary->init($request->parameters['language']);

			if(!$request->isPost())
				throw new Nette\Application\BadRequestException('Expected POST', 400);

			// Check authorization token (basic CSRF prevention)
			$session = $session->getSection(strtr(__CLASS__, '\\', '.'));
			if(!isset($session->authToken) || $request->parameters['token'] != $session->authToken)
				throw new Nette\Application\BadRequestException('Invalid token', 403);

			// Process input
			$data = Nette\Utils\Json::decode(file_get_contents('php://input'), Nette\Utils\Json::FORCE_ARRAY);
			if(isset($data['translations'])) {
				foreach($data['translations'] as $tr) {
					if(!isset($tr['key']) || !isset($tr['value'])) continue;

					$dictionary->addTranslation($tr['key'], (array) $tr['value']);
				}
			}

			// @note atomicity?
			$dictionary->save();

			$payload = array('ok' => TRUE);
			return new Nette\Application\Responses\JsonResponse($payload);
		});

		// Add our route as first
		$routeList[] = self::$registeredRoute; // Bypass the index check by expanding array
		for($i = count($routeList) - 1; $i > 0; $i--) $routeList[$i] = $routeList[$i - 1];
		$routeList[0] = self::$registeredRoute;
	}

	/**
	 * Constructor.
	 * Takes translator service and prepares URL for save requests
	 */
	function __construct(vBuilder\Localization\Translator $translator, Nette\Http\Request $httpRequest, Nette\Http\Session $session, Nette\DI\Container $container) {
		$this->translator = $translator;
		$this->httpRequest = $httpRequest;
		$this->basePath = $container->parameters['wwwDir'] . '/..';

		if(!isset(self::$registeredRoute))
			throw new Nette\InvalidStateException(__CLASS__ . "::register not called?");

		// Creates unique authorization token (basic CSRF prevention)
		$session = $session->getSection(strtr(__CLASS__, '\\', '.'));
		if(!isset($session->authToken))
			$session->authToken = vBuilder\Utils\Strings::randomHumanToken(8);

		$this->authToken = $session->authToken;
	}

	/**
	 * Prepares data for the panel
	 */
	function gather() {
		if(isset($this->queriedTranslations)) return ;
		if($this->translator->getLang() == 'en') return ;

		$this->queriedTranslations = $this->translator->getLogger()
			? $this->translator->getLogger()->getQueriedTranslations()
			: array();

		// Gather translations
		foreach($this->queriedTranslations as &$translation) {
			// @todo: support for plurals
			if(!isset($translation['translations']) && $translation['isTranslated'])
				$translation['translations'] = array(__($translation['singular']));

			if(!$translation['isTranslated']) $this->ok = FALSE;
		}

		// Start with the untranslated entries
		uasort($this->queriedTranslations, function ($a, $b) {
			if($a['isTranslated'] && !$b['isTranslated']) return 1;
			return $a['singular'] < $a['singular'];
		});
	}

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 */
	function getTab() {
		$this->gather();
		if(count($this->queriedTranslations) == 0) return '';

		ob_start();

		$translationOk = $this->ok;
		require __DIR__ . '/Templates/bar.translation.tab.phtml';

		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 */
	function getPanel() {
		$this->gather();
		if(count($this->queriedTranslations) == 0) return '';

		ob_start();

		$translations = $this->queriedTranslations;
		$lang = $this->translator->getLang();
		$basePath = $this->basePath;

		$applicationRequest = new Nette\Application\Request('Nette:Micro', 'GET', array(
			'language' => $lang,
			'token' => $this->authToken
		));

		$actionUrl = self::$registeredRoute->constructUrl($applicationRequest, $this->httpRequest->getUrl());

		require __DIR__ . '/Templates/bar.translation.panel.phtml';

		return ob_get_clean();
	}

}