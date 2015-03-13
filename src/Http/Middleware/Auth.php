<?php

namespace DroneMill\FoundationApi\Http\Middleware;

use Closure;
use DroneMill\FoundationApi\Providers\AuthProvider;
use Illuminate\Auth\Guard;
use Illuminate\Contracts\Routing\Middleware;

class Auth implements Middleware {

	/**
	 * Set api specific headers after an api request is executed
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		\Auth::extend('FoundationApi', function($app)
		{
			$provider =  new AuthProvider();

			return new Guard($provider, $app['session.store']);
		});

		return $next($request);
	}
}