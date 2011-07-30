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

namespace vBuilder\Test;

/**
 * Blue screen for vBuilder tests
 *
 * @author Adam Staněk (velbloud)
 * @since Jul 30, 2011
 */
class BlueScreen extends TestScreen {

	protected $fileBrowser;
	protected $message;
	protected $assert;
	
	function render() {
		include __DIR__ . "/templates/bluescreen.phtml";
	}
	
	function setAssertVariables($expected, &$actual) {
		$this->assert = array(
			 'expected' => $expected,
			 'actual' => &$actual
		);
		
		return $this;
	}
	
	function & getAssertExpected() {
		if(isset($this->assert['expected'])) return $this->assert['expected'];
		
		$var = null;
		return $var;
	}
	
	function & getAssertActual() {
		if(isset($this->assert['actual'])) return $this->assert['actual'];
		
		$var = null;
		return $var;
	}
	
	function setFileBrowser($file, $line) {
		$this->fileBrowser = array(
			 'file' => $file,
			 'line' => $line
		);
		
		return $this;
	}
	
	function getFileBrowserFile() {
		return isset($this->fileBrowser['file']) ? $this->fileBrowser['file'] : null;
	}
	
	function getFileBrowserLine() {
		return isset($this->fileBrowser['line']) ? $this->fileBrowser['line'] : null;
	}
	
	function setMessage($msg) {
		$this->message = $msg;
		return $this;
	}
	
	function getMessage() {
		return $this->message;
	}
	
}
