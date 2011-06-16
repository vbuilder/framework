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

use vBuilder,
	 Nette,
	 dibi,
	 Nette\Security\Permission;

/**
 * Last login info entity
 *
 * @Table(name="security_lastLoginInfo")
 * 
 * @Column(userId, pk, type="integer")
 * @Column(time, realName="time2", type="DateTime")
 * @Column(ip, realName="ip2", type="string")
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jun 16, 2011
 */
class LastLoginInfo extends vBuilder\Orm\ActiveEntity {

	

}
