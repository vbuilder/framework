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

namespace vBuilder\Application\Routers;

use vBuilder,
	Nette,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\RouteList;

/**
 * Creates routes for generated CSS / JS files
 *
 * @author Adam Staněk (V3lbloud)
 * @since May 1, 2014
 */
class WebFilesRouteFactory {

	private $dir;

	/** @var Nette\Http\Response */
	private $httpResponse;

	function __construct($dir, Nette\Http\Response $httpResponse) {
		$this->dir = realpath($dir);
		$this->httpResponse = $httpResponse;
	}

	public function onRouterSetup(RouteList $router) {
		$router[] = new Route('<type js>/<file [a-f0-9]{32}>.js', callback($this, 'handleRequest'), Route::ONE_WAY);
		$router[] = new Route('<type css>/<file [a-f0-9]{32}>.css', callback($this, 'handleRequest'));
	}

	public function handleRequest(Nette\Application\Application $app) {


	}

}