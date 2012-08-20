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

namespace vBuilder\Application\UI\Controls;

use vBuilder,
	vBuilder\Application\WebFilesGenerator,
	Nette,
	Nette\Utils\Html;

/**
 * Control for generating HTML tags for JS / CSS inclusion
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 13, 2011
 */
class WebFilesHeadGenerator extends Nette\Application\UI\Control {
	
	/** @var string rendered hash */
	private $_renderedHash = array();
	
	/** @var string rendered HTML of created HEAD tags */
	private $_renderedHtml;

	/**
	  * Returns DI context container
	  *
	  * @return Nette\DI\IContainer
	  */
	public function getContext() {
		return $this->getPresenter(true)->context;
	}
	
	/** 
	 * Returns base URL
	 *
	 * @return string
	 */
	private function getBaseUrl() {
		return rtrim($this->context->httpRequest->getUrl()->getBaseUrl(), '/');
	}
	
	/**
	 * Renders HTML and returns it as a string
	 *
	 * @return string
	 */
	private function renderToString() {
		$webFiles = $this->context->webFilesGenerator;
		$output = '';

		// CSS ---------
		$this->_renderedHash[WebFilesGenerator::STYLESHEET] = $webFiles->getHash(WebFilesGenerator::STYLESHEET);
		if($this->_renderedHash[WebFilesGenerator::STYLESHEET] !== null) {
			$lastModSuffix = $webFiles->getLastModification(WebFilesGenerator::STYLESHEET) !== null ? '?ver=' . $webFiles->getLastModification(WebFilesGenerator::STYLESHEET) : '';
			
			$output .= (string) Html::el('link', array(
				'rel' => 'stylesheet',
				'href' => $this->getBaseUrl() . '/css/' . $this->_renderedHash[WebFilesGenerator::STYLESHEET] . '.css' . $lastModSuffix,
				'type' => 'text/css',
				'media' => 'all',
			));
		}
		
		// JS -----------
		$this->_renderedHash[WebFilesGenerator::JAVASCRIPT] = $webFiles->getHash(WebFilesGenerator::JAVASCRIPT);
		if($this->_renderedHash[WebFilesGenerator::JAVASCRIPT] !== null) {
			$lastModSuffix = $webFiles->getLastModification(WebFilesGenerator::JAVASCRIPT) !== null ? '?ver=' . $webFiles->getLastModification(WebFilesGenerator::JAVASCRIPT) : '';
		
			$scriptEl = Html::el('script', array(
				'type' => 'text/javascript',
				'src' => $this->getBaseUrl() . '/js/' . $this->_renderedHash[WebFilesGenerator::JAVASCRIPT] . '.js' . $lastModSuffix
			));
			
			$output .= "\n" . $scriptEl->startTag() . $scriptEl->endTag();
		}
		
		return $output;
	}

	/**
	 * Renders HTML tags to standard output
	 */
	public function render() {
		$this->_renderedHtml = $this->renderToString();

		echo $this->_renderedHtml;
	}
	
	/**
	 * Fixes rendered data to contain correct data hashes
	 * which may have changed during late render addCss / addJs
	 *
	 * @param &string rendered data
	 */
	public function lateRenderFix(&$renderedHtml) {
		if(!isset($this->_renderedHtml)) return ;
	
		$webFiles = $this->context->webFilesGenerator;
		
		if($this->_renderedHash[WebFilesGenerator::JAVASCRIPT] !== $webFiles->getHash(WebFilesGenerator::JAVASCRIPT) || $this->_renderedHash[WebFilesGenerator::STYLESHEET] !== $webFiles->getHash(WebFilesGenerator::STYLESHEET)) {
		
			$renderedHtml = str_replace($this->_renderedHtml, $this->renderToString(), $renderedHtml);
		}
	}
	 
}
