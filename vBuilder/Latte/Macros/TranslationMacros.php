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
	Latte\Macros\MacroSet,
	Latte\Compiler,
	Latte\MacroNode,
	Latte\PhpWriter,
	Latte\CompileException,
	Latte\MacroTokens;

/**
 * Translation macros (overrides Latte\Macros\CoreMacros)
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 17, 2014
 */
class TranslationMacros extends MacroSet {

	/**
	 * Installs macros
	 *
	 * @param Compiler $compiler
	 * @return TranslationMacros
	 */
	static function install(Compiler $compiler) {
		$me = new static($compiler);

		$me->addMacro('_', array($me, 'macroTranslate'), array($me, 'macroTranslate'));

		return $me;
	}

	/**
	 * {_'something'}
	 * {_'something: %foo', foo => 'bar'}
	 * {_ foo => 'bar'}something %foo{/_}
	 * {_ n => 55}%n message{_}%n messages{/_}
	 */
	public function macroTranslate(MacroNode $node, PhpWriter $writer) {

		$tokens = $writer->preprocess();

		$msgTokens = new MacroTokens;
		$argTokens = NULL;
		$currTokens = $msgTokens;

		while ($tokens->nextToken()) {
			if($tokens->depth === 0) {
				if($tokens->isCurrent('=>') && $argTokens === NULL) {
					$argTokens = $msgTokens;
					$msgTokens = NULL;

				} elseif($tokens->isCurrent(',') && $argTokens === NULL) {
					$argTokens = new MacroTokens;
					$currTokens = $argTokens;
					continue;
				}

			}

			$currTokens->append($tokens->currentToken());
		}

		if($node->parentNode && $node->parentNode->name == '_') {
			if(isset($node->parentNode->data->plural))
				throw new CompileException("Invalid plural form of {_} macro");

			$node->isEmpty = true;
			$node->parentNode->data->plural = true;
			return 'ob_start()';
		}

		$msg = $msgTokens !== NULL ? trim($writer->quoteFilter($msgTokens)->joinAll()) : NULL;
		if($msg == "") $msg = NULL;
		$args = 'array(' . ($argTokens !== NULL ? trim($writer->quoteFilter($argTokens)->joinAll()) . ', ' : '') . '\'__hint__\' => $template->getName() . ":' . $this->getCompiler()->getLine() . '")';

		if ($node->closing) {
			if(isset($node->data->plural))
				return $writer->write('echo %modify($template->translate(array_reverse(array(ob_get_clean(), ob_get_clean())), '. $args .'))');
			else
				return $writer->write('echo %modify($template->translate(ob_get_clean(), '. $args .'))');

		} elseif($msg !== NULL) {
			$node->isEmpty = true;
			return $writer->write('echo %modify($template->translate('. $msg .', '. $args .'))');

		} else
			return 'ob_start()';
	}

}