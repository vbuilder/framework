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

namespace vBuilder\Application\UI\Controls\DataTable;

use vBuilder,
	Nette,
	Nette\Utils\Html;

/**
 * Button representation for DataTable
 *
 * @author Adam Staněk (velbloud)
 * @since Jul 9, 2013
 */
class Button extends Component {

	private $_el;
	private $_url;

	public function setUrl($url) {
		$this->_url = $url;
		return $this;
	}

	public function setUrlCallback($callable) {
		if(!is_callable($callable))
			throw new Nette\InvalidArgumentException("Given URL callback is not callable");
	
		$this->_url = $callable;
		return $this;
	}

	public function getElement() {
		if(!isset($this->_el)) {
			$this->_el = Html::el('a', str_replace(" ", "\xc2\xa0", $this->getLabel()));
			$this->_el->class($this->_table->getUniqueId() . "-btn" . ucfirst($this->getName()));
		}

		return $this->_el;
	}

	public function render($rowData) {
		if($this->_renderer) {
			$r = $this->_renderer;
			return $r($this);
		}

		// Direct URL
		if($this->_url !== NULL) {

			// URL callback
			if(is_callable($this->_url)) {
				$foo = $this->_url;
				$url = $foo($rowData);
			}

			// URL string
			else {
				$url = new Nette\Http\Url($this->_url);
				foreach($this->_table->getIdColumns() as $key)
					$url->appendQuery(array('record' . ucfirst($key) => isset($rowData->{$key}) ? $rowData->{$key} : NULL));
			}

			$this->element->href((string) $url);

		// Standard action's URL
		} else
			$this->element->href($this->_table->createActionLink($this->getName(), $rowData));

		// Class
		if(($class = $this->getClass($rowData)) !== NULL) {
			$this->element->class .= " $class";
		}

		return (string) $this->element;
	}

}
