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

namespace vBuilder\Application;

use vBuilder,
	vBuilder\Utils\Strings,
	Nette,
	Nette\Application\Request,
	Nette\Application\Application,
	SystemContainer;

/**
 * Checks if application isn't under construction
 *
 * @author Adam Staněk (velbloud)
 * @since Apr 14, 2013
 */
class ConstructionMode {

	static function onApplicationRequest(Application $app, Request $request, SystemContainer $container) {

		$runningTestMode = false;

		// Forwardy (jako je napr. presmerovani na error presenter neresim)
		if($request->method != Request::FORWARD) {

			$host = $container->httpRequest->url->host;

			// Pokud jsem v produkcnim rezimu, musim zkontrolovat, jestli stranka neni ve vystavbe
			if(isset($container->parameters['underConstruction']) && $container->parameters['underConstruction'] == true) {

				// Pokud je stranka ve vystavbe a nejsem na testovaci domene, vyhodim vyjimku
				// Akceptovany jsou domeny koncici na test.*.* nebo .bilahora.v3net.cz
				if(!Strings::match($host, '#^(.+?\.)?test\.[^\.]+\.[^\.]+$#') && !Strings::match($host, '#\.bilahora\.v3net\.cz$#')) {
					/// @todo co kdyz je to pozadavek z admina?
						throw new vBuilder\Application\UnderConstructionException();

					return ;
				} else {
					$runningTestMode = true;
				}
			}

			// Pokud nejsem v produkcnim rezimu, musim se postarat o zpetny redirect (nemsi existovat 2 URL se stejnym obsahem)
			else {

				if($matches = Strings::match($host, '#^(.+?\.)?test\.([^\.]+\.[^\.]+)$#')) {
					$newHost = $matches[1] . $matches[2];

					$url = clone $container->httpRequest->url;
					$url->host = $newHost;

					$container->httpResponse->redirect($url);
					exit;
				}

			}
		}

		// Test panel (in production mode) - inicializuju ho pri zpracovani requestu aplikace,
		// takze mam jistotu ze nejsem poustenej z konzole.

		if($runningTestMode) {
			$ajaxDetected = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

			if(!$ajaxDetected) {
				register_shutdown_function(function () {

					// Render test bar
					$bar = new Nette\Diagnostics\Bar;
					$bar->addPanel(new vBuilder\Diagnostics\TestBar);
					echo $bar->render();

				});
			}
		}
	}

}
