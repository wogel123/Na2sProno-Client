<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Mail\Mail;
use App\Model\Subscription;
use App\Model\User;
use App\Model\UserReset;
use App\Model\UserToken;
use Selective\Config\Configuration;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;
use Valitron\Validator;
use function DI\get;

/**
 * @property Twig $view
 * @property Mail $mail
 * @property Auth $auth
 */
class AccountController extends Controller
{

	public function registerView(ServerRequest $request, Response $response)
	{
		$this->view->render($response, '/pages/register.twig');

		return $response;
	}

	public function loginView(ServerRequest $request, Response $response)
	{

		if ($this->auth->logged) {
			$this->view->render($response, '/pages/error404.twig');
		} else {
			$this->view->render($response, '/pages/login.twig');
		}

		return $response;
	}

	public function resetPasswordView(ServerRequest $request, Response $response)
	{

		if ($this->auth->logged) {
			$this->view->render($response, '/pages/error404.twig');
		} else {
			$params = $request->getQueryParams();
			$token = $params['token'];
			$userid = $params['userid'];

			if ((isset($token) && !empty($token)) && (isset($userid) && !empty($userid))) {
				$user_reset = UserReset::where('token', $token)->first();
				if (new \DateTime() > new \DateTime($user_reset['expiration'])) {
					$args = ['expired' => true];
				} else {
					$args = ['expired' => false];
				}
				$this->view->render($response, '/pages/reset-password.twig', $args);
			} else {
				$this->view->render($response, '/pages/error404.twig');
			}
		}

		return $response;
	}

	public function forgetPasswordView(ServerRequest $request, Response $response)
	{

		if ($this->auth->logged) {
			$this->view->render($response, '/pages/error404.twig');
		} else {
			$this->view->render($response, '/pages/forget-password.twig');
		}

		return $response;
	}

	public function checkLogin(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		$auth = new Auth($this->container->get(Configuration::class)->getString('auth.token_key'));

		return $response->withJson($auth->checkUser($data['email'], $data['password']));
	}

	public function logout(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		$result['state'] = true;
		$auth = new Auth($this->container->get(Configuration::class)->getString('auth.token_key'));
		$auth->destroyToken($data['token']);
		return $response->withJson($result);
	}

	public function verifyAccount(ServerRequest $request, Response $response)
	{
		$data = (array)$request->getParsedBody();

		$user = User::where('id', $data['userid'])->first();

		if (new \DateTime() > new \DateTime($user['verified_expires'])) {
			return $response->withJson(['error' => true, 'message' => ['e' => 'Votre code a expiré. Veuillez demander un nouveau code par email.']]);
		} else {
			if ($user['verified_code'] == $data['code']) {
				User::where('id', $data['userid'])->update(['verified' => 1]);
				return $response->withJson(['error' => false]);
			} else {
				return $response->withJson(['error' => true, 'message' => ['e' => 'Votre code n\'est pas valide.']]);
			}
		}
	}

	public function resendVerification(ServerRequest $request, Response $response)
	{
		$data = (array)$request->getParsedBody();
		$user = User::where('id', $data['userid'])->first();

		$code = str_pad(random_int(0, 999999), 6, 0, STR_PAD_LEFT);
		$code_expires = (new \DateTime())->add(new \DateInterval('PT24H'))->format('Y-m-d H:i:s');

		User::where('id', $data['userid'])->update(['verified_code' => $code, 'verified_expires' => $code_expires]);

		$this->mail->sendVerifMail($user['email'],$code);

		return $response->withJson(['error' => false, 'message' => 'Un nouveau code vous a été envoyé par email.']);
	}

	public function updateEmail(ServerRequest $request, Response $response)
	{
		$params = (array)$request->getParsedBody();

		$v = new Validator([
			"email" => $params['email']
		]);

		$v->stopOnFirstFail();

		$v->rule('required', "email")->message("Vous devez rentrer un email");
		$v->rule('email', "email")->message("Votre email n'est pas valide");

		if ($v->validate()) {

			$code = str_pad(random_int(0, 999999), 6, 0, STR_PAD_LEFT);
			$code_expires = (new \DateTime())->add(new \DateInterval('PT24H'))->format('Y-m-d H:i:s');

			User::where('id', $params['id'])->update(['email' => $params['email'], 'verified' => 0, 'verified_code' => $code, 'verified_expires' => $code_expires]);

			$this->mail->sendVerifMail($params['email'],$code);

			return $response->withJson(['error' => false, 'email' => $params['email']]);
		} else {
			return $response->withJson(['error' => true, 'message' => $v->errors()]);
		}
	}

	public function updatePassword(ServerRequest $request, Response $response)
	{
		$params = (array)$request->getParsedBody();

		$v = new Validator([
			"password" => $params['password'],
			"passwordConfirm" => $params['passwordConfirm']
		]);

		$v->stopOnFirstFail();

		$v->rule('equals', "password", "passwordConfirm")->message("Les mots de passe ne correspondent pas");

		if ($v->validate()) {
			User::where('id', $params['id'])->update(['password' => password_hash($params['password'], PASSWORD_DEFAULT)]);

			return $response->withJson(['error' => false]);
		} else {
			return $response->withJson(['error' => true, 'message' => $v->errors(), 'data' => $params]);
		}


	}

