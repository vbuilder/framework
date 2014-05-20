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
	Nette\Application\IResponse,
	SystemContainer;

/**
 * Checks if application isn't under construction.
 *
 * @note it's run only in production mode
 *
 * @author Adam Staněk (velbloud)
 * @since Apr 14, 2013
 */
class ConstructionMode extends Nette\Object {

	private $enabled;

	/** @var Nette\Http\IRequest */
	private $httpRequest;

	/** @var Nette\Http\IRequest */
	private $httpResponse;

	/** @var array of callbacks (ConstructionMode $sender, Nette\Http\Url $url) **/
	private $urlTesters = array();

	/**
	 * @param bool web under construction?
	 */
	public function __construct($enabled) {
		$this->enabled = $enabled;
	}

	public function injectHttpRequest(Nette\Http\IRequest $r) {
		$this->httpRequest = $r;
	}

	public function injectHttpResponse(Nette\Http\IResponse $r) {
		$this->httpResponse = $r;
	}

	public function addTestUrl($cb) {
		if(!is_callable($cb))
			throw new Nette\InvalidArgumentException("Expected callable");

		$this->urlTesters[] = $cb;
	}

	// -------------------------------------------------------------------------

	/**
	 * Checks if given URL trigger TEST mode
	 *
	 * @param Nette\Http\Url
	 * @return bool
	 */
	protected function isTestUrl(Nette\Http\Url $url) {

		foreach($this->urlTesters as $cb) {
			if($cb($this, $url))
				return TRUE;
		}

		return Strings::match($url->host, '#^(.+?\.)?test\.[^\.]+\.[^\.]+$#')
			|| Strings::match($url->host, '#\.bilahora\.v3net\.cz$#');
	}

	/**
	 * Translates URL to production
	 * Example: test.mujweb.cz/12 -> www.mujweb.cz/12
	 *
	 * @param Nette\Http\Url test url
	 * @return Nette\Http\Url|NULL production url or NULL if translation can't be derived
	 */
	protected function translateUrl(Nette\Http\Url $url) {

		if($matches = Strings::match($url->host, '#^(.+?\.)?test\.([^\.]+\.[^\.]+)$#')) {
			$newHost = $matches[1] . $matches[2];
			$url = clone $url;
			$url->host = $newHost;
			return $url;
		}

		return NULL;
	}

	// -------------------------------------------------------------------------

	/**
	 * Test bar factory
	 */
	public function createTestBar() {
		$bar = new Nette\Diagnostics\Bar;
		$bar->addPanel(new vBuilder\Diagnostics\TestBar);
		return $bar;
	}

	/**
	 * Checks each request for test mode
	 */
	public function onApplicationRequest(Application $app, Request $request) {

		// Forwardy (jako je napr. presmerovani na error presenter neresim)
		if($request->method != Request::FORWARD) {

			// Pokud nejsem v testovacim modu vyhodim vyjimku, ze je web v rekonstrukci
			if(!$this->isTestUrl($this->httpRequest->url)) {
				if($this->enabled)
					throw new vBuilder\Application\UnderConstructionException();
			}

			// Pokud web uz neni ve vystavbe, musim se postarat o zpetny redirect
			// moznych URL sdilenych administratorem v dobe vystavby
			// (nesmi existovat 2 URL se stejnym obsahem, tj: test.mujweb.cz/12 -> www.mujweb.cz/12)
			elseif(!$this->enabled) {
				if($newUrl = $this->translateUrl($this->httpRequest->url)) {
					$this->httpResponse->redirect((string) $newUrl);
					exit;
				}
			}
		}
	}

	/**
	 * Renders test mode bar if necessary
	 */
	public function onApplicationResponse(Application $app, IResponse $response) {
		if(!$this->isTestUrl($this->httpRequest->url))
			return ;

		$that = $this;
		$httpResponse = $this->httpResponse;

		register_shutdown_function(function () use ($that, $httpResponse) {
			if(preg_match('#^text/html#i', $httpResponse->getHeader('Content-Type'))) {
				echo $that->createTestBar()->render();
			}
		});
	}
}
