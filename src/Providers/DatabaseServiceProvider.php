<?php

namespace DroneMill\FoundationApi\Providers;

use DroneMill\FoundationApi\Database\Manager;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseServiceProvider as DatabaseBaseServiceProvider;
use Illuminate\Database\Eloquent\Model;

class DatabaseServiceProvider extends DatabaseBaseServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerQueueableEntityResolver();

		// The connection factory is used to create the actual connection instances on
		// the database. We will inject the factory into the manager so that it may
		// make the connections while they are actually needed and not of before.
		$this->app->singleton('db.factory', function($app)
		{
			return new ConnectionFactory($app);
		});

		// The database manager is used to resolve various connections, since multiple
		// connections might be managed. It also implements the connection resolver
		// interface which may be used by other components requiring connections.
		$this->app->singleton('db', function($app)
		{
			return new Manager($app, $app['db.factory']);
		});
	}

}
