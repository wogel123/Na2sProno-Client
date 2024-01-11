<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
	protected $table = 'subscriptions';
	protected $fillable = ['userid','subscriptionid','created_at','updated_at','end_at'];

	protected $casts = [
		'userid' => 'string'
	];

	public function user()
	{
		return $this->hasOne('App\Model\User', "id", "userid");
	}

}