<?php

namespace DroneMill\FoundationApi\Auth;

use Illuminate\Database\Eloquent\Collection as Collection;
use Log;
use User;

// TODO: implement a contract here
class Service
{
	protected $user;
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	public function find_user_by_token($authToken)
	{
		$tokenModel = $this->provider->getTokenModel();
		$token = forward_static_call_array([$tokenModel, 'find'], [$authToken]);

		if (! ($token instanceof $tokenModel))
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
		$authenticatableModel = $this->provider->getAuthenticatableModel();
		$user = forward_static_call_array([$authenticatableModel, 'find'], [$id]);

		if (! ($user instanceof $authenticatableModel))
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
