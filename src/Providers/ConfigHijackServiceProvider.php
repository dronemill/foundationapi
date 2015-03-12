<?php

namespace DroneMill\FoundationApi\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigHijackServiceProvider extends ServiceProvider
{
	public function register()
	{
		$config = $this->app['config'];

		/*if (strpos(\Request::url(), $config->get('web.url')) !== false)
		{
			$config->set('app.request_type', 'web');
		}
		else
		{
			$config->set('app.request_type', 'api');
		}*/

		// some other useful things we can hijack:
		// auth.driver
		// session.driver
	}
}
