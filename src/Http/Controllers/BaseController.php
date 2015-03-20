<?php

namespace DroneMill\FoundationApi\Http\Controllers;

use Auth;
use AuthPermission;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use ResponseDocument;

abstract class BaseController extends Controller {

	use DispatchesCommands, ValidatesRequests;

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

	/**
	 * Authenticate
	 *
	 * @method  authenticate
	 * @return  an auth attempt
	 */
	public static function authenticate($request)
	{
		$token = $request->header('x-dronemill-auth');

		if (empty($token))
		{
			$token = $request->input('DRONEMILL_AUTH');
		}

		return Auth::attempt(['token' => $token]);
	}

	/**
	 * Return an error to the client
	 *
	 * @param   string   $message      the error message to be returned
	 * @param   string   $code         the error code to be returned
	 * @param   int      $status_code  the http status code to set
	 * @return  response               a json response object
	 */
	public static function error($message, $code, $status_code = 418)
	{
		return response()->json([
			'error' => [
				'message' => $message,
				'code'    => $code,
			]
		], $status_code);
	}

	/**
	 * Parse a set of ids
	 *
	 * @param   string  &$ids   the ids to parse
	 * @return  array[string]   the parsed ids
	 */
	public static function ParseIds(&$ids)
	{
		if (is_array($ids))
		{
			// we have already parsed these ids
			return $ids;
		}

		// check if we have an empty string
		if ($ids === '')
		{
			return $ids = [];
		}

		// check if we need to parse these ids
		if (strpos($ids, ',') === false)
		{
			return $ids = [$ids];
		}

		// remove trailing comma if it exists
		if (substr($ids, strlen($ids) - 1, 1) === ',')
		{
			$ids = substr($ids, 0, strlen($ids) - 1);
		}

		// explode and return the results
		return $ids = explode(',', $ids);
	}

	/**
	 * Handle missed calls to the controller
	 *
	 * @param   string  $method
	 * @param   array   $params
	 * @return  mixed
	 */
	public function __call($method, $params)
	{
		ResponseDocument::flush();

		switch ((int) Auth::user()->privilege)
		{
			case AuthPermission::PERMISSION_USER:
				$type = 'user';
				break;

			case AuthPermission::PERMISSION_HOST:
				$type = 'host';

				Auth::user()->load('user_host');
				if (! ($host = Auth::user()->user_host))
				{
					throw new Exception("User host record is empty");
				}

				break;

			case AuthPermission::PERMISSION_SUPPORT:
				$type = 'support';
				break;

			case AuthPermission::PERMISSION_OPS:
				$type = 'ops';
				break;
		}

		if (empty($type) || (! method_exists($this, $method . '_' . $type)))
		{
			return parent::__call($method, $params);
		}

		return call_user_func_array([$this, $method . '_' . $type], $params);
	}
}
