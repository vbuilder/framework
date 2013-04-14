<?php

/**
 * This file is part of vBuilder CMS.
 *
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 *
 * For more information visit http://www.vbuilder.cz
 *
 * vBuilder is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vBuilder. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Application\UI;

use vBuilder,
	vBuilder\Application\WebFilesGenerator,
	Nette;

/**
 * Base presenter for redaction based pages
 *
 * @author Adam Staněk (velbloud)
 * @since Sep 25, 2011
 */
class Presenter extends Nette\Application\UI\Presenter {
	
	/**
	 * Starts up
	 * 
	 * @return void
	 */
	protected function startup() {
		parent::startup();
		
		// Defaultne vypiname layout pro AJAXovy pozadavky
		if($this->isAjax()) {
			$this->setLayout(false);
		}
	}
		
	/**
	 * Compilation time templating filters
	 * 
	 * @param  Nette\Templating\Template
	 * @return void
	 */
	final public function templatePrepareFilters($template) {

		// We cannot use Nette\Latte\Engine class directly, because we need our SystemMacros
		// to be declared befor UIMacros
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

		vBuilder\Latte\Macros\UIMacros::install($compiler);
		Nette\Latte\Macros\FormMacros::install($compiler);
	}

	/**
	 * Magic template factory - docasne reseni
	 * @param string $file Filename relatice to the dir. If not set, "default" will be used
	 * @return Nette\Templating\FileTemplate
	 */
	public function createTemplate($file = null, $class = null) {
		$tpl = parent::createTemplate();
		$tpl->context = $this->context;
		
		$tpl->registerHelper('stripBetweenTags', 'vBuilder\\Latte\\Helpers\\FormatHelpers::stripBetweenTags');
		$tpl->setTranslator($this->context->translator);

		return $tpl;
	}
	
	/**
	 * Overloaded sendTemplate for web files generation
	 */
	public function sendTemplate() {
		// Invokes createTemplate and etc.
		$template = $this->getTemplate();
		
		if (!$template) {
			return;
		}

		if ($template instanceof Nette\Templating\IFileTemplate && !$template->getFile()) { // content template
			$files = $this->formatTemplateFiles();
			foreach ($files as $file) {
				if (is_file($file)) {
					$template->setFile($file);
					break;
				}
			}

			if (!$template->getFile()) {
				$file = preg_replace('#^.*([/\\\\].{1,70})$#U', "\xE2\x80\xA6\$1", reset($files));
				$file = strtr($file, '/', DIRECTORY_SEPARATOR);
				throw new Nette\Application\BadRequestException("Page not found. Missing template '$file'.");
			}
		}
		
		if ($this->layout !== FALSE) { // layout template
			$files = $this->formatLayoutTemplateFiles();
			foreach ($files as $file) {
				if (is_file($file)) {
					$template->layout = $file;
					$template->_extends = $file;
					break;
				}
			}

			if (empty($template->layout) && $this->layout !== NULL) {
				$file = preg_replace('#^.*([/\\\\].{1,70})$#U', "\xE2\x80\xA6\$1", reset($files));
				$file = strtr($file, '/', DIRECTORY_SEPARATOR);
				throw new Nette\FileNotFoundException("Layout not found. Missing template '$file'.");
			}
		}
		
		
		// Redering kvuli tomu, aby se zpracovaly pripadna addCss/Js makra pro generator
		$rendered = (string) $template;

		if($this->context->hasService('webFilesGenerator'))	{
			$webFileTypes = array(WebFilesGenerator::JAVASCRIPT, WebFilesGenerator::STYLESHEET);
			$webFiles = $this->context->webFilesGenerator;
			
			// Generates web files
			$webFiles->registerOutput(TEMP_DIR . '/webfiles/' . $webFiles->getHash(WebFilesGenerator::JAVASCRIPT) . '.js', WebFilesGenerator::JAVASCRIPT);
			$webFiles->registerOutput(TEMP_DIR . '/webfiles/' . $webFiles->getHash(WebFilesGenerator::STYLESHEET) . '.css', WebFilesGenerator::STYLESHEET);
			
			foreach($webFileTypes as $fileType) {			
				if($webFiles->isCached($fileType) === false) {
					$webFiles->generate($fileType);
				}
			}
			
			// Oprava pripadne zmeny CSS / JS souboru po vygenerovani hlavicky
			$this['webFiles']->lateRenderFix($rendered);
		}
		
		$this->sendResponse(new Nette\Application\Responses\TextResponse($rendered));
	}
	
	public function createComponentHeadGenerator($name) {
		$control = new Controls\HeadGenerator($this, $name);
		return $control;
	}
	
	public function createComponentWebFiles($name) {
		$control = new Controls\WebFilesHeadGenerator($this, $name);
		return $control;
	}
	
}
