<?php

namespace DroneMill\FoundationApi\Providers;

use Auth;
use DroneMill\FoundationApi\Auth\Provider;
use Illuminate\Auth\Guard;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
	/**
	 * Install the FoundationApi auth driver
	 *
	 * @return void
	 */
	public function boot()
	{
		Auth::extend('FoundationApi', function($app)
		{
			$provider =  new Provider();

			return new Guard($provider, $app['session.store']);
		});
	}

	public function register()
	{

	}
}
