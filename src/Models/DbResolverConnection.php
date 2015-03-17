<?php

namespace DroneMill\FoundationApi\Models;

use DroneMill\FoundationApi\Database\Model;
use AuthPermission;

class DbResolverConnection extends Model {

	/**
	 * The database connection used by the model.
	 *
	 * @var string
	 */
	protected $connection = 'server';

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'db_connection';

	/**
	 * primary key is not auto-incrementing
	 *
	 * @var  boolean
	 */
	public $incrementing = false;

	/**
	 * ResponseDocument references
	 *
	 * @var  Array
	 */
	public $jsonView = [
		'type' => 'db_connection',
		'attributes' => [
			'id' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'driver' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'database' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'username' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'password' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'charset' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'collation' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'prefix' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'created_at' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
				],
			],
			'updated_at' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
				],
			],
		],
	];

	/**
	 * Eagerly load values
	 *
	 * @var  array
	 */
	protected $with = [ 'db_connection_hosts' ];

	public function db_connection_hosts()
	{
		return $this->hasMany('DroneMill\FoundationApi\Models\DbResolverConnectionHost', 'db_connection_id', 'id');
	}

	public function delete()
	{
		// http://stackoverflow.com/a/14174356
		// $this->db_connection_host()->delete();

		// Cacsade the delete
		DbResolverConnectionHost::where('db_connection_id', $this->id)->delete();

		return parent::delete();
	}
}
