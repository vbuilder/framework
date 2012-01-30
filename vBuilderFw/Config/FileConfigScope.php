<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vmanager.cz
 * 
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vManager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vManager. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Config;

use Nette;

/**
 * 
 *
 * @author Jirka Vebr
 */
class FileConfigScope extends ConfigScope {
	
	private $filenames = array();
	
	
	public function __construct(array $filenames, $fallback = null) {
		foreach ($filenames as $filename) {
			$ext = \pathinfo($filename, PATHINFO_EXTENSION);
			if (\is_file($filename) and \is_readable($filename) and in_array($ext, array('neon', 'ini'))) {
				$this->filenames[] = $filename;
			} else {
				throw new Nette\InvalidArgumentException("Invalid file '$filename'.");
			}
		}
		parent::__construct($fallback);
	}
	
	public function load() {
		$this->isLoaded = true;
		foreach($this->filenames as $file) {
			$decoded = $this->decode($file);
		
			if($decoded)
				$this->data = array_merge($this->data, $decoded);
		}
	}
	
	public function save() {
		throw new \LogicException('This doesn\'t make sense...');
	}
	
	private function decode($file) {
		$ext = \pathinfo($file, \PATHINFO_EXTENSION);
		
		switch ($ext) {
			case 'ini':
				$ini = \parse_ini_file('safe://'.$file, true, \INI_SCANNER_RAW);
				foreach ($ini as $key => $scope) {
					foreach ($scope as $key2 => $val) {
						$m = $this->parseBools($val);
						if (\is_numeric($m)) {
							$m = \intval($m);
						}
						$ini[$key][$key2] = $m;
					}
				}
				return $ini;
			case 'neon':
				return Nette\Utils\Neon::decode(file_get_contents('safe://'.$file));
			default:
				return;
		}
	}
}