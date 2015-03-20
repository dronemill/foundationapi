<?php

namespace DroneMill\FoundationApi\Testing;

use Artisan;
use FactoryMuffin;
use File;
use Mockery;
use Clousure;

// TODO: Comment this class

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase {

	protected static $factoryMuffin = null;

	protected $seeded = false;
	protected $migrated = false;

	public static function setupBeforeClass()
	{
		if (! class_exists('App'))
		{
			$app = new Static;
			$app->createApplication();
		}

		self::$factoryMuffin = new FactoryMuffin();
		self::$factoryMuffin->loadFactories(static::FACTORIES_PATH);
	}

	public function setUp()
	{
		parent::setUp();

		static::resetModelEvents();

		// If we do not have a migrated DB, then migrate
		if (! $this->migrated)
		{
			$this->migrateUp();
			$this->migrated = true;
		}

	}

	public function seed($class = 'DatabaseSeeder')
	{
		$this->seeded = true;

		parent::seed();
	}

	/**
	 * Reset model events
	 *
	 * There is currently a bug in Laravel laravel#1181 that prevents model events from firing
	 * more than once in a test suite. This means that the first test that uses model tests
	 * will pass but any subseqeuent tests will fail. There are a couple of temporary solutions
	 * listed in that thread which you can use to make your tests pass in the meantime.
	 *
	 * https://github.com/laravel/framework/issues/1181
	 * https://github.com/dwightwatson/validating/commit/b7e1918de4adb6000764372695f32ff91cf07df8
	 *
	 * @return  void
	 */
	public static function resetModelEvents($pathToModels = [])
	{
		foreach ($pathToModels as $path)
		{
			$files = File::files($path['path']);

			// Remove the directory name and the .php from the filename
			$files = str_replace($path['path'].'/', '', $files);
			$files = str_replace('.php', '', $files);

			// if(($key = array_search('AModelThatWeDontWantToBoot', $files)) !== false) {
			// unset($files[$key]);
			// }

			// Reset each model event listeners.
			foreach ($files as $model) {

				// Flush any existing listeners.
				call_user_func(array($path['namespace'] . '\\' . $model, 'flushEventListeners'));

				// Reregister them.
				call_user_func(array($path['namespace'] . '\\' . $model, 'boot'));
			}
		}
	}

	public function cleanup()
	{

	}

	public function tearDown()
	{
		Mockery::close();

		self::$factoryMuffin->deleteSaved();

		$this->cleanup();

		// if we seeded the db, then migrate down
		if ($this->seeded)
		{
			$this->migrateDown();
			$this->seeded = false;
			$this->migrated = false;
		}

		parent::tearDown();
	}

	public static function tearDownAfterClass()
	{
		parent::tearDownAfterClass();
	}

	public function assertAttributesValueInArrayOfObjects(Array $objects, Array $checks)
	{
		foreach ($checks as $check)
		{
			$this->assertAttributeValueInArrayOfObjects($objects, key($check), $check[key($check)]);
		}
	}

	public function assertAttributeValueInArrayOfObjects(Array $objects, $attribute, $value)
	{
		foreach ($objects as $object)
		{
			if (! isset($object->$attribute))
			{
				$this->fail('attribute \'' . $attribute . '\' does not exist');
			}

			if ($object->$attribute === $value)
			{
				return true;
			}
		}

		$this->fail('unmatched value of attribute \'' . $attribute . '\'');
	}

	/**
	* Determine if two associative arrays are similar
	*
	* Both arrays must have the same indexes with identical values
	* without respect to key ordering
	*
	* @param array $a
	* @param array $b
	* @return bool
	*/
	public function assertArraysAreSimilar($a, $b)
	{
		foreach($a as $k => $v)
		{
			if (is_array($v))
			{
				$this->assertArraysAreSimilar($v, $b[$k]);
			}

			$this->assertSame($v, $b[$k]);
		}
	}

}
