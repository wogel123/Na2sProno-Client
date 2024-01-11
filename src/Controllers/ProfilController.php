<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Model\Subscription;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Slim\Views\Twig;

/**
 * @property Twig $view
 * @property Auth $auth
 */
class ProfilController extends Controller
{
	public function profilView(ServerRequest $request, Response $response)
	{

		if ($this->auth->logged) {
			$user = $this->auth->user;

			$sub = Subscription::where('userid', $user['id'])->first();

			$args = [
				'state' => $sub['state'],
				'start' => $sub['subscribed_at'],
				'end'	=> $sub['end'],
			];

			$this->view->render($response, 'pages/account.twig', $args);
		} else {
			$this->view->render($response, 'pages/error404.twig');
		}


		return $response;
	}
}