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

use Nette,
	vBuilder\Utils\Strings,
	vBuilder\Utils\Ldap as LdapUtils;

/**
 * Basic identity factory
 *
 * @todo  cofigurable LDAP binding + table name + id column name
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 3, 2013
 */
class IdentityFactory extends Nette\Object implements IIdentityFactory {

	const TABLE_ROLES = 'roles';

	/** @var DibiConnection */
	protected $db;

	/** @var array of string */
	protected $tableName = array(
		self::TABLE_ROLES => 'security_userRoles'
	);

	/** Constructor */
	public function __construct(\DibiConnection $dbConnection) {
		$this->db = $dbConnection;
	}

	/**
	 * Returns table name
	 *
	 * @return string table name
	 * @throws Nette\InvalidArgumentException if invalid table requested
	 */
	public function getTableName($table) {
		if(!isset($this->tableName[$table]))
			throw new Nette\InvalidArgumentException("Invalid table '$table'");

		return $this->tableName[$table];
	}

	/**
	 * Creates IIdentity object from obtained user data
	 *
	 * @param mixed user data
	 * @param IAuthenticator authenticator
	 *
	 * @return IIdentity
	 */
	public function createIdentity($userData, $authenticator) {

		$uid = NULL;
		$roles = array();
		$profile = array();

		// ---------------------------------------------------------------------

		// DB Password
		if($authenticator instanceof Authenticators\DatabasePasswordAuthenticator) {
			$uid = $userData->{$authenticator->getColumn($authenticator::ID)};
			$roles[] = "user:$uid";
			$profile = $userData;
		}

		// LDAP
		elseif($authenticator instanceof Authenticators\LdapBindAuthenticator) {
			$ldapData = (array) $userData;

			$idCol = 'id';
			$tableName = 'security_users';

			// LDAP Binding
			// DB column name -> ldap array key (or callable)
			$binding = array(

				// Primary keys
				array(
					'username' => function ($ldapData) use ($authenticator) {
						return mb_substr(
							$ldapData['dn'],
							mb_strlen($authenticator->getQueryPrefix()),
							0 - mb_strlen($authenticator->getQuerySuffix())
						);
					}

				// Other data
				), array(
					'name' => 'givenname',
					'surname' => 'sn',
					'email' => function ($ldapData) use (&$binding) {
						$username = $binding[0]['username']($ldapData);

						$tokens = Strings::splitWithEscape($ldapData['dn'], ',dc=');
						array_shift($tokens);
						return $username . '@' . implode($tokens, '.');
					}
				)
			);

			// Prepare data based on LDAP binding
			$boundData = $this->bindValues($ldapData, $binding[0]);

			$this->db->query('LOCK TABLES %n WRITE', $tableName);
			$ds = $this->db->select('*')->from($tableName);
			foreach($boundData as $key => $value) $ds->where('%n = %s', $key, $value);
			$profile = $ds->fetch();

			// If profile does not exist yet
			if($profile === FALSE) {
				$boundData = array_merge($boundData, $this->bindValues($ldapData, $binding[1]));
				$this->db->insert($tableName, $boundData)->execute();
				$boundData[$idCol] = $uid = $this->db->getInsertId();
				$profile = $boundData;
			} else {
				$uid = $profile[$idCol];
			}

			$this->db->query('UNLOCK TABLES');

			$roles[] = "user:$uid";

			// TODO: configurable
			$groupsDn = NULL;
			if($groupsDn == NULL) {
				$dnTokens = array_reverse($userData->getParsedDn());
				foreach($dnTokens as $k => $v) {
					if(!Strings::startsWith($v, 'dc=')) {
						array_splice($dnTokens, $k, count($dnTokens), array('ou=groups'));
						break;
					}
				}

				$groupDn = implode(array_reverse($dnTokens), ',');
			}

			$username = str_replace(array('\\', ')'), array('\\\\', '\\)'), $boundData['username']);
			$userGid = intval($userData->gidNumber);
			$filter = "(&(objectClass=posixGroup)(|(gidNumber=$userGid)(memberUid=$username)))";
			$result = $authenticator->ldapConnection->search($groupsDn, $filter);
			foreach($result as $record) {
				$roles[] = $record->cn;
			}
		}

		// Preshared secret
		elseif($authenticator instanceof Authenticators\PresharedSecretAuthenticator) {
			$uid = Strings::intoParameterizedString('psk', array($userData->key));
			$roles[] = $uid;
			$profile = $userData;

		// Other authenticators
		} else {
			throw new Nette\NotSupportedException("Authenticator " . get_class($authenticator) . " not supported yet");
		}

		// ---------------------------------------------------------------------

		// Remove duplicit roles
		$roles = array_unique($roles);

		// Sanity check
		if(!is_scalar($uid) || $uid == "")
			throw new Nette\InvalidStateException("User ID has to be non-empty string or number");

		// ---------------------------------------------------------------------

		$dbRoles = $this->db->query("SELECT [role] FROM %n", $this->tableName[self::TABLE_ROLES], 'WHERE [user] = %s', $uid)->fetchAll();
		foreach($dbRoles as $curr) {
			if(!in_array($curr->role, $roles))
				$roles[] = $curr->role;
		}

		// ---------------------------------------------------------------------

		// Identity
		$identity = new Nette\Security\Identity(
			$uid,
			$roles,
			$profile
		);


		return $identity;
	}

	/**
	 * Helper function for getting values from data with given binding
	 *
	 * @param  array data
	 * @param  array column binding
	 * @return array
	 */
	protected function bindValues($data, $binding) {
		$bound = array();
		foreach($binding as $key1 => $key2) {
			$bound[$key1] = is_callable($key2)
				? $key2($data)
				: ( isset($data[$key2]) ? $data[$key2] : NULL );
		}

		return $bound;
	}

}