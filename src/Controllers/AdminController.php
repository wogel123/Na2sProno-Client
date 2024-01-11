<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Model\Ticket;
use App\Model\User;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;
use function DI\get;

/**
 * @property Twig $view
 * @property Auth $auth
 */

class AdminController extends Controller
{
	public function homeView(ServerRequest $request, Response $response)
	{

		$this->view->render($response, '/admin/pronoindex.twig');

		return $response;
	}

	public function statsView(ServerRequest $request, Response $response)
	{

		$this->view->render($response, '/admin/stats.twig');

		return $response;
	}

	public function pronosView(ServerRequest $request, Response $response)
	{

		$this->view->render($response, '/admin/pronos.twig');

		return $response;
	}

	public function pronolistView(ServerRequest $request, Response $response)
	{
		$this->view->render($response, '/admin/pronolist.twig');

		return $response;
	}

	public function subscriptionsView(ServerRequest $request, Response $response)
	{
		$this->view->render($response, '/admin/subscriptions.twig');

		return $response;
	}

	public function pronoeditView(ServerRequest $request, Response $response)
	{

		$routeContext = RouteContext::fromRequest($request);
		$route = $routeContext->getRoute();

		$id = $route->getArgument('id');

		$ticket = Ticket::with('match.prono')->where('id', $id)->first();

		$this->view->render($response, '/admin/prono.twig', ['id' => $id, 'ticket' => $ticket]);

		return $response;
	}

	public function membersView(ServerRequest $request, Response $response)
	{

		$this->view->render($response, '/admin/members.twig');


		return $response;
	}

	public function editMember(ServerRequest $request, Response $response)
	{

		$routeContext = RouteContext::fromRequest($request);
		$route = $routeContext->getRoute();

		$id = $route->getArgument('id');

		$member = User::where('id', $id)->first();

		$this->view->render($response, '/admin/member.twig', ['member' => $member]);

		return $response;
	}

}