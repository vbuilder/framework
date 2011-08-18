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

namespace vBuilder\Utils;

/**
 * CLI argument parser
 * 
 * Parses arguments in format:
 * php script.php <switches> <options> <arguments>
 * 
 * Example:
 *		-p -l --ahoj 123 -a valA soubor1 soubor2
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 18, 2011
 */
class CliArgsParser {
	
	protected $args;
	protected $parsedArguments;
	protected $parsedOptions;
	
	protected $switches;
	protected $options;
	
	protected $argHelp;
	protected $min;
	protected $max;
	
	protected $errorMsg = false;
	
	/**
	 * Constructor
	 * 
	 * @param array|null array of arguments, if null arguments of current script are taken
	 */
	public function __construct($args = null) {
		$this->args = $args === null ? array_slice($_SERVER['argv'], 1) : $args;
	}
	
	/**
	 * Registers switch
	 * 
	 * @param string name
	 * @param string help text
	 * 
	 * @return CliArgsParser fluent
	 */
	public function addSwitch($name, $helpText = '') {
		$this->switches[$name] = $helpText;
		
		return $this;
	}
	
	/**
	 * Registers option for parser
	 * 
	 * @param string name of option
	 * @param string option label for option value
	 * @param string option help message
	 * @param string|null if less than 4 arguments given, option is registered as required
	 * 
	 * @return CliArgsParser fluent
	 */
	public function addOption($name, $helpLabel = '', $helpText = '', $defaultValue = null) {
		$this->options[$name] = array(
			 'label' => $helpLabel,
			 'help' => $helpText,
			 'default' => $defaultValue,
			 'required' => func_num_args() < 4
		);
		
		return $this;
	}
	
	/**
	 * Sets number of required arguments
	 * 
	 * @param int|null minimum number of arguments
	 * @param int|null maximum number of arguments
	 * @return CliArgsParser fluent
	 */
	public function setNumRequiredArgs($min, $max = null) {
		$this->min = $min;
		$this->max = $max;
		
		return $this;
	}
	
	/**
	 * Sets help label for arguments
	 * 
	 * @param string label
	 * @return CliArgsParser fluent
	 */
	public function setArgumentHelp($name) {
		$this->argHelp = $name;
		return $this;
	}
	
	/**
	 * Returns option/switch value
	 * 
	 * @param string name
	 * @return string|false
	 */
	public function get($name) {
		if(!isset($this->parsedArguments)) throw new Nette\InvalidStateException("Arguments has not been parsed yet");
		
		return isset($this->parsedOptions[$name]) ? $this->parsedOptions[$name] : false;
	}
	
	/**
	 * Returns array of arguments
	 * 
	 * @return array
	 */
	public function getArguments() {
		if(!isset($this->parsedArguments)) throw new Nette\InvalidStateException("Arguments has not been parsed yet");
		
		return $this->parsedArguments;
	}
	
	/**
	 * Parse arguments
	 * 
	 * @return bool true on success 
	 */
	public function parse() {
		if(isset($this->parsedArguments)) throw new Nette\InvalidStateException("Arguments has been already parsed");
		$this->parsedArguments = array();
		$this->parsedOptions = array();
		
		$needValueFor = null;
		foreach($this->args as $arg) {
			if(count($this->parsedArguments) == 0 && preg_match('#^(--|[/-])([a-z]+(-[a-z]+)*)$#i', $arg, $matches)) {
				$keys = $matches[1] == '-' ? preg_split('//', $matches[2], -1, PREG_SPLIT_NO_EMPTY) : array($matches[2]);
				foreach($keys as $key) {
					
					if(isset($this->switches[$key])) {
						$this->parsedOptions[$key] = true;
					} elseif(isset($this->options[$key])) {
						$needValueFor = $key;
					} else {
						$this->errorMsg = "Undefined option '$key'";
						return false;
					}
					
				}
			} else {
				if($needValueFor) {
					$this->parsedOptions[$needValueFor] = $arg;
					$needValueFor = null;
				} else {
					$this->parsedArguments[] = $arg;
				}
				
			}
		}
		
		if($needValueFor !== null) {
			$this->errorMsg = "Missing value for option '$needValueFor'";
			return false;
		}
		
		if($this->max !== null && count($this->parsedArguments) > $this->max) {
			$this->errorMsg = "Too many argumenrs";
			return false;
		}
		
		if($this->min !== null && count($this->parsedArguments) < $this->min) {
			$this->errorMsg = "Too few argumenrs";
			return false;
		}
		
		foreach($this->options as $key => $option) {
			if($option['required'] && !isset($this->parsedOptions[$key])) {
				$this->errorMsg = "Missing required option '$key'";
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Returns error message or false if there isn't any
	 * 
	 * @return string|false
	 */
	public function getErrorMsg() {
		if(!isset($this->parsedArguments)) throw new Nette\InvalidStateException("Arguments has not been parsed yet");
		
		return $this->errorMsg;
	}
	
	/**
	 * Prints usage help to standard output
	 * 
	 * @return CliArgsParser fluent
	 */
	public function printUsage() {
		echo "Usage:\n";
		echo "\t" . $_SERVER['SCRIPT_NAME'];
		
		if(count($this->switches)) echo " [switches]";
		if(count($this->options)) echo " [options]";
		
		if(isset($this->min) || isset($this->max)) {
			if($this->argHelp != '')
				echo " [".$this->argHelp."]";
			else
				echo " [argument]";
		}

		echo "\n";
		
		if(count($this->switches)) {
			echo "\nSwitches:\n";
			foreach($this->switches as $curr => $help) {
				echo "\t";
				
				if(strlen($curr) > 1) echo "--";
				else echo "-";
				
				echo "$curr";
				
				if($help != '') echo "\t$help";
				
				echo "\n";
			}
		}		
		
		if(count($this->options)) {
			echo "\nOptions:\n";
			foreach($this->options as $curr => $option) {
				echo "\t";
				
				if(strlen($curr) > 1) echo "--";
				else echo "-";
				
				echo "$curr";
				
				if($option['label'] != '') echo " <".$option['label'].">";
				else echo " <value>";
				
				echo "\t" . $option['help'];
				
				if($option['required']) echo " (REQUIRED)";
				elseif($option['default'] != '') echo " (Default: ".$option['default'].")";
				
				echo "\n";
			}
		}
		
		return $this;
	}
	
}
