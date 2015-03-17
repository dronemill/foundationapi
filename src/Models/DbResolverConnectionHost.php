<?php

namespace DroneMill\FoundationApi\Models;

use DroneMill\FoundationApi\Database\Model;
use AuthPermission;

class DbResolverConnectionHost extends Model {

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
	protected $table = 'db_connection_host';

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
		'type' => 'db_connection_host',
		'attributes' => [
			'id' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'db_connection' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_MODEL,
				'laxyLoad' => true,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'host' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_MODEL,
				'laxyLoad' => true,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
					'modify' => [],
				],
			],
			'updated_at' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
				],
			],
			'deleted_at' => [
				'type' => self::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE,
				'permission' => [
					'read' => [AuthPermission::PERMISSION_ALL_ALLOW, ],
				],
			],
		],
	];

	public function db_connection()
	{
		return $this->belongsTo('DbResolverConnection', 'db_connection_id', 'id');
	}
}
