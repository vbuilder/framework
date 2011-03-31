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

use vBuilder, Nette,
	 vBuilder\Security\SecurityException;

/**
 * Secured implementation of active entity.
 * 
 * Class takes care of authorizing all entity actions against Nette\Security ACL
 * model. All resource ids are generated automaticly and all permission checking
 * is also taken care of internally.
 *  * 
 *
 * @author Adam Staněk (V3lbloud)
 * @since Mar 4, 2011
 */
class SecureActiveEntity extends ActiveEntity implements Nette\Security\IResource {
	
	const ACL_PERMISSION_READ = 'read';
	const ACL_PERMISSION_CREATE = 'create';
	const ACL_PERMISSION_UPDATE = 'update';
	const ACL_PERMISSION_DELETE = 'delete';
	
	public function __construct() {
		$this->data->onFirstRead[] = \callback($this, 'readSecurityCheck');
		$this->onCreate[] = \callback($this, 'createSecurityCheck');
		$this->onUpdate[] = \callback($this, 'updateSecurityCheck');
		$this->onDelete[] = \callback($this, 'deleteSecurityCheck');
		
		call_user_func_array(array('parent', '__construct'), func_get_args()); 
	}
	
	/**
	 * This function is called before first read of any data (except for ID fields).
	 * It takes care of checking for read permission.
	 * 
	 * @throws SecurityException if user does not have permission to read this entity
	 */
	public function readSecurityCheck() {
		$this->checkPermission(self::ACL_PERMISSION_READ);
	}
	
	/**
	 * This function is called before insert command is commited to DB
	 * 
	 * @throws SecurityException if user does not have permission to create this entity
	 */
	public function createSecurityCheck() {
		$this->checkPermission(self::ACL_PERMISSION_CREATE);
	}
	
	/**
	 * This function is called before update command is commited to DB
	 * 
	 * @throws SecurityException if user does not have permission to update this entity
	 */
	public function updateSecurityCheck() {
		$this->checkPermission(self::ACL_PERMISSION_UPDATE);
	}
	
	/**
	 * This function is called before delete command is commited to DB
	 * 
	 * @throws SecurityException if user does not have permission to delete this entity
	 */
	public function deleteSecurityCheck() {
		$this->checkPermission(self::ACL_PERMISSION_DELETE);
	}
	
	/**
	 * Return resource id of this entity, all entity instances are child of this
	 * resource.
	 * 
	 * @return string resource id 
	 */
	public static function getParentResourceId() {
		return \get_called_class();
	}
	
	/**
	 * Returns resource ID for ACL
	 * 
	 * @return string
	 */
	public function getResourceId() {
		if($this->checkIfIdIsDefined() && count($this->metadata->getIdFields()) > 0) {
			$ids = array();
			foreach($this->metadata->getIdFields() as $name) $ids[] = $this->data->$name;
			
			$resId = self::getParentResourceId() . '(' . implode($ids, ',') . ')';
			
			$acl = Nette\Environment::getUser()->getAuthorizationHandler();
			if($acl instanceof Nette\Security\Permission && !$acl->hasResource($resId))
				$acl->addResource($resId, static::getParentResourceId()); 
			
			return $resId;
			
		} else
			return static::getParentResourceId();
	}
	
	/**
	 * Helper function for checking permission on current entity
	 * 
	 * @param string permission name
	 * @throws SecurityException if user does not have permission to do that
	 */
	protected function checkPermission($permission) {
		if(!Nette\Environment::getUser()->isAllowed($this, $permission))
			throw new SecurityException("Operation '$permission' is not permitted on '".$this->getResourceId()."'", SecurityException::OPERATION_NOT_PERMITTED);
	}
	
}
