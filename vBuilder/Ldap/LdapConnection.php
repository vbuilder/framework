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
	Nette;

/**
 * LDAP connection layer
 *
 * @note If you are using SSL, valid certificate is required for LDAP server.
 *   otherwise you will keep getting errors like "Can't contact LDAP server (-1)".
 *	 You can't bypass check by adding following line into /etc/ldap.conf:
 *		TLS_REQCERT never
 *   (apache restart might be required)
 *
 * @author Adam Staněk (velbloud)
 * @since Sep 10, 2013
 */
class LdapConnection extends Nette\Object {

	const URI = 'uri';
	const BIND_DN = 'bindDn';
	const BIND_PASSWORD = 'bindPassword';

	protected $parameters = array(
		self::URI => 'ldap://localhost',
		self::BIND_DN => NULL,
		self::BIND_PASSWORD => NULL
	);

	/** @var Nette\DI\Container DI context container */
	protected $context;

	/** @var resource */
	protected $resource;

	/** @var boolean */
	protected $bound = FALSE;

	public function __construct(array $parameters, Nette\DI\Container $context) {
		$this->context = $context;
		$this->setParameters($parameters);

		if(!function_exists('ldap_connect'))
			throw new Nette\NotSupportedException("LDAP library is not installed on the server");
	}

	public function __destruct() {
		if($this->isOpened())
			$this->close();
	}

	// -------------------------------------------------------------------------

	/**
	 * Open LDAP resource
	 *
	 * @return LdapConnection fluent interface
	 * @throws Nette\InvalidStateException if already opened
	 * @throws LdapException if resource could not been created
	 */
	public function open() {
		if($this->isOpened())
			throw new Nette\InvalidStateException("LDAP resource already opened");

		$this->resource = ldap_connect($this->parameters[self::URI]);
		if(!$this->resource) throw new LdapException("Error while opening new LDAP resource");

		ldap_set_option($this->resource, LDAP_OPT_PROTOCOL_VERSION, 3);
		return $this;
	}

	/**
	 * Close opened resource
	 *
	 * @return LdapConnection fluent interface
	 * @throws Nette\InvalidStateException if not opened
	 */
	public function close() {
		if(!$this->isOpened())
			throw new Nette\InvalidStateException("No opened LDAP resource");

		ldap_close($this->resource);
		$this->bound = FALSE;
		$this->resource = NULL;
		return $this;
	}

	/**
	 * Returns TRUE if LDAP resource has been opened (ldap_connect was called)
	 *
	 * @note Please note, that this does not mean that the connection has been established!
	 *
	 * @return boolean
	 */
	public function isOpened() {
		return $this->resource !== NULL;
	}

	// -------------------------------------------------------------------------

	/**
	 * Bind an LDAP resource
	 *
	 * @return LdapConnection fluent interface
	 * @throws Nette\InvalidStateException if already bound or not yet opened
	 * @throws LdapException if bind fails
	 */
	public function bind() {
		if($this->bound)
			throw new Nette\InvalidStateException("LDAP resource has been already bound");

		if(!$this->isOpened())
			throw new Nette\InvalidStateException("No opened LDAP resource");

		if(!@ldap_bind($this->resource, $this->parameters[self::BIND_DN], $this->parameters[self::BIND_PASSWORD])) {

			$errNo = ldap_errno($this->resource);
			$errMsg = ldap_error($this->resource);

			throw new LdapException("LDAP bind failed ($errMsg)", $errNo);
		}

		$this->bound = TRUE;
		return $this;
	}

	/**
	 * Returns TRUE if LDAP resource has been bound (ldap_bind called)
	 *
	 * @return boolean
	 */
	public function isBound() {
		return $this->bound;
	}

	// -------------------------------------------------------------------------

	/**
	 * Sets connection parameters
	 *
	 * @param array $parameters
	 * @return LdapConnection fluent interface
	 */
	public function setParameters(array $parameters) {

		foreach($parameters as $key => $value) {
			if(!array_key_exists($key, $this->parameters))
				throw new Nette\InvalidArgumentException("Invalid parameter '$key'");

			if($key == self::URI && $this->isOpened())
				throw new Nette\InvalidStateException("LDAP resource already opened");

			if(in_array($key, array(self::BIND_DN, self::BIND_PASSWORD)) && $this->isBound())
				throw new Nette\InvalidStateException("LDAP resource already bound");

			$this->parameters[$key] = $value;
		}

		return $this;
	}

	// -------------------------------------------------------------------------

	/**
	 * Create result set based on specified LDAP search
	 * @param  string  base DN
	 * @param  string  filter string
	 * @param  array   name of attributes to query
	 * @param  boolean query only attribute keys (no values)?
	 * @param  integer size limit
	 * @param  integer time limit
	 * @param  integer deref flags
	 * @return LdapResult
	 * @throws  LdapException if ldap_search fails
	 */
	public function search($baseDn, $filter = '(objectclass=*)', array $attributes = array(), $attrsOnly = FALSE, $sizelimit = 0, $timelimit = 0, $deref = LDAP_DEREF_NEVER) {
		$this->prepare();

		$resource = @ldap_search($this->resource, $baseDn, $filter, $attributes, $attrsOnly, $sizelimit, $timelimit, $deref);
		if(!$resource && ldap_errno($this->resource) != 32)
			throw new LdapException(ldap_error($this->resource), ldap_errno($this->resource));

		return new LdapResult($resource, $this);
	}

	// -------------------------------------------------------------------------

	/**
	 * Returns connection resource or NULL
	 * if no resource has been opened
	 *
	 * @return LDAP link resource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Routine for lazy preparation
	 *
	 * @return LdapConnection fluent interface
	 */
	protected function prepare() {
		if(!$this->isOpened())
			$this->open();

		if(!$this->isBound())
			$this->bind();

		return $this;
	}

}
