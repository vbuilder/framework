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

/**
 * Manager of OAuth2 tokens
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 22, 2014
 */
interface ITokenManager {

	/**
	 * Returns token or FALSE
	 *
	 * @param string token
	 * @return Token|FALSE
	 */
	function getToken($token);

	/**
	 * Invalidates existing token.
	 * Returns FALSE if token does not exist.
	 *
	 * @param string token
	 * @return TRUE|FALSE
	 */
	function invalidateToken($token);

	/**
	 * Creates new authorization token
	 *
	 * @param mixed token parameters
	 * @return Token
	 */
	function createToken($parameters = NULL);

	/**
	 * Refreshes existing token bound with refresh token.
	 * Returns FALSE if no matching token found.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-15#section-6
	 *
	 * @param string refresh token
	 * @return Token|FALSE
	 */
	function refreshToken($refreshToken);

}