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

	/**
	 * Error responses
	 */
	const ERROR_NOT_FOUND = 'not_found';
	const ERROR_INVALID_REQUEST = 'invalid_request';
	/**/

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
	 * Returns client information
	 *
	 * @return vBuilder\RestApi\OAuth2\Client|NULL
	 */
	public function getClient() {
		return $this->presenter->getClient();
	}

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

	/**
	 * Sets HTTP response code
	 *
	 * @param int code
	 * @return self
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setCode($code) {
		$this->httpResponse->setCode($code);
		return $this;
	}

	/**
	 * Creates absolute URL for given target
	 *
	 * @param string target
	 * @param array parameters
	 *
	 * @return string
	 * @throws Nette\InvalidArgumentException
	 */
	public function link($target = NULL, array $params = array()) {
		list($class, $method) = $this->parseLinkTarget($target);

		list($httpMethod, $path, $addtionalParams) = $this->presenter->requestRouter->createUrl(
			$class, $method, $params
		);

		return $this->presenter->link($path, $addtionalParams);
	}

	/**
	 * Creates payload structure for HATEOAS link
	 *
	 * @param string rel
	 * @param string target
	 * @param array parameters
	 *
	 * @return array
	 * @throws Nette\InvalidArgumentException
	 */
	public function hateoasLink($rel = 'self', $target = NULL, array $params = array()) {
		list($class, $method) = $this->parseLinkTarget($target);

		list($httpMethod, $path, $addtionalParams) = $this->presenter->requestRouter->createUrl(
			$class, $method, $params
		);

		return array(
			'rel' => $rel,
			'href' => $this->presenter->link($path, $addtionalParams),
			'method' => $httpMethod
		);
	}

	/**
	 * Parses link target string to class and method
	 *
	 * @param string|NULL target
	 *
	 * @return array (string class name, string method name)
	 * @throws Nette\InvalidArgumentException
	 */
	protected function parseLinkTarget($target = NULL) {
		if($target === NULL || $target == 'this') {

			$class = get_called_class();
			$method = $this->presenter->resourceRequest->getMethodReflection()->getName();

		} else if(preg_match('#^((\\\\?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*))*)(::([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*))?$#', $target, $matches)) {
			if(isset($matches[5])) {
				$class = $matches[1];
				$method = $matches[5];

			} else {
				$class = get_called_class();
				$method = $matches[1];

				if(Nette\Utils\Strings::contains($method, '\\'))
					throw new Nette\InvalidArgumentException("Invalid link target '$target'");
			}

		} else
			throw new Nette\InvalidArgumentException("Invalid link target '$target'");

		return array($class, $method);
	}

 }