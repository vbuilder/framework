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

namespace vBuilder\Mail;

use vBuilder,
		Nette;

/**
 * E-mail notificator base for event listeners
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 22, 2011
 */
class MailNotificator extends vBuilder\EventListener {
	
	/** @var Nette\Mail\Message */
	private $_message;
	
	/** @var Nette\Templating\ITemplate */
	private $_template;
	
	/** @var Nette\DI\Container */ 
	protected $context;
	
	/**
	 * Constructor
	 * 
	 * @param Nette\DI\Container $context 
	 */
	public function __construct(Nette\DI\Container $context) {
		$this->context = $context;		
	}
	
	// ***************************************************************************
	
	/**
	 * Returns mail message
	 * 
	 * @return Nette\Mail\Message 
	 */
	final public function getMessage() {
		if(!isset($this->_message)) {
			$this->_message = $this->createMessage();
		}
		
		return $this->_message;
	}
	
	/**
	 * Mail message factory 
	 * 
	 * @return Nette\Mail\Message 
	 */
	protected function createMessage() {
		$message = new Nette\Mail\Message;
		
		// CLI
		if($this->context->httpRequest->getUrl()->getHost() != "")
			$message->setFrom('info@' . $this->context->httpRequest->getUrl()->getHost());
			
		return $message;
	}
	
	// ***************************************************************************
	
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
	 * Mail template factory
	 * 
	 * @param string class name to use (if null FileTemplate will be used)
	 * 
	 * @return Nette\Templating\ITemplate
	 */
	protected function createTemplate($class = NULL) {
		// No need for checking class because of getTemplate
		$template = $class ? new $class : new Nette\Templating\FileTemplate;
		$presenter = $this->context->application->getPresenter();
		
		$template->onPrepareFilters[] = callback($this, 'templatePrepareFilters');
		$template->registerHelperLoader('Nette\Templating\Helpers::loader');
		$template->setCacheStorage($this->context->nette->templateCacheStorage);

		$template->registerHelper('printf', 'sprintf');
		$template->setTranslator($this->context->translator);
		
		// default parameters
		$template->mailNotificator = $this;
		$template->presenter = $presenter;
		$template->context = $this->context;
		$template->baseUri = $template->baseUrl = rtrim($this->context->httpRequest->getUrl()->getBaseUrl(), '/');
		$template->basePath = preg_replace('#https?://[^/]+#A', '', $template->baseUrl);
		$template->user = $this->context->user;
				
		return $template;
	}
	
	/**
	 * Descendant can override this method to customize template compile-time filters.
	 * @param  Nette\Templating\Template
	 * @return void
	 */
	public function templatePrepareFilters($template, &$engine = null) {
		if(!$engine) $engine = new Nette\Latte\Engine;

		$template->registerFilter($engine);
	}
	
	
}

