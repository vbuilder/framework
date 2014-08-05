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

namespace vBuilder\RestApi\OAuth2;

use Nette\Object;

/**
 * Authorization token container
 *
 * holds information about the authorization token
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 23, 2014
 */
class Token extends Object {

	private $token;
	private $refreshToken;
	private $ttl;
	private $parameters;

	public function __construct($token, $ttl, $refreshToken = NULL, $parameters = NULL) {
		$this->token = $token;
		$this->refreshToken = $refreshToken;
		$this->ttl = $ttl;
		$this->parameters = $parameters;
	}

	public function getToken() {
		return $this->token;
	}

	public function getTtl() {
		return $this->ttl;
	}

	public function getRefreshToken() {
		return $this->refreshToken;
	}

	public function getParameters() {
		return $this->parameters;
	}

}