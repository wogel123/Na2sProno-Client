<?php

namespace App\Auth;

use App\Controllers\SubscriptionController;
use App\Lib\JWT\JsonWebToken;
use App\Lib\Utils\RandomString;
use App\Model\User;
use App\Model\UserToken;

class Auth
{
	private $token_key;

	public $logged = false;

	public $user;

	public $subscribed;

	public function __construct($token_key)
	{
		$this->token_key = $token_key;
	}

	public function getById(string $userId)
	{
		$this->user = User::where("id", $userId)->with('subscription')->first();
		$this->subscribed = (new SubscriptionController())->checkUser($userId);
	}

	public function checkUser($email, $password)
	{
		$user = User::where('email', $email)->with('subscription')->first();

		if ($user == null)
			$return = ['error' => true, 'message' => 'L\'utilisateur est inexistant'];

		else if (!password_verify($password, $user['password'])) {
			$return = ['error' => true, 'message' => 'Le mot de passe est incorrect'];

		} else {
			$token      = $this->generateToken($user['id']);
			$return     = ['error' => false, 'message' => 'Connexion réussie. Vous allez être redirigé ...'];
			$this->user = $user;
			$this->subscribed = (new SubscriptionController())->checkUser($user['id']);
		}

		$return['user'] = $user;
		return $return;
	}

	public function generateToken($id)
	{
		$token_id = RandomString::generate('nozero', 32);
		$token = JsonWebToken::encode(['token_id' => $token_id, 'user_id' => $id], $this->token_key);

		UserToken::create([
			'tokenid' => $token_id,
			'userid' => $id
		]);

		setcookie('user_token', $token, time() + 60 * 60 * 24 * 90, '/', 'dev.wogel123.fr', true, false);

		return $token;
	}

	public function checkToken($token)
	{
		$token  = JsonWebToken::decode($token, $this->token_key);
		$_token = UserToken::where('tokenid', $token['token_id'])->first();

		$this->logged = ($_token && $_token['expired'] == 0);

		if ($this->logged) {
			$this->getById($_token['userid']);
		}

		return $this;
	}

	public function destroyToken($token)
	{
		$_token = JsonWebToken::decode($token, $this->token_key);

		UserToken::where('tokenid', $_token['token_id'])->update(["expired" => 1]);

		setcookie('user_token', "bye", time(), '/', 'dev.wogel123.fr', true, false);
	}

}