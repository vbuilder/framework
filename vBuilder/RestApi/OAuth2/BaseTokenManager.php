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

use vBuilder,
	Nette\Object,
	Nette\Utils\Strings,
	Nette\InvalidArgumentException,
	Nette\InvalidStateException;

/**
 * Manager of OAuth2 tokens - base implementation
 *
 * Requires OpenSSL
 *
 * The refresh token keys are created using symmetric encryption of the
 * token key.
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 22, 2014
 */
abstract class BaseTokenManager extends Object implements ITokenManager {

	/** @var vBuilder\Cryptography\ISymmetricCipherProvider @inject */
	public $cipherProvider;

	/** @var string */
	protected $secretKey;

	/** @var integer token TTL in seconds */
	protected $tokenTtl = 28800;

	/**
	 * Sets secret key for refresh token generation
	 *
	 * @param string secret key
	 * @return self
	 * @throws InvalidArgumentException if invalid key is given
	 */
	public function setSecretKey($secretKey) {

		if(!is_scalar($secretKey) || $secretKey == "")
			throw new InvalidArgumentException("Secret key has to be non-empty string");

		$this->secretKey = $secretKey;
	}

	/**
	 * Sets token time to live
	 *
	 * @param integer number of seconds
	 * @return self
	 * @throws InvalidArgumentException if invalid value is given
	 */
	public function setTokenTtl($seconds) {
		$seconds = (int) $seconds;

		if($seconds < 1)
			throw new InvalidArgumentException("Token TTL has to be positive number of seconds");

		$this->tokenTtl = $seconds;
	}

	/**
	 * Returns basic token for given refresh token
	 *
	 * @param string refresh token
	 * @return string token
	 * @throws InvalidStateException if no secret key specified
	 */
	protected function refreshTokenToToken($refreshToken) {
		if(!isset($this->secretKey))
			throw new InvalidStateException("Missing secret key");

		return $this->cipherProvider->decrypt($refreshToken, $this->secretKey);
	}

	/**
	 * Returns refresh token for given token
	 *
	 * @param string token
	 * @return string refresh token
	 * @throws InvalidStateException if no secret key specified
	 */
	protected function generateRefreshToken($token) {
		if(!isset($this->secretKey))
			throw new InvalidStateException("Missing secret key");

		return $this->cipherProvider->encrypt($token, $this->secretKey);
	}

	/**
	 * Returns random token
	 *
	 * @return string
	 */
	protected function generateToken() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

}