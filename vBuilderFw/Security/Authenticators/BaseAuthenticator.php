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

namespace vBuilder\Security\Authenticators;

use vBuilder,
	vBuilder\Security\IIdentityFactory,
	vBuilder\Security\IAuthenticator,
	Nette;

/**
 * Base authentication handler
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
abstract class BaseAuthenticator extends Nette\Object implements IAuthenticator {

	/** Exception error code */
	const MAXIMUM_ATTEMPTS_EXCEEDED = 5;

	/** @var IIdentityFactory */
	protected $identityFactory;

	public function __construct(IIdentityFactory $identityFactory) {
		$this->identityFactory = $identityFactory;
	}

	/**
	 * Returns IIdentity factory.
	 *
	 * @return IIdentityFactory
	 */
	public function getIdentityFactory() {
		return $this->identityFactory;
	}

}