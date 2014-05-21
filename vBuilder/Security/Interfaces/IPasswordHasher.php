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

/**
 * Interface for support of multiple hashing algorithms
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 10, 2011
 */
interface IPasswordHasher {
    
	/**
	 * Returns hashed password.
	 * 
	 * Warning: The password can be salted (depending on implementation).
	 * Don't use this for checking on strings. Use checkPassword instead.
	 * 
	 * @param string plain text password
	 * @return string password hash
	 */
	public function hashPassword($password);

	/**
	 * Checks password against it's hash.
	 *
	 * @param string test password in plain text
	 * @param string hash of password to check against
	 *
	 * @return boolean true if passwords match
	 */
	public function checkPassword($password, $hash);

}
