<?php

namespace DroneMill\FoundationApi\Providers;

use Illuminate\Support\ServiceProvider;
use DroneMill\FoundationApi\Response\Document;

class ResponseDocumentServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('DroneMill\FroundationApi\Contracts\Response\Document', function($app)
		{
			return new Document($app['config']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['DroneMill\FroundationApi\Response\Document'];
	}

}
