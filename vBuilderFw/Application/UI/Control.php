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

use Nette;

/**
 * Advanced base control for vBuilder based applications
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 7, 2011
 */
class Control extends Nette\Application\UI\Control {
	
	protected $view = 'default';
	private $actionHandled = false;
	private $renderCalled = false;
	
	/**
	 * @var array 
	 */
	protected $renderParams = array();
	
	/** 
	 * @var vBuilder\Application\UI\ControlRenderer renderer instance
	 * @internal
	 */
	private $_renderer;
	
	private $tplCreated = false;
	
	/**
	 * Renders control
	 */
	function render($params = array()) {		
		$this->renderCalled = true;
		$this->renderParams = $params;
		
		// Pokud jsme neprosli skrz ::signalRecieved - default action, etc.
		if(!$this->actionHandled)
			$this->tryCall($this->formatActionMethod($this->view), $this->params, $this);
		
		
		// render<View> on renderer instance
		$this->tryCall($this->formatRenderMethod($this->view), $this->params, $this->renderer);
		
		$this->renderer->render();
	}
	
	/**
	 * Return current context
	 * 
	 * @return Nette\DI\IContainer
	 */
	final public function getContext() {
			return $this->getPresenter(true)->context;
	}
	
	/**
	 * Returns current view
	 * 
	 * @return string 
	 */
	final public function getView() {
		return $this->view;
	}
	
	/**
	 * Returns ORM repository (shortcut)
	 * 
	 * @return vBuilder\Orm\Repository
	 */
	final public function getRepository() {
		return $this->context->repository;
	}
	
	// <editor-fold defaultstate="collapsed" desc="Template routines">
	
	/**
	 * @return Nette\Templating\ITemplate
	 */
	protected function createTemplate($class = NULL) {
		if(func_num_args() > 0 && $class != NULL) throw new \InvalidArgumentException(get_called_class() . "::createTemplate do not support $class argument. Take look at renderers instead.");
		
		$this->tplCreated = true;
		return $this->renderer->template;
	}
	
	/**
	 * Non sense because of renderers
	 * @param  Nette\Templating\Template
	 * @return void
	 */
	final public function templatePrepareFilters($template) {
		throw new \LogicException(get_called_class() . "::templatePrepareFilters should not be called. Use renderers instead.");
	}
	
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Renderer routines">
	
	final public function setRenderer(ControlRenderer $renderer) {
		if(isset($this->_renderer))
			throw new Nette\InvalidStateException("Current renderer (".get_class($this->_renderer).") has been already used. You cannot change it to ".get_class($renderer).".");
				
							
		$this->_renderer = $renderer;
	}
	
	final public function getRenderer() {
		if(isset($this->_renderer)) return $this->_renderer;
		
		$renderer = $this->createRenderer();
		if($renderer instanceof ControlRenderer) {
			$this->_renderer = $renderer;
			return $this->_renderer;
		} else 
			throw new \LogicException(get_called_class() . "::createRenderer() has to return child of vBuilder\Application\UI\ControlRenderer.");
	}
	
	protected function createRenderer() {
		return new ControlRenderer($this);
	}
	
	// </editor-fold>	
	
	// <editor-fold defaultstate="collapsed" desc="Signal handling">
	
	/**
	 * Calls signal handler method.
	 * 
	 * @param  string
	 * @return void
	 * @throws BadSignalException if there is not handler method
	 */
	public function signalReceived($signal) {		
		$this->actionHandled = true;
		
		if(!$this->tryCall($this->formatHandleMethod($signal), $this->params)) {
			$this->view = $signal;
			
			if(!$this->tryCall($this->formatActionMethod($signal), $this->params, $this)) {
				if(!$this->tryCall($this->formatRenderMethod($signal), $this->params, $this->renderer, true)) {
					$class = get_class($this);
					throw new Nette\Application\UI\BadSignalException("There is no handler for signal '$signal' in class $class.");
				}
			}	
			
		}
	}
	
	/**
	 * Formats action method (action<View>)
	 * 
	 * @param  string
	 * @return string
	 */
	public function formatActionMethod($signal) {
		return $signal == NULL ? NULL : 'action' . $signal; // intentionally ==
	}
	
	/**
	 * Formats render method (render<View>) - called on renderer
	 * 
	 * @param  string
	 * @return string
	 */
	public function formatRenderMethod($signal) {
		return $signal == NULL ? NULL : 'render' . $signal; // intentionally ==
	}
	
	/**
	 * Actual signal handle method (handle<Signal>)
	 * 
	 * @param  string
	 * @return string
	 */
	public function formatHandleMethod($signal) {
		return parent::formatSignalMethod($signal);
	}
	
	/**
	 * Hacked method formatSignalMethod to always point to existing method.
	 * It is important for correct link creation, but has the down side that
	 * Presenter::handleInvalidLink will not be called correctly and
	 * links will be created even for non existing signals.
	 * 
	 * @param  string
	 * @return string
	 */
	public function formatSignalMethod($signal)
	{
		return $signal == NULL ? NULL : 'signalHandler'; // intentionally ==
	}
	
	/**
	 * This function is for hacking only. It will never get called because of
	 * overloaded signalRecieved method.
	 * 
	 * Note: Be awere of parameter number (because of Presenter::argsToParams)
	 */
	final public function signalHandler($foo, $bar) {
		throw new \LogicException(get_called_class() . '::signalHandler() called!');
	}
	
	// </editor-fold>	
	
	// <editor-fold defaultstate="collapsed" desc="Helpers">
	
	/**
	 * Calls public method if exists.
	 * @param  string
	 * @param  array
	 * @return bool  does method exist?
	 */
	protected function tryCall($method, array $params, $class = null, $dryRun = false) {
		if (func_num_args() == 2)
			$class = $this;
		
		$rc = $class->getReflection();
		if ($rc->hasMethod($method)) {
			$rm = $rc->getMethod($method);
			if ($rm->isPublic() && !$rm->isAbstract() && !$rm->isStatic()) {
				$this->checkRequirements($rm);
				if(!$dryRun) $rm->invokeNamedArgs($class, $params);
				return TRUE;
			}
		}
		return FALSE;
	}
	
	// </editor-fold>	

	/**
	 * Redirect to another presenter, action or signal.
	 * @param  int      [optional] HTTP error code
	 * @param  string   destination in format "[[module:]presenter:]view" or "signal!"
	 * @param  array|mixed
	 * @return void
	 * @throws Nette\Application\AbortException
	 * @throws Nette\InvalidStateException
	 */
	public function redirect($code, $destination = NULL, $args = array()) {
		if(!$this->actionHandled && $this->renderCalled) {
			throw new Nette\InvalidStateException("You cannot use redirect when no signal is called. Use ".get_called_class()."::changeView() instead.");
		}
		
		if($code == 'this') $code = $this->view;
		elseif($destination == 'this') $destination = $this->view;
		
		parent::redirect($code, $destination, $args);
	} 
	
	/**
	 * Switches current control view
	 * 
	 * @param string view
	 * @throws Nette\InvalidStateException if template has been created 
	 */
	public function changeView($view) {
		if($this->tplCreated) {
			throw new Nette\InvalidStateException("You cannot call ".get_called_class()."::changeView after template has been created.");
		}
		
		$this->signalReceived($view);
	}
	
}

