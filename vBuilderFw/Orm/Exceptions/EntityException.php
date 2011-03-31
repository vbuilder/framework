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

namespace vBuilder\Orm;

/**
 * Custom exceptions for entity errors
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 17, 2011
 */
class EntityException extends \Exception {

	const NOT_FOUND = 1;
	const ID_NOT_DEFINED = 2;
	const DATATYPE_NOT_DEFINED = 3;
	const ENTITY_TYPE_NOT_DEFINED = 4;
	const SAVE_FAILED = 5;
	
}
