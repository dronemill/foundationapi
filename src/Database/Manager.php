<?php

namespace DroneMill\FoundationApi\Database;

use Config;
use DroneMill\FoundationApi\Models\DbResolverConnection;
use DroneMill\FoundationApi\Models\DbResolverConnectionHost;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;

class Manager extends DatabaseManager {

	/**
	 * Get the configuration for a connection.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function getConfig($name)
	{
		$name = $name ?: $this->getDefaultConnection();

		// To get the database connection configuration, we will just pull each of the
		// connection configurations and get the configurations for the given name.
		// If the configuration doesn't exist, we'll attempt to fetch it
		$connections = $this->app['config']['database.connections'];

		$config = array_get($connections, $name);

		if ($config === null)
		{
			return $this->fetchConfig($name);
		}

		return $config;
	}


	/**
	 * Attempt to fetch the config from the server.db_connection table
	 *
	 * @param  string  $name
	 * @return array
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function fetchConfig($name)
	{
		$name = $name ?: $this->getDefaultConnection();

		// ensure that the resolver connection is set in the db config
		$resolverConnection = Config::get('database.resolver.connection');
		if (empty($this->app['config']['database.connections'][$resolverConnection]))
		{
			throw new InvalidArgumentException("Resolver connection [$resolverConnection] is not configured");
		}

		// attempt to fetch the config for the given connection name
		$DbResolverConnection = new DbResolverConnection;
		$DbResolverConnection->setConnection(Config::get('database.resolver.connection'));
		$DbResolverConnection->setTable(Config::get('database.resolver.table.connection'));

		$fetch = $DbResolverConnection->newQuery()->find($name);

		// if we didnt fetch any configs, then bail
		if (is_null($fetch))
		{
			throw new InvalidArgumentException("Database connection [$name] not configured.");
		}

		$config = [
			'driver'    => $fetch->driver,
			'database'  => $fetch->database,
			'username'  => $fetch->username,
			'password'  => $fetch->password,
			'charset'   => $fetch->charset,
			'collation' => $fetch->collation,
			'prefix'    => $fetch->prefix,
		];

		if ($fetch->driver === 'mysql' && $fetch->db_connection_hosts)
		{
			$config['read'] = [];
			$config['write'] = [];

			foreach ($fetch->db_connection_hosts as $host)
			{
				if ($host->writable)
					$config['write'][] = ['host' => $host->host];
				else
					$config['read'][] = ['host' => $host->host];
			}

			if (empty($config['write']))
				throw new InvalidArgumentException('No hosts configured');

			if (empty($config['read']))
				$config['read'] = $config['write'];
		}

		$this->app['config']->set('database.connections.' . $name, $config);

		return $this->app['config']['database.connections'][$name];
	}

}
