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
 * Modified DibiDataSource for getting results
 * associated in some row class.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 3, 2011
 */
class DataSource extends \DibiDataSource {
	
  private $rowClass = null;

  /**
   * Basic constructor takes and hold parameters
   * 
   * @param string $sql sql query string
   * @param string $rowClass row class
   * @param DibiConnection $connection connection link
   */
  public function __construct($sql, $rowClass = null, DibiConnection $connection = null) {
    $this->rowClass = $rowClass;

	 if($connection === null) $connection = \dibi::getConnection();
    $sql = $connection->translate($sql);
	 
    parent::__construct($sql, $connection);
  }
  
  /**
   * Function override for obtaining row results
   * 
   * @return row results in specified class
   */
  public function getResult() {
    $result = parent::getResult();
  
    if($this->rowClass && $result instanceof \DibiResult) {
      $result->setRowClass($this->rowClass);
    } 
    
    return $result;
  }
  
}