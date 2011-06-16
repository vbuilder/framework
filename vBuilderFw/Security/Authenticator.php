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

use Nette, dibi,
 Nette\Security\Identity,
 Nette\Security\AuthenticationException,
 vBuilder\Orm\Repository;

/**
 * Simple authenticator against vBuilder user model
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 4, 2011
 */
class Authenticator implements Nette\Security\IAuthenticator {
	
	/**
	 * Performs an authentication against data model.
	 * and returns IIdentity on success or throws AuthenticationException
	 * 
	 * @param array credentials
	 * 
	 * @return IIdentity
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials) {
		$securityConfig = Nette\Environment::getConfig('security');
		$entity = isset($securityConfig['user']['entity'])
				? $securityConfig['user']['entity']
				: 'vBuilder\Security\User';
		
		$user = Repository::findAll($entity)->where('[username] = %s', $credentials[self::USERNAME])->fetch();

		if($user !== false) {
			if($user->checkPassword($credentials[self::PASSWORD])) {
				dibi::query(
					'INSERT INTO ['.LastLoginInfo::getMetadata()->getTableName().'] ([userId], [time], [ip], [time2], [ip2]) VALUES(' .
					'%i', $user->getId(), ', NOW(), %s', Nette\Environment::getHttpRequest()->getRemoteAddress(), ', NULL, NULL) ' .
					'ON DUPLICATE KEY UPDATE [time2] = [time], [ip2] = [ip], [time] = NOW(), [ip] = %s', Nette\Environment::getHttpRequest()->getRemoteAddress()
				);					  
					  
				return $user;
			} else
				throw new AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		} else
			throw new AuthenticationException("User '".$credentials[self::USERNAME]."' not found.", self::IDENTITY_NOT_FOUND);
	}

}
