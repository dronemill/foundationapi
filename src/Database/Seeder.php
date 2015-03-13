<?php

namespace DroneMill\FoundationApi\Database;

use App;
use Exception;

abstract class Seeder extends \Illuminate\Database\Seeder {

	/**
	 * Truncate a table if we we are testing or in a local env.
	 * If we arent using a sqlite driver, then truncate as normal.
	 * But if we are, then check if we are truncating a non-incrementing
	 * pk, and if so, then fetch and delete all records.
	 *
	 * @param  string  $table  the table to truncate
	 */
	public function SmartTruncate($table)
	{
		// globalize the table name
		if (substr($table, 0, 1) !== '\\')
		{
			$table = '\\' . $table;
		}

		// if we arent in a testing env, then just truncate as normal
		if (App::environment() !== 'testing')
		{
			// Saftey check
			if (App::environment() !== 'local')
			{
				throw new Exception("UNSAFE: not truncating in non testing or local environment");
			}

			$table::truncate();
			return;
		}

		// we need to get information about this table to proceed
		$t = new $table;

		// if we are not using a sqlite driver, then truncate as normal
		if ($t->getConnection()->getConfig('driver') !== 'sqlite')
		{
			$table::truncate();
			return;
		}

		// truncate if table is incrementing
		if ($t->getIncrementing())
		{
			$table::truncate();
			return;
		}

		// get all the records
		$table::all()->each(function($model)
		{
			$model->delete();
		});
	}

}
