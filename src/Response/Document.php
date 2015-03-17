<?php

namespace DroneMill\FoundationApi\Response;

use AuthPermission;
use DroneMill\FoundationApi\Database\Model;
use DroneMill\Helpers\Arrays as Arr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Log;


class Document implements Renderable {

	/**
	 * A list of keys to ensure we have, even if we have no models
	 *
	 * @var  array
	 */
	protected $keys = [];

	/**
	 * The models we are storing, pre-render
	 *
	 * @var  array
	 */
	protected $models = [];

	/**
	 * the compiled document
	 *
	 * @var  array
	 */
	protected $compiled = ['refs' => []];


	/**
	 * push a model or collection into the response
	 *
	 * @method  push
	 * @param   Eloquent|Collection  $model  the model or collection to push
	 * @return  void
	 */
	public function push($model, $key = '')
	{
		$this->models[] = $model;

		if ($key) $this->keys[] = $key;
	}

	/**
	 * Symmenantics
	 */
	public function append($model)
	{
		$this->push($model);
	}

	/**
	 * return the current stack of models
	 *
	 * @return  array  the models
	 */
	public function models()
	{
		return $this->models;
	}

	/**
	 * flush (empty) the models stack
	 *
	 * @return  void
	 */
	public function flush()
	{
		$this->models = [];
		$this->compiled = ['refs' => []];
		$this->keys = [];
	}

	/**
	 * build and compile the response document
	 *
	 * @method  build
	 * @param   integer  $status  the http status code to return
	 * @return  Response
	 */
	public function build($status = 200)
	{
		return response()->json($this->render(), $status);
	}

	/**
	 * render the document
	 *
	 * @method  render
	 * @return  array  the rendered document
	 */
	public function render()
	{
		foreach ($this->models as $model)
		{
			if ($model instanceof Collection)
			{
				$self = $this;

				$model->each(function($m) use ($self)
				{
					$self->compile($m);
				});

				continue;
			}

			$this->compile($model);
		}

		if (empty($this->compiled['refs']))
		{
			unset($this->compiled['refs']);
		}

		foreach ($this->keys as $key)
		{
			Arr::ensureKeyExists($this->compiled, $key);
		}

		if (empty($this->compiled))
		{
			$this->compiled = new \stdClass();
		}

		return $this->compiled;
	}


	/**
	 * compile a model, adding it to the collective document array
	 *
	 * @method  compile
	 * @param   model   $model  the model or colleciton to add
	 * @return  void
	 */
	protected function compile(Eloquent $model, $isRef = false)
	{
		// setup a scope reference
		$self = $this;

		Log::debug("Compiling model of type: " . get_class($model));

		// first we need to get an array from this model
		$modelArray = $model->toArray();
		$compiledArray = [];

		// We need to see if any of the model's attributes are either
		// a model, or a collection of models
		foreach ($model->jsonView['attributes'] as $attr => $attrInfo)
		{
			$this->ensureAttributePermissions($attrInfo);

			// see if we have permission to read this attribute
			if (! AuthPermission::userHasPermission($attrInfo['permission']['read']))
			{
				continue;
			}

			//
			// at this point we have permission for this attribute
			//

			// if we have a basic attribute, then just simply continue on
			if ($attrInfo['type'] === Model::JSONVIEW_ATTRIBUTE_TYPE_ATTRIBUTE)
			{
				if (! array_key_exists($attr, $modelArray)) $modelArray[$attr] = null;

				$v = $modelArray[$attr];

				if (array_key_exists('datatype', $attrInfo))
				{
					switch ($attrInfo['datatype'])
					{
						case 'int':
						case 'integer':
							$v = (int) $v;
							break;

						case 'bool':
						case 'boolean':
							$v = (bool) $v;
							break;

						case 'float':
						case 'double':
						case 'real':
							$v = (float) $v;
							break;

						case 'string':
							$v = (string) $v;
							break;

						case 'array':
							$v = (array) $v;
							break;

						case 'object':
							$v = (object) $v;
							break;

						case 'unset':
							$v = (unset) $v;
							break;
					}
				}

				$compiledArray[$attr] = $v;

				continue;
			}

			// if this is a ref, then we are not going to load / fetch relationships
			if ($isRef)
			{
				continue;
			}

			if ($attrInfo['type'] === Model::JSONVIEW_ATTRIBUTE_TYPE_MODEL)
			{
				$val = $this->smartLoadAttribute($attr, $attrInfo, $model);

				// if we are processing a relation, then we need to fetch the record
				if ($val instanceof \Illuminate\Database\Eloquent\Relations\Relation)
				{
					$val = $val->first();
				}

				if (! is_null($val))
				{
					$this->appendCompiledAttribute($val, $compiledArray);
				}
			}

			if ($attrInfo['type'] === Model::JSONVIEW_ATTRIBUTE_TYPE_COLLECTION)
			{
				$collection = $this->smartLoadAttribute($attr, $attrInfo, $model);

				if (empty($collection)) continue;

				$collection->each(function($model) use ($self, &$compiledArray)
				{
					$self->appendCompiledAttribute($model, $compiledArray, true);
				});
			}
		}

		// Pluralize the type
		$typeKey = str_plural($model->jsonView['type']);

		$id = 'id';
		if ($model->jsonView && array_key_exists('id', $model->jsonView))
		{
			$id = $model->jsonView['id'];
		}

		if (! $isRef)
		{
			Arr::ensureKeyExists($this->compiled, $typeKey);
			$this->compiled[$typeKey][$model->$id] = $compiledArray;
		}
		else
		{
			Arr::ensureKeyExists($this->compiled['refs'], $typeKey);
			$this->compiled['refs'][$typeKey][$model->$id] = $compiledArray;
		}
	}


