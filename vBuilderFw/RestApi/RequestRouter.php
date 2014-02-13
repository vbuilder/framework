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
	Nette,
	Nette\Utils\Strings;

/**
 * REST API Router
 *
 * - manages registered Resource classes
 * - creates Request container with matching handler method
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 12, 2014
 */
class RequestRouter extends Nette\Object {

	const ANNOTATION_URL = 'Url';

	/** @var vBuilder\Application\ClassInfoProvider @inject */
	public $classInfoProvider;

	/** @var array */
	private $classes = array();

	// -------------------------------------------------------------------------
	// -- Resource registration
	// -------------------------------------------------------------------------

	/**
	 * Register resource class
	 *
	 * @return self
	 * @throws Nette\InvalidArgumentException if class does not exist or is not subclassing our base class
	 */
	public function registerClass($className) {
		if(!class_exists($className))
			throw new Nette\InvalidArgumentException("Class $className does not exist");

		if(!is_a($className, $this->resourceProviderClass, TRUE))
			throw new Nette\InvalidArgumentException("Expected subclass of " . $this->resourceClass);

		if(!isset($this->classes[$className]))
			$this->classes[$className] = NULL;

		return $this;
	}

	/**
	 * Register all Resource subclasses
	 *
	 * @return self
	 */
	public function registerAllClasses() {
		$classes = $this->classInfoProvider->getAllChildrenOf($this->resourceProviderClass);

		foreach($classes as $className) {
			if(!isset($this->classes[$className]))
				$this->classes[$className] = NULL;
		}

		return $this;
	}

	/**
	 * Returns base ResourceProvider class
	 */
	public function getResourceProviderClass() {
		return __NAMESPACE__ . '\\ResourceProvider';
	}

	// -------------------------------------------------------------------------
	// -- Request creation
	// -------------------------------------------------------------------------

	/**
	 * Creates request container with matching request handler
	 *
	 * @param string HTTP method (case does not matter)
	 * @param string resource path (expecting leading slash)
	 *
	 * @return Request|NULL
	 */
	public function createRequest($method, $resourceUrl) {

		// Go through all registered classes
		foreach($this->classes as $class => &$info) {
			// Load class info if not loaded yet
			if($info === NULL) $info = $this->getClassInfo($class);

			// Go through all resource handlers in current class
			foreach($info->handlers as $handler) {
				// Filter out handlers not matching HTTP method
				if(strcasecmp($method, $handler->method) !== 0) continue;

				// Go through all registered URLs for current handler
				foreach($handler->urls as $url => $urlInfo) {
					// Load UrlMatcher if necessary
					if($urlInfo === NULL) $urlInfo = $this->getUrlInfo($url);

					// If URL matches, return request for current handler
					if(($params = $urlInfo->matches($resourceUrl)) !== NULL) {
						return new Request(
							$class,
							$handler->reflection,
							$params
						);
					}
				}
			}
		}

		return NULL;
	}

	/**
	 * Prepares class info
	 *
	 * @return StdClass
	 */
	protected function getClassInfo($class) {
		$info = new \StdClass;

		$info->reflection = new Nette\Reflection\ClassType($class);
		$info->handlers = array();

		// Go through all public class methods
		$methods = $info->reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach($methods as $method) {

			// Skip non-callable or implicit methods
			if(!$method->isUserDefined() || $method->isAbstract())
				continue;

			$infoHandler = new \StdClass;
			$infoHandler->reflection = $method;

			// Guess HTTP method for current method (or skip if not matching)
			if(Strings::startsWith($method->getName(), 'post'))
				$infoHandler->method = 'POST';

			elseif(Strings::startsWith($method->getName(), 'get'))
				$infoHandler->method = 'GET';

			elseif(Strings::startsWith($method->getName(), 'put'))
				$infoHandler->method = 'PUT';

			elseif(Strings::startsWith($method->getName(), 'delete'))
				$infoHandler->method = 'DELETE';
			else
				continue;

			// Gather method annotations (and skip those without any resource URL defined)
			$annotations = $method->getAnnotations();
			if(!isset($annotations[self::ANNOTATION_URL]))
				continue;

			// Register method URLs
			$infoHandler->urls = array();
			foreach((array) $annotations[self::ANNOTATION_URL] as $url)
				$infoHandler->urls[$url] = NULL;

			$info->handlers[] = $infoHandler;
		}

		return $info;
	}

	/**
	 * Prepares URL matcher
	 *
	 * @return ResourceUrlMatcher
	 */
	protected function getUrlInfo($url) {
		return new ResourceUrlMatcher($url);
	}

}