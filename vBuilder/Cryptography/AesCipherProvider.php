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

namespace vBuilder\Cryptography;

use vBuilder,
	Nette;

/**
 * Basic implementation for AES-256 bit (CBC)
 * Uses OpenSSL for encryption / decryption.
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 22, 2014
 */
class AesCipherProvider extends Nette\Object implements ISymmetricCipherProvider {

	function __construct() {
		extension_loaded('openssl');
		if(!extension_loaded('openssl'))
			throw new Nette\InvalidStateException("PHP extension OpenSSL is not loaded.");
	}

	public function encrypt($data, $password) {

		$iv = openssl_random_pseudo_bytes(16);
		$encrypted_data = openssl_encrypt($data, 'aes-256-cbc', $password, true, $iv);
		return bin2hex($iv) . bin2hex($encrypted_data);
	}

	public function decrypt($data, $password) {
		$iv = hex2bin(substr($data, 0, 16 * 2));
		$data = hex2bin(substr($data, 16 * 2));

		$plain_data = openssl_decrypt($data, 'aes-256-cbc', $password, true, $iv);
		return $plain_data;
	}

}