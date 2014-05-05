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
	Iterator;

/**
 * LDAP result iterator
 * 
 * @author Adam Staněk (velbloud)
 * @since Sep 10, 2013
 */
class LdapResultIterator implements Iterator {

	/** @var LdapResult */
	protected $result;

	/** @var LDAP entry resource */
	protected $resource;

	function __construct(LdapResult $result) {
		$this->result = $result;
	}

	// -------------------------------------------------------------------------
	
	function rewind() {
		if($this->result->resource)
			$this->resource = ldap_first_entry($this->result->connection->resource, $this->result->resource);
	}

	function current() {
		return $this->result->fetch($this->resource);
	}

	function key() {
		return ldap_get_dn($this->result->connection->resource, $this->resource);
	}

	function next() {
		if($this->result->resource)
			$this->resource = ldap_next_entry($this->result->connection->resource, $this->resource);
	}

	function valid() {
		return $this->result->resource && $this->resource != FALSE;
	}

}