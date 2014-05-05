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

namespace vBuilder\Security;

use Nette;

/**
 * Interface for supporting many hashing algorithms for storing passwords
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
interface IAuthenticator extends Nette\Security\IAuthenticator {
    
     const ID = 3;
     const EMAIL = 4;

    /**
     * Returns name of authentication method for this handler.
     * {@link User::AUTHN_METHOD_INVALID} 
     *
     * @return string
     */
	public function getMethod();

	/**
     * Returns name of authentication source for this handler.
     * {@link vBuilder\Security\User::AUTHN_SOURCE_ALL} 
     *
     * @return string
     */
	public function getSource();

}
