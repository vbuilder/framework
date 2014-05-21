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

namespace vBuilder\Application;

use vBuilder,
	vBuilder\Utils\Strings,
	Nette;

/**
 * Provider of open graph data
 *
 * @see http://opengraphprotocol.org/
 * @see http://developers.facebook.com/tools/debug
 * @see https://developers.facebook.com/docs/opengraph/
 * 
 * @see http://www.joshspeters.com/how-to-optimize-the-ogdescription-tag-for-search-and-social
 * 
 * @author Adam Staněk (velbloud)
 * @since May 22, 2013
 */
class OpenGraphDataProvider extends Nette\FreezableObject {

	protected $_url;
	protected $_type;
	protected $_title;
	protected $_siteName;
	protected $_description;
	protected $_images = array();

	public function getUrl() {
		return $this->_url;
	}

	public function setUrl($url) {
		$this->_url = $url;
		return $this;
	}

	public function getType() {
		return $this->_type;
	}

	public function setType($type) {
		$this->_type = $type;
		return $this;
	}

	public function getTitle() {
		return $this->_title;
	}

	public function setTitle($title) {
		$this->_title = $title;
		return $this;
	}

	public function getSiteName() {
		return $this->_siteName;
	}

	public function setSiteName($siteName) {
		$this->_siteName = $siteName;
		return $this;
	}

	public function getDescription() {
		return $this->_description;
	}

	public function setDescription($description) {
		$this->_description = $description;
		return $this;
	}

	public function getImage() { return $this->getImages(); }
	public function getImages() {
		return $this->_images;
	}

	public function setImage($imageUrl) { return $this->setImages($imageUrl); }
	public function setImages($imageUrl) {
		$urls = !is_array($imageUrl) ? array($imageUrl) : $imageUrl;

		$this->_images = array();
		foreach($urls as $url) $this->addImage($url);
	}

	public function addImage($imageUrl) {
		$this->_images[] = $imageUrl;
		return $this;
	}

}
