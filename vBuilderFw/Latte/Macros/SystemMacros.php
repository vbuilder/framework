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

namespace vBuilder\Latte\Macros;

use vBuilder,
	Nette,
	Nette\Latte\MacroNode,
	Nette\Latte\ParseException,
	Nette\Latte\MacroTokenizer;

/**
 * System macros
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 2, 2011
 */
class SystemMacros extends Nette\Latte\Macros\MacroSet {

	protected $_prolog = array();

	/**
	 * Installs redactions macros to parser
	 * 
	 * @param Nette\Latte\Parser $parser
	 * @return RedactionMacros
	 */
	static function install(Nette\Latte\Compiler $compiler) {
		$me = new static($compiler);

		$me->addMacro('addCss', array($me, 'macroWebFile'));
		$me->addMacro('addJs', array($me, 'macroWebFile'));
		
		$me->addMacro('meta', array($me, 'macroMeta'));
		$me->addMacro('iftest', array($me, 'macroTest'), array($me, 'macroEndTest'));
		$me->addMacro('ifTest', array($me, 'macroTest'), array($me, 'macroEndTest'));
		
		return $me;
	}

	function finalize() {
		// ($prolog, $epilog)
		return array(implode($this->_prolog, "\n"), '');
	}
	
	/**
	 * {addCss ...}
	 * {addJs ...}
	 * 
	 * @param MacroNode $node
	 * @param type $writer
	 * @return string 
	 */
	function macroWebFile(MacroNode $node, $writer) {

		$lang = lcfirst(mb_substr($node->name, 3));
		$cmd = '$context->webFilesGenerator->addFile('
			. $node->args . ', '
			. "'$lang', "
			. 'array(dirname($template->getFile()), WWW_DIR)'
			. ');';

		
		// ---------

		// Pokud se macro vyskytuje bez nejakeho parent bloku musime ho zapsat v prologu,
		// protoze jinak by se nemuselo vubec zavolat kvuli dedicnosti sablon
		if($node->parentNode == NULL) {
			$this->_prolog[] = $cmd;
		} else 
			$writer->write($cmd);
	}

	/**
	 * {meta ...}
	 * 
	 * @param MacroNode $node
	 * @param type $writer
	 * @return string 
	 */
	function macroMeta(MacroNode $node, $writer) {
		// TODO: podpora pro veci jako addKeywords

		$cmd = '';
		$force = false;
		$symbol = false;

		foreach($node->tokenizer->tokens as $token) {
			if($symbol)
				$cmd .= $token['value'];
			elseif($token['value'] == '!')
				$force = true;
			elseif($token['type'] == MacroTokenizer::T_SYMBOL) {
				if(!$force)
					$cmd .= 'if(!$context->metadata->{"' . $token['value'] . '"} && $context->metadata->{"' . $token['value'] . '"} !== false) ';

				$cmd .= '$context->metadata->{"' . $token['value'] . '"} =';

				$symbol = true;
			}
		}

		$cmd .= ';';
		if(!$force) $cmd = "{ $cmd }";
				
		// ---------

		// Pokud se macro vyskytuje bez nejakeho parent bloku musime ho zapsat v prologu,
		// protoze jinak by se nemuselo vubec zavolat kvuli dedicnosti sablon
		if($node->parentNode == NULL) {
			$this->_prolog[] = $cmd;
		} else 
			$writer->write($cmd);
	}

	function macroTest(MacroNode $node, $writer) {
		// isset($context->parameters['productionMode']) && $context->parameters['productionMode'] === false

		return $writer->write('{ if(Nette\Diagnostics\Debugger::$productionMode === false):');
	}

	function macroEndTest(MacroNode $node, $writer) {
		return $writer->write('endif; }');
	}

}