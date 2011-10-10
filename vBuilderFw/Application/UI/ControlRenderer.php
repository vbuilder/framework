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

 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Application\UI;

use vBuilder,
		Nette;

/**
 * Control rendering class
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class ControlRenderer extends vBuilder\Object {
	
	/** @var vBuilder\Application\UI\Control control */
	protected $control;
	
	/** 
	 * @var Nette\Templating\ITemplate template
	 * @internal
	 */
	private $_template;
	
	/**
	 * @var string template directory path (absolute) 
	 * @internal
	 */
	private $_tplDir;
	
	/**
	 * Constructor
	 * 
	 * @param Control owning control
	 */
	function __construct(Control $control) {
		$this->control = $control;
	}
	
	/**
	 * Returns current context
	 * 
	 * @return Nette\DI\IContainer
	 */
	final public function getControl() {
			return $this->control;
	}
	
	/**
	 * Returns current context
	 * 
	 * @return Nette\DI\IContainer
	 */
	final public function getContext() {
			return $this->control->context;
	}
	
	/**
	 * Returns the presenter of belonging control.
	 * @param  bool   throw exception if presenter doesn't exist?
	 * @return Presenter|NULL
	 */
	final public function getPresenter($need = TRUE) {
		return $this->control->getPresenter($need);
	}
	
	/**
	 * Returns current control's view
	 * 
	 * @return string view name
	 */
	final public function getView() {
		return $this->control->view;
	}
	
	/**
	 * Default action/view is always defined, so we don't throw an exception
	 */
	function renderDefault() {
		
	}
	
	/**
	 * Renders matching template for current view
	 * 
	 * @return void
	 */
	function render() {
		// File templates
		if($this->template instanceof Nette\Templating\IFileTemplate && !$this->template->getFile()) {
			foreach($this->formatTemplateFiles() as $file) {
				if(file_exists($file)) {
					$this->template->setFile($file);
					$this->template->render();
					return ;
				}
			}
		
			throw new Nette\Application\BadRequestException("Page not found. Missing template '$file'.");
		}
		
		// Other templates
		$this->template->render();
	}
	
	/**
	 * Sets primary template directory
	 * 
	 * @param string path to directory (absolute)
	 */
	public function setTemplateDirectory($path) {
		if(!is_dir($path))
			throw new \InvalidArgumentException("$path is not a directory.");
		
		$this->_tplDir = $path;
	}
	
	/**
	 * Return default template directory (depending on path to class file)
	 * 
	 * @return string path to directory 
	 */
	protected function getDefaultTemplateDirectory() {
		return dirname($this->getReflection()->getFileName()) . '/Templates';
	}
	
	/**
	 * Retursn current template directory
	 * 
	 * @return string path to directory
	 */
	function getTemplateDirectory() {
		return isset($this->_tplDir)
							? $this->_tplDir
							: $this->getDefaultTemplateDirectory();
	}
	
	/**
	 * Formats view template file names.
	 * @return array
	 */
	public function formatTemplateFiles() {
		$filename = $this->view . '.latte';
		
		if(isset($this->_tplDir)) {
			return array(
					$this->_tplDir . '/' . $filename,
					$this->getDefaultTemplateDirectory() . '/' . $filename
			);
		}

		return array($this->getDefaultTemplateDirectory() . '/' . $filename);
	}
	
	/**
	 * Returns template
	 * 
	 * @return Nette\Templating\ITemplate 
	 */
	final public function getTemplate() {
		if(!isset($this->_template)) {
			$value = $this->createTemplate();
			if (!$value instanceof Nette\Templating\ITemplate && $value !== NULL) {
				$class2 = get_class($value); $class = get_class($this);
				throw new Nette\UnexpectedValueException("Object returned by $class::createTemplate() must be instance of Nette\\Templating\\ITemplate, '$class2' given.");
			}

			$this->_template = $value;
		}
		
		return $this->_template;
	}
	
	/**
	 * Template factory
	 * 
	 * @param string class name to use (if null FileTemplate will be used)
	 * 
	 * @return Nette\Templating\ITemplate
	 */
	protected function createTemplate($class = NULL) {
		// No need for checking class because of getTemplate
		$template = $class ? new $class : new Nette\Templating\FileTemplate;
		$presenter = $this->getPresenter(FALSE);
		$template->onPrepareFilters[] = callback($this, 'templatePrepareFilters');
		$template->registerHelperLoader('Nette\Templating\DefaultHelpers::loader');

		// default parameters
		$template->renderer = $this;
		$template->control = $this->control;
		$template->presenter = $presenter;
		$template->context = $this->context;
		$template->baseUri = $template->baseUrl = rtrim($this->context->httpRequest->getUrl()->getBaseUrl(), '/');
		$template->basePath = preg_replace('#https?://[^/]+#A', '', $template->baseUrl);
		
		if ($presenter instanceof Presenter) {
			$template->setCacheStorage($presenter->getContext()->templateCacheStorage);
			$template->user = $presenter->getUser();
			$template->netteHttpResponse = $presenter->getHttpResponse();
			$template->netteCacheStorage = $presenter->getContext()->cacheStorage;

			// flash message
			if ($presenter->hasFlashSession()) {
				$id = $this->getParamId('flash');
				$template->flashes = $presenter->getFlashSession()->$id;
			}
		}
		
		if (!isset($template->flashes) || !is_array($template->flashes)) {
			$template->flashes = array();
		}

		return $template;
	}
	
	/**
	 * Descendant can override this method to customize template compile-time filters.
	 * @param  Nette\Templating\Template
	 * @return void
	 */
	public function templatePrepareFilters($template, &$engine = null) {
		if(!$engine) $engine = new Nette\Latte\Engine;
		
		vBuilder\Latte\Macros\SystemMacros::install($engine->parser);
		$template->registerFilter($engine);
	}
	
}