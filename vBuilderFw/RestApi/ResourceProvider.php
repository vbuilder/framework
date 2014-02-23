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

namespace vBuilder\RestApi;

use vBuilder, Nette;

/**
 * Base resource implementation
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 12, 2014
 */
class ResourceProvider extends Nette\Object {

	/** @var Nette\Http\IRequest @inject */
	public $httpRequest;

	/** @var Nette\Http\IResponse @inject */
	public $httpResponse;

	/** @var Nette\Application\IPresenter */
	public $presenter;

	/** @var vBuilder\Security\User @inject */
	public $user;

	/**
	 * Startup
	 *
	 * @return void
	 */
	public function startup() {

	}

	// -------------------------------------------------------------------------

	/**
	 * Returns POST data
	 *
	 * @return mixed
	 */
	public function getPostData() {
		return $this->presenter->getPostData();
	}

	/**
	 * Returns GET data
	 *
	 * @return array
	 */
	public function getGetData() {
		return $this->httpRequest->getQuery();
	}

	/**
	 * Sets HTTP header for the response
	 *
	 * @param  string  header name
	 * @param  string  header value
	 * @return self
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setHeader($name, $value) {
		$this->httpResponse->setHeader($name, $value);
		return $this;
	}


 }