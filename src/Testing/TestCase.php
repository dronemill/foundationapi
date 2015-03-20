<?php

namespace DroneMill\FoundationApi\Testing;

use Artisan;
use FactoryMuffin;
use File;
use Mockery;
use Clousure;


/**
 * A TestCase Foundation
 */
abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase {

	/**
	 * Our FactoryMuffin instance
	 *
	 * @var  League\FactoryMuffin\FactoryMuffin
	 */
	protected static $factoryMuffin = null;

	/**
	 * Track if we have seeded the database or not
	 *
	 * @var  boolean
	 */
	protected $seeded = false;

	/**
	 * Track if we have run migrations on the db
	 *
	 * @var  boolean
	 */
	protected $migrated = false;

	/**
	 * Bootstrap the testing environment
	 *
	 * @return void
	 */
	public static function setupBeforeClass()
	{
		/**
		 * If the class 'App' does not exist, then that means that we are
		 * the very first test class being instantiated, and we need to
		 * bootstrap the application so that the AliasLoader is registered
		 */
		if (! class_exists('App'))
		{
			$app = new Static;
			$app->refreshApplication();
		}

		// create our FactoryMuffin instance
		self::$factoryMuffin = new FactoryMuffin();

		// loud the factory definitions we have declared
		self::$factoryMuffin->loadFactories(static::FACTORIES_PATH);
	}

	/**
	 * This is the setUp method run before each test method
	 *
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();

		// reset model events
		static::resetModelEvents();

		// if we do not have a migrated DB, then migrate
		if (! $this->migrated)
		{
			$this->migrateUp();
			$this->migrated = true;
		}

	}

	/**
	 * Seed the db, and track that we did so
	 *
	 * @param  string  $class
	 * @return void
	 */
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
			// fetch a listing of all the files in the given model path
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

	/**
	 * Extraneous cleanup routines to be run after each test method
	 */
	public function cleanup()
	{

	}

	/**
	 * Tear down and cleanup after a test case method
	 */
	public function tearDown()
	{
		// stop mocking any classes we are currently mocking
		Mockery::close();

		// delete models that we have saved to the db
		// note: this happens in the reverse order as they were created
		self::$factoryMuffin->deleteSaved();

		// run any extraneous cleanup routines we have
		$this->cleanup();

		// if we seeded the db, then migrate down, thus unseeding the db
		if ($this->seeded)
		{
			$this->migrateDown();
			$this->seeded = false;
			$this->migrated = false;
		}

		parent::tearDown();
	}

	/**
	 * This is run to tear down a test calss after all test methods have been executed
	 */
	public static function tearDownAfterClass()
	{
		parent::tearDownAfterClass();
	}

	/**
	 * Assert that multiple attribute/value pairs are set in the given
	 *
	 * @param   []StdClass $objects
	 * @param   Array      $checks
	 */
	public function assertAttributesValueInArrayOfObjects(Array $objects, Array $checks)
	{
		foreach ($checks as $check)
		{
			$this->assertAttributeValueInArrayOfObjects($objects, key($check), $check[key($check)]);
		}
	}

	/**
	 * Assert that there is an attrabute/value pair in atleast one of the given objects
	 *
	 * @param   []StdClass $objects
	 * @param   string     $attribute
	 * @param   mixed      $value
	 * @return  bool
	 */
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
