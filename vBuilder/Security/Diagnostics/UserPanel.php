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

namespace vBuilder\Security\Diagnostics;

use vBuilder,
	Nette,
	Nette\Diagnostics\Helpers;

/**
 * This class simply overrides Nette\Security\Diagnostics\UserPanel
 * because of the class type hint (we need to use vBuilder\Security\User)
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class UserPanel extends Nette\Object implements Nette\Diagnostics\IBarPanel
{
	/** @var Nette\Security\User */
	private $user;

	private $tplDir;

	public function __construct($netteDir, vBuilder\Security\User $user)
	{
		$this->user = $user;
		$this->tplDir = $netteDir . '/Nette/Security/Diagnostics/templates';
	}


	/**
	 * Renders tab.
	 * @return string
	 */
	public function getTab()
	{
		ob_start();
		require $this->tplDir . '/UserPanel.tab.phtml';
		return ob_get_clean();
	}


	/**
	 * Renders panel.
	 * @return string
	 */
	public function getPanel()
	{
		ob_start();
		require $this->tplDir . '/UserPanel.panel.phtml';
		return ob_get_clean();
	}

}
