<?php

namespace DroneMill\FoundationApi\Handlers;

use Auth;
use AuthPermission;
use EchoIt\JsonApi\Exception as ApiException;
use EchoIt\JsonApi\Handler as ApiHandler;
use EchoIt\JsonApi\Request as ApiRequest;
use EchoIt\JsonApi\Request;
use Illuminate\Http\Response as BaseResponse;

/**
* Handles API requests
*/
class Api extends ApiHandler
{
	/**
	 * Handle GET requests
	 * @param EchoIt\JsonApi\Request $request
	 * @return EchoIt\JsonApi\Model|Illuminate\Support\Collection|EchoIt\JsonApi\Response|Illuminate\Pagination\LengthAwarePaginator
	 */
	public function handleGet(ApiRequest $request)
	{
		return $this->handlePermissionAwareGet($request, new $this->model);
	}

	/**
	 * Handle PUT requests
	 * @param EchoIt\JsonApi\Request $request
	 * @return EchoIt\JsonApi\Model|Illuminate\Support\Collection|EchoIt\JsonApi\Response
	 */
	public function handlePut(ApiRequest $request)
	{
		//you can use the default PUT functionality, or override with your own
		return $this->handlePutDefault($request, new $this->model);
	}

	/**
	 * Default handling of GET request.
	 * Must be called explicitly in handleGet method.
	 *
	 * @param  EchoIt\JsonApi\Request $request
	 * @param  EchoIt\JsonApi\Model $model
	 * @return EchoIt\JsonApi\Model|Illuminate\Pagination\LengthAwarePaginator
	 */
	protected function handleGetDefault(Request $request, $model)
	{
		$total = null;

		if (empty($request->include))
		{
			$request->include = static::$exposedRelations;
		}

		if (empty($request->id))
		{
			// check if we are filtering anything
			if (!empty($request->filter))
			{
				$model = $this->handleFilterRequest($request->filter, $model);
			}

			// handle sorting
			if (!empty($request->sort))
			{
				//if sorting AND paginating, get total count before sorting!
				if ($request->pageNumber)
				{
					$total = $model->count();
				}

				$model = $this->handleSortRequest($request->sort, $model);
			}
		}
		else
		{
			$model = $model->where('id', '=', $request->id);
		}

		try
		{
			if ($request->pageNumber && empty($request->id))
			{
				$results = $this->handlePaginationRequest($request, $model, $total);
			}
			else
			{
				$results = $model->get();
			}
		}
		catch (\Illuminate\Database\QueryException $e)
		{
			throw new ApiException(
				'Database Request Failed',
				static::ERROR_SCOPE | static::ERROR_UNKNOWN_ID,
				BaseResponse::HTTP_INTERNAL_SERVER_ERROR,
				['details' => $e->getMessage()]
			);
		}

		return $results;
	}

	/**
	 *
	 *
	 * @param  EchoIt\JsonApi\Request $request
	 * @param  EchoIt\JsonApi\Model $model
	 * @return  EchoIt\JsonApi\Model|Illuminate\Pagination\LengthAwarePaginator
	 */
	protected function handlePermissionAwareGet(Request $request, $model)
	{
		switch ((int) Auth::user()->privilege)
		{
			case AuthPermission::PERMISSION_USER:
				$method = 'handleGetUser';
				break;

			case AuthPermission::PERMISSION_HOST:
				$method = 'handleGetHost';

				if (empty(Auth::user()->user_host))
				{
					throw new ApiException(
						'User host does not exist',
						static::ERROR_SCOPE | static::ERROR_UNKNOWN_ID,
						BaseResponse::HTTP_INTERNAL_SERVER_ERROR,
						[]
					);
				}

				break;

			case AuthPermission::PERMISSION_SUPPORT:
				$method = 'handleGetSupport';
				break;

			case AuthPermission::PERMISSION_OPS:
				$method = 'handleGetOps';
				break;
		}

		if (!empty($method) && method_exists($this, $method))
		{
			return $this->{$method}($request, $model);
		}

		throw new ApiException(
			'Request handler does not exist',
			static::ERROR_SCOPE | static::ERROR_UNKNOWN_ID,
			BaseResponse::HTTP_INTERNAL_SERVER_ERROR,
			['details' => ['privilege' => Auth::user()->privilege, 'method' => $method]]
		);
	}

	/**
	 * Function to handle filtering requests.
	 *
	 * @param  array $filters key=>value pairs of column and value to filter on
	 * @param  EchoIt\JsonApi\Model $model
	 * @return EchoIt\JsonApi\Model
	 */
	protected function handleFilterRequest($filters, $model)
	{
		foreach ($filters as $key => $value)
		{
			$model = $model->where($key, '=', $value);
		}

		return $model;
	}
}