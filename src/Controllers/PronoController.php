<?php

namespace App\Controllers;

use App\Model\Match;
use App\Model\Prono;
use App\Model\Ticket;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

/**
 * @property Twig $view
 */
class PronoController extends Controller
{
	public function dailyPronoView(ServerRequest $request, Response $response)
	{

		$tickets = Ticket::with('match.prono')->whereDay('created_at', date('d'))->get();

		$this->view->render($response, '/pages/daily-prono.twig', ['tickets' => $tickets]);

		return $response;
	}

	public function pronoView(ServerRequest $request, Response $response)
	{
		$routeContext = RouteContext::fromRequest($request);
		$route = $routeContext->getRoute();

		$id = $route->getArgument('id');
		$ticket = Ticket::with('match.prono')->where('id', $id)->first();

		$this->view->render($response, '/pages/prono.twig', ['ticket' => $ticket]);

		return $response;
	}

	public function archiveView(ServerRequest $request, Response $response)
	{
		$this->view->render($response, '/pages/archive.twig');

		return $response;
	}

	public function submitProno(ServerRequest $request, Response $response)
	{
		$data = (array)$request->getParsedBody();

		$ticket = new Ticket();
		$ticket->odd = $data['odd'];
		$ticket->type = $data['type'];
		$ticket->save();

		foreach ($data['matchData'] as $item) {
			$match = new Match();

			$date = \DateTime::createFromFormat('d/m/Y H:i', $item['time'])->format('Y-m-d H:i:s');
			$match->time = $date;

			$match->team1 = $item['team1'];
			$match->team2 = $item['team2'];
			$match->ticketid = $ticket['id'];
			$match->save();

			foreach ($item['prono'] as $item) {
				$prono = new Prono();
				$prono->type = $item['type'];
				$prono->prono = $item['prono'];
				$prono->odd = $item['odd'];
				$prono->matchid = $match['id'];
				$prono->save();
			}
		}

		return $response->withJson(['error' => false, 'message' => 'Votre pronostic a bien été enregistré']);

	}


	public function listProno(ServerRequest $request, Response $response, $args = [])
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
		$totalRecords = Ticket::select('count() as allcount')->count();
		$totalRecordswithFilter = Ticket::select('count() as allcount')->where('type', 'like', '%' . $searchValue . '%')->count();

		// Requete
		$records = Ticket::with('match.prono')->skip($start)->take($rowperpage)->orderBy('created_at', 'DESC')->get();

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

	public function editState(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		$id = $data['id'];
		$state = $data['state'];
		Ticket::where('id', $id)->update(['state' => $state]);

		return $response->withJson(['error' => false, 'message' => 'Le statut a bien été mis à jour']);
	}

	public function editMatch(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		$id = $data['id'];
		$team1 = $data['team1'];
		$team2 = $data['team2'];
		$time = \DateTime::createFromFormat('d/m/Y H:i', $data['time'])->format('Y-m-d H:i:s');
		Match::where('id', $id)->update(['team1' => $team1, 'team2' => $team2, 'time' => $time]);

		return $response->withJson(['error' => false, 'message' => 'Le match a bien été mis à jour']);
	}

	public function editProno(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		$id = $data['id'];
		$type = $data['type'];
		$prono = $data['prono'];
		$odd = $data['odd'];
		Prono::where('id', $id)->update(['type' => $type, 'prono' => $prono, 'odd' => $odd]);

		return $response->withJson(['error' => false, 'message' => 'Le pronostic a bien été mis à jour']);
	}

	public function edit(ServerRequest $request, Response $response)
	{
		$routeContext = RouteContext::fromRequest($request);
		$route = $routeContext->getRoute();
		$field = $route->getArgument('field');
		$data = $request->getParsedBody();
		Ticket::where('id',$data['id'])->update([$field => $data['field']]);

		return $response->withJson(['error' => false, 'message' => 'Le ticket a bien été mis à jour']);
	}

	public function deleteTicket(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		Ticket::where('id',$data['id'])->delete();

		return $response->withJson(['error' => false]);
	}

	public function deleteMatch(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		Match::where('id',$data['id'])->delete();

		return $response->withJson(['error' => false, 'message' => 'Le match a bien été supprimé']);
	}

	public function deleteProno(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		Prono::where('id',$data['id'])->delete();

		return $response->withJson(['error' => false, 'message' => 'Le prono a bien été supprimé']);
	}

	public function getTicket(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();
		$ticket = Ticket::where('id',$data['id'])->first();

		return $response->withJson(['error' => false, 'ticket' => $ticket]);
	}

}