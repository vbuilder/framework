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

namespace vBuilder\Latte\Macros;

use vBuilder,
	Latte\CompileException,
	Latte\Macros\MacroSet,
	Latte\Compiler,
	Latte\MacroNode,
	Latte\ParseException,
	Latte\MacroTokens,
	Latte\Tokenizer;

/**
 * System macros
 *
 * @author Adam Staněk (velbloud)
 * @since Aug 2, 2011
 */
class SystemMacros extends MacroSet {

	protected $_prolog = array();

	/**
	 * Installs redactions macros to parser
	 *
	 * @param Compiler $compiler
	 * @return SystemMacros
	 */
	static function install(Compiler $compiler) {
		$me = new static($compiler);

		$me->addMacro('addCss', array($me, 'macroWebFile'));
		$me->addMacro('addJs', array($me, 'macroWebFile'));

		$me->addMacro('meta', array($me, 'macroMeta'));

		return $me;
	}

	/**
	 * Initializes before template parsing.
	 * @return void
	 */
	public function initialize() {
		$this->_prolog[$this->getCompiler()->getTemplateId()] = array();
	}

	/**
	 * Finishes template parsing.
	 * @return array(prolog, epilog)
	 */
	function finalize() {
		return array(implode($this->_prolog[$this->getCompiler()->getTemplateId()], "\n"), '');
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
		$cmd = '$_control->getPresenter()->getContext()->webFilesGenerator->addFile('
			. '$_control->getPresenter()->getContext()->expand('
			. $node->args . '), '
			. "'$lang', "
			. 'array(dirname($template->getName()), $_control->getPresenter()->getContext()->parameters[\'wwwDir\'])'
			. ');';


		// ---------

		// Pokud se macro vyskytuje bez nejakeho parent bloku musime ho zapsat v prologu,
		// protoze jinak by se nemuselo vubec zavolat kvuli dedicnosti sablon.
		// Naopak ve snippetModu to nedelame, abychom zbytecne neposilali soubory z layoutu
		// a ostatnich bloku, ktere nebyly vykresleny.
		if($node->parentNode == NULL) {
			$cmd = 'if(!$_control->snippetMode) ' . $cmd;
			$this->_prolog[$this->getCompiler()->getTemplateId()][] = $cmd;
		} else
			return $writer->write($cmd);
	}

	/**
	 * {meta ...}
	 *
	 * Examples:
	 * {meta title 'Something'}
	 * {meta !title 'Something'}
	 * {meta og:type 'article'}
	 * {meta og:image[] 'http://www.myweb.tld/images/image.png'}
	 *
	 * @param MacroNode $node
	 * @param type $writer
	 * @return string
	 */
	function macroMeta(MacroNode $node, $writer) {
		return $writer->write('');

		$key = '';
		$value = '';

		// Operator (= or [])
		$operator = '=';

		// True if key starts with ! (forcing ovewrite of existing value)
		$force = false;

		// 0: No symbol yet
		// 1: Symbol found, wating for whitespace
		// 2: Whitespace found -> everything else is a value
		$state = 0;

		$prev = NULL;
		foreach($node->tokenizer->tokens as $token) {

			// Getting value (don't care about anything)
			if($state > 1) {
				$value .= $token[Tokenizer::VALUE];
			}

			// Special characters (operators, etc...)
			elseif($token[Tokenizer::TYPE] == MacroTokens::T_CHAR) {

				// : in a symbol
				if($token[Tokenizer::VALUE] == ':' && $state > 0) {
					$key .= $token[Tokenizer::VALUE];
				}

				elseif($token[Tokenizer::VALUE] == '[' && $state > 0) {

				}

				elseif($token[Tokenizer::VALUE] == ']' && $state > 0 && $prev[Tokenizer::VALUE] == '[') {
					$operator = '[]';
				}

				// ! before any symbol
				elseif($token[Tokenizer::VALUE] == '!' && $state == 0) {
					$force = true;

				} else
					throw new CompileException("Invalid meta declaration: " . $node->name . " " . $node->args . $node->modifiers);

			}

			// Checking for the first symbol
			elseif($token[Tokenizer::TYPE] == MacroTokens::T_SYMBOL) {

				$key .= $token[Tokenizer::VALUE];

				// We have found first symbol
				if($state == 0) $state++;
			}

			// Checking for whitespace after symbol
			if($token[Tokenizer::TYPE] == MacroTokens::T_WHITESPACE && $state > 0) {
				$state++;
			}

			$prev = $token;
		}


		// Building method name
		$keyTokens = explode(':', $key);
		$setMethod = ($operator == '=' ? 'set' : 'add') . ucfirst($keyTokens[count($keyTokens) - 1]);
		$getMethod = 'get' . ucfirst($keyTokens[count($keyTokens) - 1]);
		for($i = 0; $i < count($keyTokens) - 1; $i++) {
			$setMethod = $keyTokens[$i] . '->' . $setMethod;
			$getMethod = $keyTokens[$i] . '->' . $getMethod;
		}

		$setMethod = '$_control->getPresenter()->getContext()->metadata->' . $setMethod;
		$getMethod = '$_control->getPresenter()->getContext()->metadata->' . $getMethod;

		$cmd = $setMethod . '(' . $value . ');';

		if(!$force && $operator == '=')
			$cmd = "{ if(!$getMethod() && $getMethod() !== FALSE) $cmd }";

		// ---------

		// Pokud se macro vyskytuje bez nejakeho parent bloku musime ho zapsat v prologu,
		// protoze jinak by se nemuselo vubec zavolat kvuli dedicnosti sablon
		if($node->parentNode == NULL) {
			$this->_prolog[$this->getCompiler()->getTemplateId()][] = $cmd;
		} else
			return $writer->write($cmd);
	}

}
