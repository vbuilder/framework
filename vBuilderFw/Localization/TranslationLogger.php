<?php

/**
 * This file is part of vBuilder Framework (vBuilder FW).
 *
 * Copyright (c) 2012 V3Net.cz, s.r.o <info@v3net.cz>
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

namespace vBuilder\Localization;

use Nette,
	vBuilder;

/**
 * Logger of queried translations
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 17, 2014
 */
class TranslationLogger extends Nette\Object {

	private $queriedTranslations = array();

	/**
	 * @return array
	 */
	function getQueriedTranslations() {
		return array_values($this->queriedTranslations);
	}

	function translation() {
		$this->log(func_get_args(), TRUE);
	}

	function missingTranslation() {
		$this->log(func_get_args(), FALSE);
	}

	protected function log($args, $ok) {
		$messages = (array) $args[0];
		$id = md5(implode($messages, '.'));

		if(!isset($this->queriedTranslations[$id]))
			$this->queriedTranslations[$id] = array(
				'singular' => $messages[0],
				'plural' => isset($messages[1]) ? $messages[1] : NULL,
				'hints' => array(),
				'isTranslated' => $ok
			);

		$hint = NULL;

		if(isset($args[1]['__hint__']))
			$hint = $args[1]['__hint__'];

		if($hint !== NULL && !in_array($hint, $this->queriedTranslations[$id]['hints']))
			$this->queriedTranslations[$id]['hints'][] = $hint;
	}

}
