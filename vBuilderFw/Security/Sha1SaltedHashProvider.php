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

 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Security;

/**
 * Class for making salted password hashes using SHA-1 algorithm.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 10, 2011
 */
class Sha1SaltedHashProvider implements IPasswordHashProvider {

	/**
	 * Return hashed password.
	 *
	 * Warning: The passwords are salted.
	 * Don't use this for checking on strings. Use checkPassword instead.
	 *
	 * @param string plain text password
	 * @return string password hash
	 */
	public function hashPassword($password) {
		return self::__hash($password."", NULL, "sha1");
	}

	/**
	 * Checks password against it's hash
	 *
	 * @param string test password in plain text
	 * @param string hash of password to check against
	 *
	 * @return boolean true if passwords match
	 */
	public function checkPassword($password, $hash) {
		return self::__hash($password, $hash, "sha1") !== false;
	}

	/**
	 * Hash function for maintenance salted passwords.
	 * Kindly taken from one of anonymous contributors (including func __harvest, _scrabmle):
	 *    http://www.php.net/manual/en/function.sha1.php#94326
	 * and corrected mistakes.
	 *
	 * @param string Password to hash
	 * @param string Hash to compare with
	 * @param string Hashing algorithm
	 *
	 * @return string|boolean Hash of password if there was no hash to check taken or
	 *     if the hashes match. False if they dont.
	 */
	static private function __hash($password, $obscured = NULL, $algorithm = "sha1") {
		// If hash isn't right length it might get to endless loop
		if($obscured !== null && strlen($obscured) != 40) return false;

	    // whether to use user specified algorithm
	    $mode = in_array($algorithm, hash_algos());
	    // generate random salt
	    $salt = uniqid(mt_rand(), true);
	    // hash it
	    $salt = $mode ? hash($algorithm, $salt) : sha1($salt);
	    // get the length
	    $slen = strlen($salt);
	    // compute the actual length of salt we will use
	    // 1/8 to 1/4 of the hash, with shorter passwords producing longer salts
	    $slen = max($slen >> 3, ($slen >> 2) - strlen($password));
	    // if we are checking password against a hash, harvest the actual salt from it, otherwise just cut the salt we already have to the proper size
	    $salt = $obscured ? self::__harvest($obscured, $slen, $password) : substr($salt, 0, $slen);
	    // hash the password - this is maybe unnecessary
	    $hash = $mode ? hash($algorithm, $password) : sha1($password);
	    // place the salt in it
	    $hash = self::__scramble($hash, $salt, $password);
	    // and hash it again
	    $hash = $mode ? hash($algorithm, $hash) : sha1($hash);
	    // cut the result so we can add salt and maintain the same length
	    $hash = substr($hash, $slen);
	    // ... do that
	    $hash = self::__scramble($hash, $salt, $password);
	    // and return the result
	    return $obscured && $obscured !== $hash ? false : $hash;
	}

	/**
	 * Function helper for placing salt into hash
	 */
	static private function __scramble($hash, $salt, $password) {
	    $k = strlen($password); $j = $k = $k > 0 ? $k : 1; $p = 0; $index = array(); $out = ""; $m = 0;
	    for ($i = 0; $i < strlen($salt); $i++)
	    {
	      $c = substr($password, $p, 1);
	      $j = pow($j + ($c !== false ? ord($c) : 0), 2) % (strlen($hash) + strlen($salt));
	      while (array_key_exists($j, $index))
	        $j = ++$j % (strlen($hash) + strlen($salt));
	      $index[$j] = $i;
	      $p = ++$p % $k;
	    }
	    for ($i = 0; $i < strlen($hash) + strlen($salt); $i++)
	      $out .= array_key_exists($i, $index) ? $salt[$index[$i]] : $hash[$m++];
	    return $out;
	}

	/**
	 * Function helper for taking salt out of hash
	 */
	static private function __harvest($obscured, $slen, $password) {
	    $k = strlen($password); $j = $k = $k > 0 ? $k : 1; $p = 0; $index = array(); $out = "";
	    for ($i = 0; $i < $slen; $i++)
	    {
	      $c = substr($password, $p, 1);
	      $j = pow($j + ($c !== false ? ord($c) : 0), 2) % strlen($obscured);
	      while (in_array($j, $index))
	        $j = ++$j % strlen($obscured);
	      $index[$i] = $j;
	      $p = ++$p % $k;
	    }
	    for ($i = 0; $i < $slen; $i++)
	      $out .= $obscured[$index[$i]];
	    return $out;
	}

}
