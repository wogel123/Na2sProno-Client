<?php

namespace App\Controllers;

use App\Mail\Mail;
use App\Model\Subscription;
use App\Model\User;
use DI\Container;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

/**
 * @property Mail $mail
 */
class SubscriptionController extends Controller
{

	public function __construct()
	{

	}

	private function getSubscription($id)
	{
		$url = "https://api-m.sandbox.paypal.com/v1/billing/subscriptions/" . $id;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$headers = array(
			"Authorization: Basic QVRaNFMtaTlRcWRPeTBmWGl1eExOS3ZSNjl3MVYydlNaaFNQSUo4ZV95NXFvaldtSkZneUlVanhzcTZkOExfYjFKM0JjOHhaQnV5V3pielQ6RUVOSFpZWkdJR19zZmZFdkp3eFpsZHJ1Nm9xNjN5QVNZd2M2aUNCZVZIWDJkcXJhN2UyWFcwMmZBNWt2a2U1T2RRVVdWR0NXYklaS19sNlE=",
		);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$resp = curl_exec($curl);

		return json_decode($resp);
	}

	private function postSubscription($id)
	{
		$url = "https://api-m.sandbox.paypal.com/v1/billing/subscriptions/" . $id . "/cancel";

		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$headers = array(
			"Authorization: Basic QVRaNFMtaTlRcWRPeTBmWGl1eExOS3ZSNjl3MVYydlNaaFNQSUo4ZV95NXFvaldtSkZneUlVanhzcTZkOExfYjFKM0JjOHhaQnV5V3pielQ6RUVOSFpZWkdJR19zZmZFdkp3eFpsZHJ1Nm9xNjN5QVNZd2M2aUNCZVZIWDJkcXJhN2UyWFcwMmZBNWt2a2U1T2RRVVdWR0NXYklaS19sNlE=",
			"Content-Type: application/json",
		);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		$data = <<<DATA
				{
				  "reason": "Abonnement annulé"
				}
				DATA;

		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		curl_exec($curl);
		curl_close($curl);

		return $this;

	}

	public function createSubscription(ServerRequest $request, Response $response)
	{
		$data = $request->getParsedBody();

		$req = $this->getSubscription($data['subscriptionid']);
		$next_payment_iso = $req->billing_info->next_billing_time;

		$next_payment = \DateTime::createFromFormat('Y-m-d\\TH:i:s\\Z', $next_payment_iso);
		$next_payment->add(new \DateInterval('P1D'));
		$next_payment->format('Y-m-d');

		Subscription::create([
			'userid' => $data['userid'],
			'subscriptionid' => $data['subscriptionid'],
			'end_at' => $next_payment
		]);

		return $response;
	}

	public function checkUser($id)
	{
		$user = User::where('id', $id)->first();
		if ($user['is_admin']) {
			return true;
		} else {
			$sub = Subscription::where('userid', $id)->first();
			if ($sub) {
				if ($sub['end_at'] <= date('Y-m-d')) {
					$req = $this->getSubscription($sub['subscriptionid']);
					$last_payment_iso = $req->billing_info->last_payment->time;
					$last_payment = \DateTime::createFromFormat('Y-m-d\\TH:i:s\\Z', $last_payment_iso);
					$last_payment->format('Y-m-d');
					$end_at = \DateTime::createFromFormat('Y-m-d', $sub['end_at']);
					$diff = $last_payment->diff($end_at)->format("%a");
					if ($diff > 2) {
						$this->postSubscription($sub['subscriptionid']);
						Subscription::where('userid', $id)->delete();
						return false;
					} else {
						$next_payment_iso = $req->billing_info->next_billing_time;
						$next_payment = \DateTime::createFromFormat('Y-m-d\\TH:i:s\\Z', $next_payment_iso);
						$next_payment->add(new \DateInterval('P1D'));
						$next_payment->format('Y-m-d');
						Subscription::where('userid', $id)->update(['end_at' => $next_payment]);
						return true;
					}
				} else {
					return true;
				}
			} else {
				return false;
			}
		}
	}

	public function listSubscription(ServerRequest $request, Response $response)
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
		$totalRecords = Subscription::select('count() as allcount')->count();
		$totalRecordswithFilter = Subscription::select('count() as allcount')->where('userid', 'like', '%' . $searchValue . '%')->count();

		// Requete
		$records = Subscription::with('user')->skip($start)->take($rowperpage)->get();

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

}