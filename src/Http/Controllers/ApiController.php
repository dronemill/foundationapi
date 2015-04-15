<?php

namespace DroneMill\FoundationApi\Http\Controllers;

use BaseController as TheBaseController; // BaseController is already in use
use EchoIt\JsonApi\Request as ApiRequest;
use EchoIt\JsonApi\ErrorResponse as ApiErrorResponse;
use EchoIt\JsonApi\Exception as ApiException;
use Request;

class ApiController extends TheBaseController
{

	public $handlerNamespace = 'App\\Handlers\\Models\\';
	public $handlerClassSuffix = 'Handler';

	public function handleRequest($modelName, $id = null)
	{
		/**
		 * Forumulate an entity name
		 *
		 * @var  string
		 */
		$entity = ucfirst(camel_case(str_singular($modelName)));

		/**
		* Create handler name from model name
		* @var string
		*/
		$handlerClass = $this->handlerNamespace . $entity . $this->handlerClassSuffix;


		// ensure we actually have a handler class
		if (! class_exists($handlerClass))
		{
			// if a handler class does not exist for requested model,
			// it is not considered to be exposed in the API
			return new ApiErrorResponse(404, 404, 'Entity [' . $entity . '] not found');
		}

		// store request input, and params
		$url        = Request::url();
		$method     = Request::method();
		$include    = ($i = Request::input('include')) ? explode(',', $i) : $i;
		$sort       = ($i = Request::input('sort')) ? explode(',', $i) : $i;
		$filter     = ($i = Request::except('sort', 'include', 'page')) ? $i : [];
		$content    = Request::getContent();
		$page       = Request::input('page');
		$pageSize   = null;
		$pageNumber = null;

		// setup pagination
		if ($page)
		{
			// if we are doing pagination, ensure that we received the propper params
			if (is_array($page) && !empty($page['size']) && !empty($page['number']))
			{
				$pageSize = $page['size'];
				$pageNumber = $page['number'];
			}
			else
			{
				return new ApiErrorResponse(400, 400, 'Expected page[size] and page[number]');
			}
		}

		// instantiate new ApiRequest object
		$request = new ApiRequest(Request::url(), $method, $id, $content, $include, $sort, $filter, $pageNumber, $pageSize);

		// instantiate new Handler
		$handler = new $handlerClass($request);

		// a handler can throw EchoIt\JsonApi\Exception which must be gracefully handled to give proper response
		try
		{
			$res = $handler->fulfillRequest();
		}
		catch (ApiException $e)
		{
			return $e->response();
		}

		// return the json response
		return $res->toJsonResponse();
	}
}