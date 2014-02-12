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

use vBuilder,
	Nette;

/**
 * Base implementation of REST API Presenter
 *
 * @todo JSON formated error responses on Exception
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 12, 2014
 */
class Presenter extends Nette\Object implements Nette\Application\IPresenter {

	/** @internal parameter keys */
	const PARAM_KEY_PATH = 'path';
	/**/

	/** @var Nette\Http\IRequest @inject */
	public $httpRequest;

	/** @var Nette\Http\IResponse @inject */
	public $httpResponse;

	/** @var vBuilder\RestApi\RequestRouter @inject */
	public $requestRouter;

	/** @var Nette\DI\Container @inject */
	public $systemContainer;

	/** @var mixed|NULL */
	protected $postData;

	/** @var string */
	protected $outputContentType;

	/**
	 * @return Nette\Application\IResponse
	 */
	public function run(Nette\Application\Request $request) {

		// Query output content type -------------------------------------------

		// Accept header is comma separated fallback sequence
		$cTypes = preg_split('/,/', $this->httpRequest->getHeader('Accept'), 0, PREG_SPLIT_NO_EMPTY);
		foreach($cTypes as $cType) {
			// We ignore all the options
			$cType = preg_replace('/;.*/', '', $cType);

			if(strcasecmp($cType,  'text/html') === 0 || strcmp($cType,  '*/*') === 0) {
				$this->outputContentType = 'text/html';
				$this->httpResponse->setContentType('text/html', 'utf-8');
				break;

			} elseif(strcasecmp($cType,  'application/json') === 0) {
				$this->outputContentType = 'application/json';
				$this->httpResponse->setContentType('application/json', 'utf-8');
				break;
			}
		}

		if($this->outputContentType === NULL)
			throw new Nette\Application\BadRequestException("No Accept header given or not satisfiable");

		// Find request handler ------------------------------------------------

		// Gather resource path
		$parameters = $request->getParameters();
		$resourcePath = isset($parameters[self::PARAM_KEY_PATH]) ? trim($parameters[self::PARAM_KEY_PATH]) : NULL;
		if(!$resourcePath) throw new Nette\Application\BadRequestException("No resource path given.");

		// Request router expects leading slash
		if($resourcePath[0] != '/') $resourcePath = "/$resourcePath";

		// Request router: find resource handler
		/** @var vBuilder\RestApi\Request|NULL */
		$handlerRequest = $this->requestRouter->createRequest(
			$this->httpRequest->getMethod(),
			$resourcePath
		);

		if($handlerRequest === NULL)
			throw new Nette\Application\BadRequestException("No resource handler for given URL / HTTP method");

		// Decode POST data ----------------------------------------------------
		if($this->httpRequest->isPost()) {
			$cType = $this->httpRequest->getHeader('Content-Type');
			if(strcasecmp($cType,  'application/json') === 0) {
				$this->postData = Nette\Utils\Json::decode(file_get_contents('php://input'));
			}

			elseif(strcasecmp($cType,  'application/x-www-form-urlencoded') === 0) {
				$this->postData = $this->httpRequest->getPost();
			}

			elseif($cType === NULL)
				throw new Nette\Application\BadRequestException("Content-Type header is mandatory for POST requests");
			else
				throw new Nette\Application\BadRequestException("Request content type of POST data is not supported");
		}

		// Create resource instance and prepare all dependencies ---------------
		$class = $handlerRequest->getResourceClassName();
		$resource = new $class;
		$this->systemContainer->callInjects($resource);

		// Prepare and order invoke parameters ---------------------------------
		$mReflection = $handlerRequest->getMethodReflection();
		$invokeParams = array();
		$requestParams = $handlerRequest->getParameters();
		$definedParams = $mReflection->getParameters();

		$index = 0;
		foreach($definedParams as $pReflection) {
			$index++;

			// Parameter not given in URL?
			if(!isset($requestParams[$pReflection->getName()])) {
				// Default value where available
				if($pReflection->isDefaultValueAvailable()) {
					$invokeParams[$pReflection->getName()] = $pReflection->getDefaultValue();
					continue;
				}

				throw new Nette\Application\BadRequestException("Missing #$index parameter for $class::" . $mReflection->getName() . '()');
			}

			$invokeParams[$pReflection->getName()] = $requestParams[$pReflection->getName()];
		}

		// Invoke handler method on resource instance
		$responsePayload = $mReflection->invokeArgs($resource, $invokeParams);
		return $this->createResponse($responsePayload);
	}

	/**
	 * Creates and returns response from given payload formatted for selected
	 * output content type.
	 *
	 * @return Nette\Application\IResponse
	 */
	protected function createResponse($payload) {
		if($this->outputContentType == 'application/json')
			return new Nette\Application\Responses\JsonResponse($payload);
		else
			return new Nette\Application\Responses\TextResponse(
				(string) Nette\Utils\Html::el('pre', Nette\Utils\Json::encode($payload, Nette\Utils\Json::PRETTY))
			);
	}

}