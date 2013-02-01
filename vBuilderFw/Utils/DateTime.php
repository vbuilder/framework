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

namespace vBuilder\Utils;

use Nette;

/**
 * Date/Time routines
 *
 * @author Adam Staněk (velbloud)
 * @since Jan 30, 2013
 */
class DateTime {

	const MINUTE	= 'minute';
	const HOUR		= 'hour';
	const DAY		= 'day';
	const WEEK		= 'week';
	const MONTH		= 'month';
	const YEAR		= 'year';

	/**
	 * Returns the first valid DT in given period
	 * 
	 * @param  \DateTime $dt     [description]
	 * @param  string vBuilder\Utils\DateTime period constant
	 * @return \DateTime
	 * @throws  Nette\InvalidArgumentException If invalid period is specified
	 */
	public static function startOfPeriod(\DateTime $dt, $period) {

		switch($period) {
			case self::MINUTE:
				return \DateTime::createFromFormat('Y-m-d H:i:s', $dt->format('Y-m-d H:i:00'), $dt->getTimezone());

			case self::HOUR:
				return \DateTime::createFromFormat('Y-m-d H:i:s', $dt->format('Y-m-d H:00:00'), $dt->getTimezone());

			case self::DAY:
				return \DateTime::createFromFormat('Y-m-d H:i:s', $dt->format('Y-m-d 00:00:00'), $dt->getTimezone());			
			case self::WEEK:
				$dt2 = \DateTime::createFromFormat('Y-m-d H:i:s', $dt->format('Y-m-d 00:00:00'), $dt->getTimezone());
				$dt2->modify('-' . ($dt2->format('N') - 1) . ' days');
				return $dt2;

			case self::MONTH:
				return \DateTime::createFromFormat('Y-m-d H:i:s', $dt->format('Y-m-01 00:00:00'), $dt->getTimezone());

			case self::YEAR:
				return \DateTime::createFromFormat('Y-m-d H:i:s', $dt->format('Y-01-01 00:00:00'), $dt->getTimezone());

			default:
				throw new Nette\InvalidArgumentException("Invalid period argument");
		}
	}

	/**
	 * Returns the last valid DT of given period
	 * 
	 * @param  \DateTime $dt     [description]
	 * @param  string vBuilder\Utils\DateTime period constant
	 * @return \DateTime
	 * @throws  Nette\InvalidArgumentException If invalid period is specified
	 */
	public static function endOfPeriod(\DateTime $dt, $period) {
		$dt2 = static::startOfPeriod($dt, $period);

		switch($period) {
			case self::MINUTE:
				return $dt2->modify('+59 seconds');

			case self::HOUR:
				return $dt2->modify('+59 minutes 59 seconds');		
				
			case self::DAY:
				return $dt2->modify('+23 hours 59 minutes 59 seconds');

			case self::WEEK:
				return $dt2->modify('+6 days 23 hours 59 minutes 59 seconds');

			case self::MONTH:
				return $dt2->modify('+' . ($dt2->format('t') - 1) . ' days 23 hours 59 minutes 59 seconds');

			case self::YEAR:
				return \DateTime::createFromFormat('Y-m-d H:i:s', $dt->format('Y-12-31 23:59:59'), $dt->getTimezone());
		}
	}

}