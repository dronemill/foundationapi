<?php

namespace DroneMill\FoundationApi\Database;

use App;
use DB;
use Log;
use DroneMill\FoundationApi\Auth\Permission as AuthPermission;
use DroneMill\FoundationApi\Auth\Permission\Exception as AuthPermissionException;
use EchoIt\JsonApi\Model as JsonApiModel;

abstract class Model extends JsonApiModel {

	const JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE  = 'attribute';
	const JSONVIEW_ATTRIBUTE_TYPE_MODEL      = 'model';
	const JSONVIEW_ATTRIBUTE_TYPE_COLLECTION = 'collection';

	/**
	 * enable timestamps
	 *
	 * @var  boolean
	 */
	public $timestamps = true;

	/**
	 * primary key is not auto-incrementing
	 *
	 * @var  boolean
	 */
	public $incrementing = false;

	/**
	 * The attributes that should be casted to native types.
	 *
	 * @var array
	 */
	protected $casts = [
		'enabled' => 'boolean',
	];

	/**
	 * Scope Where Id In
	 *
	 * @method  scopeWhereIdIn
	 * @param   Illuminate\Database\Query\Builder
	 * @param   array $ids
	 * @oaram   bool $returnOnEmpty Should we abort scope when no ids were provided?
	 * @return  Illuminate\Database\Query\Builder
	 */
	public function scopeWhereIdIn($query, $ids, $returnOnEmpty = false)
	{
		if (empty($ids))
		{
			if ($returnOnEmpty) return $query;

			return $query->where('id', null);
		}

		return $query->whereIn('id', $ids);
	}

	/**
	 * Scope Where enabled
	 *
	 * @method  scopeWhereEnabled
	 * @param   Illuminate\Database\Query\Builder
	 * @param   bool $enabled
	 * @return  Illuminate\Database\Query\Builder
	 */
	public function scopeWhereEnabled($query, $enabled = true)
	{
		return $query->where('enabled', $enabled);
	}

	/**
	* Handle Model Boot
	*/
	public static function boot()
	{
		parent::boot();

		static::updating(function($model)
		{
			$original = $model->getOriginal();

			// FIXME need to do permission check here
		});
	}

	/**
	 * Call the parent constructor.
	 *
	 * @param  string  $method
	 * @param  array   $args
	 * @return mixed
	 */
	public function ConstructParent($args)
	{
		switch (count($args))
		{
			case 0:
				return parent::__construct();

			case 1:
				return parent::__construct($args[0]);

			case 2:
				return parent::__construct($args[0], $args[1]);

			case 3:
				return parent::__construct($args[0], $args[1], $args[2]);

			case 4:
				return parent::__construct($args[0], $args[1], $args[2], $args[3]);

			default:
				return call_user_func_array("parent::__construct", $args);
		}
	}


	/**
	 * Patch the model with a given set of attributes
	 *
	 * We accomplish this by iterating over the set of attributes, checking
	 * if the Authed user has modify permission, and then updating the model.
	 * Saving when done.
	 *
	 * @param array $attributes attribute=>delta
	 */
	public function patch(array $attributes = [])
	{
		Log::info('Patching Model', ['model' => get_called_class()]);

		foreach ($attributes as $attribute => $delta)
		{
			if (
				(! array_key_exists($attribute, $this->jsonView['attributes'])) ||
				(! array_key_exists('permission', $this->jsonView['attributes'][$attribute])) ||
				(! array_key_exists('modify', $this->jsonView['attributes'][$attribute]['permission']))
			)
			{
				Log::warning('Patch is not supported on this attribute', ['model' => get_called_class(), 'attribute' => $attribute]);
				throw new AuthPermissionException('Patch is not supported on this attribute: ' . $attribute);
			}

			$modifyPerms = $this->jsonView['attributes'][$attribute]['permission']['modify'];

			if (! AuthPermission::UserHasPermission($modifyPerms))
			{
				Log::warning('User does not have permission to patch attribute', ['model' => get_called_class(), 'attribute' => $attribute]);
				throw new AuthPermissionException('permission denied to modify attribute: ' . $attribute);
			}

			Log::info('Patching attribute', ['model' => get_called_class(), 'attribute' => $attribute]);
			$this->$attribute = $delta;
		}

		$this->save();
	}

	/**
	 * find an un-used id
	 *
	 * @param   integer  $times  the number of times to attempt
	 * @throws  Exception
	 * @return  string           the new id
	 */
	public static function findNewId($times = 16)
	{
		$model = new Static;

		for ($i = 0; $i < $times; $i++)
		{
			if (App::environment() === 'testing')
			{
				$id = \Libs\Utils\Integer\Misc::rand(16);
			}
			else
			{
				$id = \Libs\Utils\Integer\Misc::random64();
			}

			if (DB::connection($model->getConnectionName())->table($model->getTable())->where('id', '=', $id)->count() === 0)
			{
				return $id;
			}
		}

		throw new \Exception('Failed finding new id after ' . $times . ' times');
	}

}
