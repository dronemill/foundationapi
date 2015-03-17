<?php

namespace DroneMill\FoundationApi\Providers;

use DroneMill\FoundationApi\Auth\Service;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Log;
use Config;

class AuthProvider implements UserProvider
{
	/**
	 *
	 * @var service
	 */
	private $service;

	/**
	 * The Authenticatable model to use
	 *
	 * @var  string
	 */
	private $authenticatableModel = '\App\Models\User';

	/**
	 * The model where the tokens are stored
	 *
	 * @var  string
	 */
	private $tokenModel = '\App\Models\UserToken';

	/**
	 * Fetch and store various auth configs, and instantiate the auth service
	 */
	public function __construct()
	{
		// set the model to use for auth
		$this->authenticatableModel = Config::get('auth.model');
		$this->tokenModel           = Config::get('auth.token_model');

		// instantiate the auth service
		$this->service = new Service($this);
	}

	/**
	 * fetch this providers instance of the Auth SErvice
	 *
	 * @return  DroneMill\FoundationApi\Auth\Service
	 */
	public function getService()
	{
		return $this->service;
	}

	/**
	 * fetch the authenticatable Model
	 *
	 * @return  string
	 */
	public function getAuthenticatableModel()
	{
		return $this->authenticatableModel;
	}

	/**
	 * fetch the token Model
	 *
	 * @return  string
	 */
	public function getTokenModel()
	{
		return $this->tokenModel;
	}

	/**
	 * These two methods are not used, but required the
	 * Illuminate\Contracts\Auth\UserProvider contractual interface
	 */
	public function retrieveByToken($identifier, $token) {}
	public function updateRememberToken(Authenticatable $user, $token) {}

	/**
	 * Retrieve a user by their unique identifier.
	 *
	 * @param  mixed  $id
	 *
	 * @return Authenticatable|false
	 */
	public function retrieveByID($id)
	{
		$user = $this->service->find_user_by_id($id);

		if (! ($user instanceof $this->authenticatableModel))
		{
			Log::info('failed retrieving user by id', ['id' => $id]);
			return false;
		}

		Log::debug('successfully retreived user by id', ['user' => $user->id]);
		return $user;
	}

	/**
	 * Retrieve a user by the given credentials.
	 *
	 * @param  array  $credentials
	 *
	 * @return Authenticatable|new empty authenticatableModel
	 */
	public function retrieveByCredentials(array $credentials)
	{
		// Ensure that we have a token to fetch by
		if (empty($credentials['token']))
		{
			Log::info('failed retrieving user by credentials. Token not present');
			return new $this->authenticatableModel();
		}

		$user = $this->service->find_user_by_token($credentials['token']);

		if (! ($user instanceof $this->authenticatableModel))
		{
			Log::info('failed finding user by token', ['token' => $credentials['token']]);
			return new $this->authenticatableModel();
		}

		Log::debug('successfully retreived user by credentials', ['user' => $user->id]);
		return $user;
	}

	/**
	 * Validate a user against the given credentials.
	 *
	 * @param Authenticatable $user
	 * @param  array  $credentials
	 *
	 * @return bool
	 */
	 public function validateCredentials(Authenticatable $user, array $credentials)
	 {
		$validated = $this->service->validate_user_credentials($user, $credentials);

		if (! $validated)
		{
			Log::info('failed validating credentials', ['user' => $user->id]);
		}
		else
		{
			Log::debug('successfully validated credentials', ['user' => $user->id]);
		}

		return $validated;
	 }
}
