<?php

namespace DroneMill\FoundationApi\Auth;

use Illuminate\Database\Eloquent\Collection as Collection;
use Log;
use User;
use DroneMill\FoundationApi\Providers\AuthProvider;


// TODO: implement a contract here
class Service
{
	protected $user;
	protected $provider;

	public function __construct(AuthProvider $provider)
	{
		$this->provider = $provider;
	}

	public function find_user_by_token($authToken)
	{
		$token = forward_static_call_array([$this->provider->tokenModel, 'find'], [$authToken]);

		if (! ($token instanceof $this->provider->tokenModel))
		{
			Log::info('failed finding user by token', ['token' => $authToken]);
			return false;
		}

		$this->user = $token->user;

		Log::debug('found user by token', ['token' => $authToken, 'user_id' => $this->user->id]);

		return $this->user;
	}

	public function find_user_by_id($id)
	{
		$user = forward_static_call_array([$this->provider->authenticatableModel, 'find'], [$id]);

		if (! ($user instanceof $this->provider->authenticatableModel))
		{
			Log::info('failed finding user by id', ['id' => $id]);
			return false;
		}
		$this->user = $user;
		Log::debug('found user by id', ['user' => $this->user->id]);

		return $this->user;
	}

	public function validate_user_credentials($user, $credentials)
	{
		// load the user_tokens if we havent already
		$user->load('user_tokens');

		if (empty($user->user_tokens))
		{
			Log::info('empty user token', ['user' => $user->id]);
			return false;
		}

		if (! ($user->user_tokens instanceof Collection))
		{
			Log::info('user token is not an instance of Collection', ['user' => $user->id]);
			return false;
		}

		return $user->user_tokens->contains($credentials['token']);
	}
}