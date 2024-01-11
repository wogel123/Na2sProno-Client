<?php

namespace App\Controllers;

use Noweh\TwitterApi\Client;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Slim\Views\Twig;

/**
 * @property Twig $view
 */
class HomeController extends Controller
{
	public function __invoke(ServerRequest $request, Response $response)
	{
		$this->view->render($response, 'pages/home.twig');

		return $response;
	}

	public function error404(ServerRequest $request, Response $response)
	{
		$this->view->render($response, 'pages/error404.twig');

		return $response;
	}

	public function contact(ServerRequest $request, Response $response)
	{
		$this->view->render($response, 'pages/contact.twig');

		return $response;
	}

	public function design(ServerRequest $request, Response $response)
	{
		$this->view->render($response, 'pages/design.twig');

		return $response;
	}



}