<?php

namespace DroneMill\FoundationApi\Auth;

use Auth;
use Log;

class Permission {

	const PERMISSION_ALL           = 100;
	const PERMISSION_ALL_ALLOW     = ['type' => 'allow', 'level' => self::PERMISSION_ALL];
	const PERMISSION_ALL_DENY      = ['type' => 'deny',  'level' => self::PERMISSION_ALL];

	const PERMISSION_USER          = 30;
	const PERMISSION_USER_ALLOW    = ['type' => 'allow', 'level' => self::PERMISSION_USER];
	const PERMISSION_USER_DENY     = ['type' => 'deny',  'level' => self::PERMISSION_USER];

	const PERMISSION_HOST          = 20;
	const PERMISSION_HOST_ALLOW    = ['type' => 'allow', 'level' => self::PERMISSION_HOST];
	const PERMISSION_HOST_DENY     = ['type' => 'deny',  'level' => self::PERMISSION_HOST];

	const PERMISSION_SUPPORT       = 10;
	const PERMISSION_SUPPORT_ALLOW = ['type' => 'allow', 'level' => self::PERMISSION_SUPPORT];
	const PERMISSION_SUPPORT_DENY  = ['type' => 'deny',  'level' => self::PERMISSION_SUPPORT];

	const PERMISSION_OPS           = 0;
	const PERMISSION_OPS_ALLOW     = ['type' => 'allow', 'level' => self::PERMISSION_OPS];
	const PERMISSION_OPS_DENY      = ['type' => 'deny',  'level' => self::PERMISSION_OPS];

	/**
	 * Check if the user can read this attribute
	 *
	 * @param   permissions   $permissions  the model permissions
	 * @return  boolean
	 */
	public static function UserHasPermission($permissions)
	{
		if (! Auth::check())
		{
			throw new \Exception("Login Required");
		}

		return self::hasPermission(Auth::user()->privilege, $permissions);
	}

	public static function HasPermission($privilege, Array $permissions)
	{
		$privilege = ((int) $privilege);

		$authed = false;

		foreach ($permissions as $perm)
		{
			switch ($perm)
			{
				case self::PERMISSION_ALL_ALLOW:
					$authed = true;
					break;

				case self::PERMISSION_ALL_DENY:
					$authed = false;
					break;

				case self::PERMISSION_USER_ALLOW:
					if ($privilege === self::PERMISSION_USER) $authed = true;
					break;

				case self::PERMISSION_USER_DENY:
					if ($privilege === self::PERMISSION_USER) $authed = false;
					break;

				case self::PERMISSION_HOST_ALLOW:
					if ($privilege === self::PERMISSION_HOST) $authed = true;
					break;

				case self::PERMISSION_HOST_DENY:
					if ($privilege === self::PERMISSION_HOST) $authed = false;
					break;

				case self::PERMISSION_SUPPORT_ALLOW:
					if ($privilege === self::PERMISSION_SUPPORT) $authed = true;
					break;

				case self::PERMISSION_SUPPORT_DENY:
					if ($privilege === self::PERMISSION_SUPPORT) $authed = false;
					break;

				case self::PERMISSION_OPS_ALLOW:
					if ($privilege === self::PERMISSION_OPS) $authed = true;
					break;

				case self::PERMISSION_OPS_DENY:
					if ($privilege === self::PERMISSION_OPS) $authed = false;
					break;
			}
		}

		return $authed;
	}
}