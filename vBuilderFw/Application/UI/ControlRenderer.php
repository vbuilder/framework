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
		if($this->template !== FALSE) {
			// File templates
			if($this->template instanceof Nette\Templating\IFileTemplate && !$this->template->getFile()) {
				foreach($this->formatTemplateFiles() as $file) {
					if(file_exists($file)) {					
						$this->template->setFile($file);
						
						$this->template->render();
						return ;
					}
				}
			
				throw new Nette\Application\BadRequestException("Template not found in " . implode(', ', $this->formatTemplateFiles()));
			}
			
			// Other templates
			$this->template->render();
		}
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
	 * Returns path to default template file for given view
	 * 
	 * @param null|string view name (if null, current view is used)
	 * @return string absolute file path
	 */
	protected function getDefaultTemplateFile($view = null) {
		if(!$view) $view = $this->view;
		$filename = $view . '.latte';
		
		return $this->getDefaultTemplateDirectory() . '/' . $filename;
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
					$this->getDefaultTemplateFile()
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
	 * Sets template
	 * 
	 * @param FALSE|Nette\Templating\ITemplate new template
	 */
	public function setTemplate($template) {
		if($template === FALSE || $template instanceof Nette\Templating\ITemplate) {
			 $this->_template = $template;
			 return ;
		}

		$class = get_called_class();
		throw new Nette\UnexpectedValueException("Invalid object given for $class::setTemplate. FALSE or implementation of Nette\\Templating\\ITemplate required.");
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
		$template->registerHelperLoader('Nette\Templating\Helpers::loader');

		$template->registerHelper('stripBetweenTags', 'vBuilder\Latte\Helpers\FormatHelpers::stripBetweenTags');

		// default parameters
		$template->renderer = $this;
		$template->control = $template->_control = $this->control;
		$template->presenter = $template->_presenter = $presenter;
		$template->context = $this->context;
		$template->baseUri = $template->baseUrl = rtrim($this->context->httpRequest->getUrl()->getBaseUrl(), '/');
		$template->basePath = preg_replace('#https?://[^/]+#A', '', $template->baseUrl);
		
		if ($presenter instanceof Nette\Application\UI\Presenter) {
			$template->setCacheStorage($this->context->{'nette.templateCacheStorage'});
			$template->user = $this->context->user;
			$template->netteHttpResponse = $this->context->httpResponse;
			$template->netteCacheStorage = $this->context->getByType('Nette\Caching\IStorage');

			// flash message
			if ($presenter->hasFlashSession()) {			
				$id = $this->control->getParameterId('flash');
				$template->flashes = $presenter->getFlashSession()->$id;
			}
		}
		
		if (!isset($template->flashes) || !is_array($template->flashes)) {
			$template->flashes = array();
		}

		return $template;
	}
	
	/**
	 * Compilation time templating filters
	 * 
	 * @param  Nette\Templating\Template
	 * @return void
	 */
	final public function templatePrepareFilters($template) {

		// We cannot use Nette\Latte\Engine class directly, because we need our UIMacros patch
		// $this->getPresenter()->getContext()->nette->createLatte()

		$parser = new Nette\Latte\Parser;
		$compiler = new Nette\Latte\Compiler;
		$compiler->defaultContentType = Nette\Latte\Compiler::CONTENT_XHTML;

		$this->lattePrepareMacros($compiler, $template);

		$template->registerFilter(function ($s) use ($compiler, $parser) {
			return $compiler->compile($parser->parse($s));
		});
	}

	/**
	 * Prepares Latte macros
	 * 
	 * @param  Nette\Latte\Compiler
	 * @return void
	 */
	protected function lattePrepareMacros(Nette\Latte\Compiler $compiler, Nette\Templating\Template $template) {
		Nette\Latte\Macros\CoreMacros::install($compiler);
		$compiler->addMacro('cache', new Nette\Latte\Macros\CacheMacro($compiler));
		
		// Must be before UIMacros
		vBuilder\Latte\Macros\SystemMacros::install($compiler);

		// Auto-extend for templates
		if($template instanceof Nette\Templating\FileTemplate && $template->getFile() != "" && $template->getFile() != $this->getDefaultTemplateFile() && file_exists($this->getDefaultTemplateFile())) {
			vBuilder\Latte\Macros\UIMacros::installWithAutoExtend($compiler, $this->getDefaultTemplateFile());
		} else
			vBuilder\Latte\Macros\UIMacros::install($compiler);

		Nette\Latte\Macros\FormMacros::install($compiler);

		vBuilder\Latte\Macros\RegionMacros::install($compiler);
	}
	
}