	public function createAccount(ServerRequest $request, Response $response)
	{
		$params = (array)$request->getParsedBody();

		$v = new Validator([
			"username" => $params['username'],
			"email" => $params['email'],
			"password" => $params['password'],
			"passwordConfirm" => $params['passwordConfirm']
		]);

		$v->stopOnFirstFail();

		$v->rule('required', "username")->message("Vous devez rentrer un nom d'utilisateur");
		$v->rule('required', "email")->message("Vous devez rentrer un email");
		$v->rule('required', "password")->message("Vous devez rentrer un mot de passe");
		$v->rule('email', "email")->message("Votre email n'est pas valide");
		$v->rule('equals', "password", "passwordConfirm")->message("Les mots de passe ne correspondent pas");

		if ($v->validate()) {

			$id = uniqid("", true);
			$code = str_pad(random_int(0, 999999), 6, 0, STR_PAD_LEFT);
			$code_expires = (new \DateTime())->add(new \DateInterval('PT24H'))->format('Y-m-d H:i:s');

			User::create([
				"id" => $id,
				"username" => $params['username'],
				"email" => $params['email'],
				"password" => password_hash($params['password'], PASSWORD_DEFAULT),
				"verified_code" => $code,
				"verified_expires" => $code_expires,
			]);

			$this->mail->sendVerifMail($params['email'],$code);
			(new Auth($this->container->get(Configuration::class)->getString('auth.token_key')))->generateToken($id);

			return $response->withJson(['error' => false, 'userid' => $id, 'email' => $params['email']]);
		} else {
			return $response->withJson(['error' => true, 'message' => $v->errors(), 'data' => $params]);
		}
	}

	public function listMembers(ServerRequest $request, Response $response)
	{
		$body = $request->getParsedBody();
		$draw = $body['draw'];
		$start = $body["start"];
		$rowperpage = $body['length']; // Element par page

		$columnIndex_arr = $body['order'];
		$columnName_arr = $body['columns'];
		$order_arr = $body['order'];
		$search_arr = $body['search'];

		$columnIndex = $columnIndex_arr[0]['column']; // Index colonne
		$columnName = $columnName_arr[$columnIndex]['data']; // Nom de colonne
		$columnSortOrder = $order_arr[0]['dir']; // Ordre
		$searchValue = $search_arr['value']; // Recherche

		// Nombres d'éléments
		$totalRecords = User::select('count() as allcount')->count();
		$totalRecordswithFilter = User::select('count() as allcount')->where('username', 'like', '%' . $searchValue . '%')->count();

		// Requete
		$records = User::orderBy($columnName, $columnSortOrder)
			->with('subscription')
			->where('username', 'like', '%' . $searchValue . '%')
			->orWhere('email', 'like', '%' . $searchValue . '%')
			->select('username', 'email', 'created_at', 'verified', 'id')
			->skip($start)
			->take($rowperpage)
			->get();

		$data_arr = array();

		//Affichage
		foreach ($records as $record) {
			$itemDataArr = array();
			foreach ($columnName_arr as $column)
				$itemDataArr[$column['name']] = $record[$column['name']];
			$data_arr[] = $itemDataArr;
		}

		//Résultat
		$result = array(
			"draw" => intval($draw),
			"recordsTotal" => $totalRecords,
			"recordsFiltered" => $totalRecordswithFilter,
			"data" => $data_arr
		);




		return $response->withJson($result);
	}

	public function edit(ServerRequest $request, Response $response)
	{
		$routeContext = RouteContext::fromRequest($request);
		$route = $routeContext->getRoute();
		$field = $route->getArgument('field');
		$data = $request->getParsedBody();

		if ($field == "password") {
			$update['field'] = password_hash($data['field'], PASSWORD_DEFAULT);
		} else {
			$update = $data;
		}

		User::where('id',$data['id'])->update([$field => $update['field']]);

		return $response->withJson(['error' => false, 'message' => 'Le membre a bien été mis à jour']);
	}


	public function deleteMember(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		User::where('id',$data['id'])->delete();

		return $response->withJson(['error' => false]);
	}

	public function sendPasswordReset(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		$user = User::where('email', $data['email'])->first();

		if(isset($user)) {
			$token = md5(uniqid(rand(), true));
			$expiration = (new \DateTime())->add(new \DateInterval('PT24H'))->format('Y-m-d H:i:s');
			UserReset::create([
				'userid' => $user['id'],
				'token' => $token,
				'expiration' => $expiration
			]);
			$this->mail->sendPasswordMail($token,$user['id'],$user['email']);
			return $response->withJson(['error' => false, 'message' => 'Un mail vous a été envoyé afin de réinitialiser votre mot de passe.']);
		} else {
			return $response->withJson(['error' => true, 'message' => 'Aucun compte ne correspond à cette adresse email']);
		}

	}

	public function resetPassword(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		$v = new Validator([
			"password" => $data['password'],
			"passwordConfirm" => $data['passwordConfirm']
		]);

		$v->stopOnFirstFail();

		$v->rule('equals', "password", "passwordConfirm")->message("Les mots de passe ne correspondent pas");

		$user_reset = UserReset::where('token', $data['token'])->first();

		if($user_reset) {
			if ($v->validate()) {
				User::where('id', $data['userid'])->update(['password' => password_hash($data['password'], PASSWORD_DEFAULT)]);
				UserReset::where('token', $data['token'])->delete();
				return $response->withJson(['error' => false, 'message' => 'Votre mot de passe a bien été mis à jour. Vous pouvez maintenant vous connecter à l\'aide de votre nouveau mot de passe.']);
			} else {
				return $response->withJson(['error' => true, 'message' => 'Les mots de passe ne correspondent pas']);
			}
		} else {
			return $response->withJson(['error' => true, 'message' => 'Une erreur est survenue. Vérifiez qu\'il s\'agisse bien du lien que vous avez reçu par email.']);
		}
	}

	public function logoutUser(ServerRequest $request, Response $response, $args = [])
	{
		UserToken::where('timestamp', '<=', date("Y-m-d H:i:s", strtotime("-1 day")))
			->update(['expired' => 1]);

		return $response->withJson(['status' => 'ok']);
	}

}