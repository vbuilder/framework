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

namespace vBuilder\Test;

/**
 * Assert class based on Nette Assert
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 3, 2011
 */
class Assert extends \Assert {

	/**
	 * Checks exception assertation.
	 * @param  string class
	 * @param  string message
	 * @param  int code
	 * @param  Exception
	 * @return void
	 */
	public static function exception($class, $message, $actual) {
		if(func_num_args() > 3) {
			// Nemuzu zmenit hlavicku funkce kvuli Strictu, takze to musim obejit
			// pres dynamicke parametry
			list($class, $message, $code, $actual) = func_get_args();
			
			if(is_object($code)) {
				$actual = $code;
				$code == null;
			}

			if(!($actual instanceof $class)) {
				self::doFail('Failed asserting that '.get_class($actual)." is an instance of class $class");
			}

			if($message) {
				self::match($message, $actual->getMessage());
			}

			if($code) {
				if($actual->getCode() !== $code) {
					self::doFail('Failed asserting that exception '.get_class($actual).' with code '.$actual->getCode().' has code '.$code);
				}
			}
			
		} else
			return parent::exception($class, $message, $actual);
	}

}
