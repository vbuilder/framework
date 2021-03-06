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

namespace vBuilder\Application\UI\Controls;

use vBuilder,
		Nette,
		Nette\Application\UI\Form,
		Nette\Application\UI\Link;

/**
 * Control for generating HTML head tags from page meta data
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 7, 2011
 */
class HeadGenerator extends Nette\Application\UI\Control {

	public $onBeforeRender = array();

	public function render($params = array()) {
		$context = $this->getPresenter(true)->context;
		
		$this->onBeforeRender($context->metadata);

		$this->template->isInDevelopmentMode = !isset($context->parameters['productionMode']) || $context->parameters['productionMode'] !== true;
		$this->template->isUnderConstruction = isset($context->parameters['underConstruction']) && $context->parameters['underConstruction'] == true;
		$this->template->isInProductionMode = !$this->template->isInDevelopmentMode && !$this->template->isUnderConstruction;
		
		$this->template->metadata = $context->metadata;

		$og = $context->metadata->og;
		if(!$og->getUrl() && $og->getUrl() !== FALSE) $og->setUrl($this->getPresenter(true)->link("//this"));
		if(!$og->getTitle() && $og->getTitle() !== FALSE) $og->setTitle($context->metadata->getTitle());
		if(!$og->getSiteName() && $og->getSiteName() !== FALSE) $og->setSiteName($context->metadata->getTitleSuffix());
		if(!$og->getDescription() && $og->getDescription() !== FALSE) $og->setDescription($context->metadata->getDescription());

		$this->template->metadata->freeze();
		
		if(isset($params['gaAccount']))
			$this->template->gaAccount = $params['gaAccount'];
		
		echo $this->template;
	}

	protected function createTemplate($class = NULL) {
		$template = parent::createTemplate();
		$template->setFile(__DIR__ . '/Templates/default.latte');
		
		return $template;
	}
	 
}
