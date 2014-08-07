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
 *
 * vBuilder is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vBuilder. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Diagnostics;

use vBuilder\Application\WebFilesGenerator,
	Tracy\IBarPanel;

/**
 * Debug bar for displaying generated web files (CSS, JS, ...)
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 20, 2011
 */
class WebFilesBar implements IBarPanel {

	/** @var WebFilesGenerator */
	private $webFiles;

	public function __construct(WebFilesGenerator $webFiles) {
		$this->webFiles = $webFiles;
	}

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 */
	public function getTab() {
		ob_start();

		$webFiles = $this->webFiles;
		require __DIR__ . '/Templates/bar.webFiles.tab.phtml';

		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 */
	public function getPanel() {
		ob_start();

		$webFiles = $this->webFiles;
		require __DIR__ . '/Templates/bar.webFiles.panel.phtml';

		return ob_get_clean();
	}

}
