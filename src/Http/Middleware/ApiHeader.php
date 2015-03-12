<?php

namespace DroneMill\FoundationApi\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\Middleware;

class ApiHeader implements Middleware {

	/**
	 * Set api specific headers after an api request is executed
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$response = $next($request);

		$response->header('Access-Control-Allow-Origin', '*');
		$response->header('Access-Control-Max-Age', '86400');
		$response->header('Access-Control-Allow-Credentials', 'true');
		$response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE, PUT, PATCH');
		$response->header('Access-Control-Allow-Headers', 'X-DroneMill-Auth,DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,X-Forwarded-For,X-Real-IP,X-Forwarded-For,If-Modified-Since,Cache-Control,Content-Type');

		return $response;
	}
}