	/**
	 * Ensure that an attributeInfo array has the propper permission structure
	 *
	 * @param   array  $attrInfo  the attrribute info
	 * @return  void
	 */
	protected function ensureAttributePermissions(Array &$attrInfo)
	{
		// ensure permission are defined
		if (! array_key_exists('permission', $attrInfo))
		{
			$attrInfo['permission'] = [];
		}

		// ensure we have read perms defined
		if (! array_key_exists('read', $attrInfo['permission']))
		{
			$attrInfo['permission']['read'] = [];
		}

		// ensure we have modify perms defined
		if (! array_key_exists('modify', $attrInfo['permission']))
		{
			$attrInfo['permission']['modify'] = [];
		}
	}


	/**
	 * load or fetch a model's attribut via method or relationship
	 *
	 * @param   string    $attr      the attribute to load/fetch
	 * @param   array     $attrInfo  the attribute info
	 * @param   Eloquent  $model     the model to operate on
	 * @return  mized
	 */
	protected function smartLoadAttribute($attr, $attrInfo, &$model)
	{
		if (array_key_exists('lazyLoad', $attrInfo) && $attrInfo['lazyLoad'])
		{
			$model->load($attr);
			return $model->$attr;
		}
		else if (array_key_exists('method', $attrInfo) && $attrInfo['method'])
		{
			return $model->$attr();
		}

		// throw new exception
	}


	/**
	 * add an attribute, model or collection to the compiled array
	 *
	 * @param   Eloquent  $model          the model
	 * @param   Array     $compiledArray  the array to append to
	 * @param   boolean   $isCollection   is this a collection
	 * @return  void
	 */
	protected function appendCompiledAttribute(Eloquent $model, Array &$compiledArray, $isCollection = false)
	{
		if (isset($model->jsonView) && array_key_exists('type', $model->jsonView))
		{
			$attrType = $model->jsonView['type'];
		}
		else
		{
			$attrType = get_class($model);
		}

		if ($isCollection)
		{
			$attrType = str_plural($attrType);

			Arr::ensureKeyExists($compiledArray, $attrType);
			$compiledArray[$attrType][] = $model->id;
		}
		else
		{
			$compiledArray[$attrType . '_id'] = $model->id;
		}

		$this->compile($model, true);
	}


	/**
	 * push a ref into the compiled array
	 *
	 * @method  pushRef
	 * @param   Eloquent   $reference  the model to push
	 * @return  void
	 */
	protected function pushRef($reference)
	{
		if (! $reference)
		{
			// for example, if the relationship returns 0 records
			return;
		}

		Arr::ensureKeyExists($this->compiled['refs'], $reference->refs['key']);

		if (array_key_exists($reference->id, $this->compiled['refs'][$reference->refs['key']]))
		{
			return;
		}

		$this->compiled['refs'][$reference->refs['key']][(string) $reference->id] = $reference->toArray();
	}

}
