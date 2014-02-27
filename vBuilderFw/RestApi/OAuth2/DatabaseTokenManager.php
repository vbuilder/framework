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
	Nette;

/**
 * Manager of OAuth2 tokens - database storing
 *
 * @author Adam Staněk (velbloud)
 * @since Feb 22, 2014
 */
class DatabaseTokenManager extends BaseTokenManager {

	/** @var DibiConnection @inject */
	public $dbConnection;

	/** @var string table name */
	protected $tableName = 'oauth2_tokens';

	/**
	 * Returns table name
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * Sets table name
	 *
	 * @param string table name
	 * @return self
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
		return $this;
	}

	/**
	 * @inheritDoc
  	 */
	function getToken($token) {
		$existingToken = $this->dbConnection->query(
			'SELECT * FROM %n', $this->tableName,
			'WHERE [token] = %s', $token,
			'AND [expires] > %t', new \DateTime
		)->fetch();

		if($existingToken === FALSE)
			return FALSE;

		$ttl = $existingToken->expires->getTimestamp() - time();
		return new Token($token, $ttl, $this->generateRefreshToken($token), $existingToken->parameters === NULL ? NULL : unserialize($existingToken->parameters));
	}

	/**
	 * @inheritDoc
  	 */
	function createToken($parameters = NULL) {
		$this->purgeOldTokens();
		$this->dbConnection->begin();
		$token = $this->generateNewToken();

		$dt = new \DateTime;
		$dt->modify("+" . $this->tokenTtl . " seconds");

		$iData = array(
			'token' => $token,
			'expires' => $dt,
			'parameters' => $parameters === NULL ? NULL : serialize($parameters)
		);

		$this->dbConnection->query('INSERT INTO %n', $this->tableName, $iData);
		$this->dbConnection->commit();

		// -----------------

		return new Token($token, $this->tokenTtl, $this->generateRefreshToken($token), $parameters);
	}

	/**
	 * @inheritDoc
  	 */
	function refreshToken($refreshToken) {
		$this->purgeOldTokens();
		$token = $this->refreshTokenToToken($refreshToken);

		$this->dbConnection->begin();

		// Check for existing token and lock it
		$existingToken = $this->dbConnection->query(
			'SELECT * FROM %n', $this->tableName,
			'WHERE [token] = %s', $token,
			'FOR UPDATE'
		)->fetch();

		// No matching token found
		if($existingToken === FALSE) {
			$this->dbConnection->rollback();
			return FALSE;
		}

		$newToken = $this->generateNewToken();
		$dt = new \DateTime;
		$dt->modify("+" . $this->tokenTtl . " seconds");

		$this->dbConnection->query(
			'UPDATE %n', $this->tableName,
			'SET [token] = %s,', $newToken,
			'[expires] = %t', $dt,
			'WHERE [token] = %s', $token
		);

		$this->dbConnection->commit();

		return new Token($newToken, $this->tokenTtl, $this->generateRefreshToken($newToken), $existingToken->parameters);
	}

	/**
	 * Purges out old tokens from DB
	 *
	 * @return void
	 */
	public function purgeOldTokens() {
		$dt = new \DateTime;
		$dt->modify("-14 days");

		$this->dbConnection->query(
			'DELETE FROM %n', $this->tableName,
			'WHERE [expires] < %t', $dt
		);
	}

	/**
	 * Helper function for generation and getting lock for new
	 * random token
	 *
	 * @return string new token
	 */
	protected function generateNewToken() {
		// Generate new key
		for($i = 0; ; $i++) {
			if($i > 10) {
				$this->dbConnection->rollback();
				throw new Nette\InvalidStateException("Couldn't generate unused token");
			}

			$newToken = $this->generateToken();
			if($this->dbConnection->query(
				'SELECT * FROM %n', $this->tableName,
				'WHERE [token] = %s', $newToken,
				'FOR UPDATE'
			)->fetch() === FALSE) break;
		}

		return $newToken;
	}

}