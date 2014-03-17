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

namespace vBuilder\Diagnostics;

use vBuilder,
	Nette;

/**
 * Debug bar for translations
 *
 * @author Adam Staněk (velbloud)
 * @since Mar 17, 2014
 */
class TranslationBar implements Nette\Diagnostics\IBarPanel {

	private $translator;
	private $queriedTranslations;
	private $ok = TRUE;

	function __construct(vBuilder\Localization\Translator $translator) {
		$this->translator = $translator;
	}

	function gather() {
		$this->queriedTranslations = $this->translator->getLogger()->getQueriedTranslations();
		$ok = &$this->ok;

		if(count($this->queriedTranslations) == 1)
			$ok = $this->queriedTranslations[0]['isTranslated'];

		uasort($this->queriedTranslations, function ($a, $b) use (&$ok) {
			if(!$a['isTranslated'] || !$b['isTranslated']) $ok = FALSE;

			return $a < $b;
		});
	}

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 */
	function getTab() {
		if(!$this->translator->getLogger()) return '';

		$this->gather();
		if(count($this->queriedTranslations) == 0) return '';

		ob_start();

		$translationOk = $this->ok;
		require __DIR__ . '/Templates/bar.translation.tab.phtml';

		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 */
	function getPanel() {
		ob_start();

		$translations = $this->queriedTranslations;
		$lang = $this->translator->getLang();
		require __DIR__ . '/Templates/bar.translation.panel.phtml';

		return ob_get_clean();
	}

}