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

use vBuilder\Orm\SessionRepository,
	Tracy\IBarPanel;

/**
 * Debug bar for displaying current session tables of ORM
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 2, 2012
 */
class OrmSessionBar implements IBarPanel {

	/** @var SessionRepository */
	private $repository;


	public function __construct(SessionRepository $repository) {
		$this->repository = $repository;
	}

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 */
	public function getTab() {
		if(!$this->getContext()->session->isStarted()) return ;

		ob_start();

		require __DIR__ . '/Templates/bar.orm.session.tab.phtml';

		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 */
	public function getPanel() {
		if(!$this->getContext()->session->isStarted()) return;

		ob_start();

		$session = $this->repository->getSession();
		require __DIR__ . '/Templates/bar.orm.session.panel.phtml';

		return ob_get_clean();
	}

}
