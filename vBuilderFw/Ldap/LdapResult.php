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

namespace vBuilder\Ldap;

use vBuilder,
	Nette,
	IteratorAggregate;

/**
 * LDAP result set
 * 
 * @author Adam Staněk (velbloud)
 * @since Sep 10, 2013
 */
class LdapResult extends Nette\Object implements IteratorAggregate {

	/** @var LdapConnection */
	protected $connection;

	/** @var LDAP search result identifier */
	protected $resource;

	function __construct($resource, LdapConnection $connection) {
		$this->resource = $resource;
		$this->connection = $connection;
	}

	function __destruct() {
		if($this->resource)
			ldap_free_result($this->resource);
	}

	// -------------------------------------------------------------------------
	
	public function getConnection() {
		return $this->connection;
	}

	public function getResource() {
		return $this->resource;
	}

	public function getIterator() {
		return new LdapResultIterator($this);
	}

	// -------------------------------------------------------------------------
	
	public function fetch($entryResource = NULL) {
		if(!$this->resource)
			return FALSE;

		if($entryResource === NULL)
			$entryResource = ldap_first_entry($this->connection->resource, $this->resource);

		return new LdapRecord($this, $entryResource);
	}

	public function fetchAll() {
		if(!$this->resource)
			return FALSE;

		return iterator_to_array($this->getIterator());
	}

}