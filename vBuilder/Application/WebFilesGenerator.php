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
namespace vBuilder\Application;

use vBuilder,
	Nette,
	Nette\Utils\Strings;

/**
 * Helper model for generation of CSS and JS web files
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 15, 2011
 */
class WebFilesGenerator extends Nette\Object {
	
	const STYLESHEET = 'css';
	const JAVASCRIPT = 'js';
	
	private $lastModificationTime = array();
	private $files = array();
	private $generated = array();
	private $output = array();
	private $hasBeenGenerated = array();
	private $hashes = array();
	
	/** @var Nette\DI\Container DI */
	private $context;
	
	/**
	 * Constructor
	 * 
	 * @param Nette\DI\Container $context 
	 */
	function __construct(Nette\DI\Container $context) {
		$this->context = $context;
		
		Nette\Diagnostics\Debugger::addPanel(new vBuilder\Diagnostics\WebFilesBar);
	}
	
	/**
	 * Registers local file to output for specified type
	 * 
	 * @param string filepath
	 * @param string file type
	 */
	public function registerOutput($outfile, $type) {	
		if(isset($this->output[$type]))
			throw new Nette\InvalidStateException ("Web file of type '$type' has been already registered.");
		
		$this->output[$type] = $outfile;
	}
	
	/**
	 * Returns registred files
	 * 
	 * @param string file type
	 * @return array of files
	 */
	public function getFiles($type) {
		return isset($this->files[$type]) ? $this->files[$type] : array();
	}
	
	/**
	 * Returns true if file has just been generated (during this page view)
	 * 
	 * @param string gile type 
	 * @return bool
	 */
	public function hasBeenGenerated($type) {
		return isset($this->hasBeenGenerated[$type]);
	}
	
	/**
	 * Returns hash of files with given type
	 *
	 * @return string
	 */
	public function getHash($type) {
		if(!isset($this->hashes[$type])) {
			
			$files = $this->getFiles($type);
			$this->hashes[$type] = count($files) > 0
					? md5(implode(array_keys($files), ','))
					: null;
		}
		
		return $this->hashes[$type];
	}
	
	/**
	 * Returns last modificaiton unix timestamp
	 *
	 * @return int
	 */
	public function getLastModification($type) {
		return isset($this->lastModificationTime[$type]) ? $this->lastModificationTime[$type] : 0;
	}
	
	/**
	 * Adds file to stack for composition
	 * 
	 * @param string|BaseFile|array file(s) to stack
	 * @param string file type
	 * @param array of string used only for files with relative file path
	 */
	public function addFile($file, $type, array $basePath = array()) {		
		if(isset($this->generated[$type]))
			throw new Nette\InvalidStateException("Web file of type '$type' has been already generated. Cannot add another file to stack.");
		
		$files = is_array($file) ? $file : array($file);
		foreach($files as $file) {

			// Soubory prilozene ke strance (Redakce: vBuilder\Redaction\DocumentFiles)
			if($file instanceOf vBuilder\Redaction\Files\BaseFile) {
				$lastMod = $file->getLastModificationTime()->format('U');
				$id = $file->getUniqId();
			}

			// Obycejne soubory
			else {
				if(Strings::startsWith($file, '/'))
					$path = $file;
				else {
					foreach($basePath as $prefix) {
						$path = Strings::endsWith($prefix, '/') ? $prefix : $prefix . '/';
						$path .= $file;
						
						if(file_exists($path)) break;
					}
				}

				if(!isset($path) || !file_exists($path)) 
					throw new Nette\InvalidArgumentException("File '$file' does not exist (Search paths: '" . implode($basePath, "', '") . "')");

				$lastMod = filemtime($path);
				$id = $path;
				$file = $path;
			}

			// Ulozim cas posledni zmeny		
			if(!isset($this->lastModificationTime[$type]) || $this->lastModificationTime[$type] < $lastMod)
				$this->lastModificationTime[$type] = $lastMod;

			$this->files[$type][$id] = $file;
		}
		
		$this->hashes[$type] = null;
	}
	
	/**
	 * Returns true, if file has been already generated.
	 * 
	 * @param string file type
	 * @return bool|null
	 */
	public function isCached($type) {
		if(!isset($this->output[$type])) throw new Nette\InvalidStateException ("Output for web file of type '$type' is not registred. Forgot to call ".get_called_class()."::registerOutput?");		
		
		$filePath = $this->output[$type];
		
		if($filePath === null || !isset($this->lastModificationTime[$type])) return null;
		if(!file_exists($filePath)) return false;
		
		return $this->lastModificationTime[$type] < filemtime($filePath);		
	}
	
	/**
	 * Generates files
	 */
	public function generate($type, $return = false) {		
		if(!isset($this->output[$type])) throw new Nette\InvalidStateException ("Output for web file of type '$type' is not registred. Forgot to call ".get_called_class()."::registerOutput?");		
		if(isset($this->generated[$type])) throw new Nette\InvalidStateException("Web file of type '$type' has been already generated");
		$this->generated[$type] = true;

		if(!isset($this->files[$type])) return ;
		
		if(!$return) {

			$filePath = $this->output[$type];
			$dirPath = pathinfo($filePath, PATHINFO_DIRNAME);
			if($filePath === null) return ;
			
			$this->hasBeenGenerated[$type] = true;
			
			if(!is_dir($dirPath))
				if(@mkdir($dirPath, 0777, true) === false) // @ - is escalated to exception
					throw new Nette\IOException("Cannot create directory '".$dirPath."'");
			

			$fp = fopen('safe://' . $filePath, 'w');
			if($fp === false) throw new Nette\IOException("Cannot write file '$filePath'");
		} else
			$result = "";

		foreach($this->files[$type] as $id => $file) {
			$head = "/* " . str_pad('  ' . $id . '  ', 74, "#", STR_PAD_BOTH) . " */\n";
			$content = $file instanceof vBuilder\Redaction\Files\BaseFile
				? $file->getContent()."\n"
				: file_get_contents($file)."\n";

			if(!$return) {
				fwrite($fp, $head);
				fwrite($fp, $content);
			} else 
				$result .= $head . $content;
		}

		if(!$return)
			fclose($fp);		
		else
			return $result;
		
	}
	
}
