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

namespace vBuilder\Application\UI;

use vBuilder,
	vBuilder\Utils\Strings,
	Nette,
	Nette\Application\UI\ITemplate,
	Nette\Application\UI\ITemplateFactory;

/**
 * Control rendering class
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 *
 * @property-read ITemplate $template
 */
class ControlRenderer extends vBuilder\Object {

	/** @var vBuilder\Application\UI\Control control */
	protected $control;

	/** @var ITemplateFactory */
	private $templateFactory;

	/** @var ITemplate template */
	private $template;

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
	 * @return Nette\DI\Container
	 */
	final public function getControl() {
			return $this->control;
	}

	/**
	 * Returns current context
	 *
	 * @return Nette\DI\Container
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
			if(!$this->template->getFile()) {
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

	public function setTemplateFactory(ITemplateFactory $templateFactory) {
		$this->templateFactory = $templateFactory;
	}

	/**
	 * @param ITemplate|FALSE
	 * @return self
	 */
	public function setTemplate($template) {

		if($template !== FALSE && !($template instanceof ITemplate))
			throw new Nette\UnexpectedValueException("Expected instance of Nette\\Application\\UI\\ITemplate or FALSE.");

		$this->template = $template;
		return $this;
	}

	/**
	 * @return ITemplate
	 */
	public function getTemplate()
	{
		if ($this->template === NULL) {
			$value = $this->createTemplate();
			if (!$value instanceof ITemplate && $value !== NULL) {
				$class2 = get_class($value); $class = get_class($this);
				throw new Nette\UnexpectedValueException("Object returned by $class::createTemplate() must be instance of Nette\\Application\\UI\\ITemplate, '$class2' given.");
			}
			$this->template = $value;
		}
		return $this->template;
	}

	/**
	 * @return ITemplate
	 */
	protected function createTemplate()
	{
		$templateFactory = $this->templateFactory ?: $this->getPresenter()->getTemplateFactory();
		$template = $templateFactory->createTemplate($this->control);

		$template->renderer = $this;

		return $template;
	}

	/**
	 * @param ITemplate
	 * @return void
	 */
	public function templatePrepareFilters($template) {

		/*

		$compiler = $template->getLatte()->getCompiler();

		Nette\Latte\Macros\CoreMacros::install($compiler);
		$compiler->addMacro('cache', new Nette\Latte\Macros\CacheMacro($compiler));

		// Must be after CoreMacros (overrides {_'xxx'})
		vBuilder\Latte\Macros\TranslationMacros::install($compiler);

		// Must be before UIMacros
		vBuilder\Latte\Macros\SystemMacros::install($compiler);

		// Auto-extend for templates
		$autoExtend = NULL;
		if($template instanceof Nette\Templating\FileTemplate && $template->getFile() != "" && $template->getFile() != $this->getDefaultTemplateFile() && file_exists($this->getDefaultTemplateFile())) {

			 // If the basename is same but the dir differs
			 if(preg_match('#^(.*?)([^/]+)$#', $template->getFile(), $mCurrent) && preg_match('#^(.*?)([^/]+)$#', $this->getDefaultTemplateFile(), $mDefault)) {
			 	if($mCurrent[2] == $mDefault[2]) {
			 		$autoExtend = $this->getDefaultTemplateFile();
			 	}
			 }
		}

		if($autoExtend) {
			vBuilder\Latte\Macros\UIMacros::installWithAutoExtend($compiler, $autoExtend);
		} else
			vBuilder\Latte\Macros\UIMacros::install($compiler);

		Nette\Latte\Macros\FormMacros::install($compiler);

		vBuilder\Latte\Macros\RegionMacros::install($compiler);

		*/
	}

}