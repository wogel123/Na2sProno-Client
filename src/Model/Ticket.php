<?php

namespace App\Model;

class Ticket extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'tickets';
	protected $fillable = ['id','odd','type'];

	protected $casts = [
		'id' => 'string'
	];

	public function match()
	{
		return $this->hasMany('App\Model\Match', "ticketid", "id");
	}

	public function prono()
	{
		return $this->hasMany('App\Model\Prono', "matchid", "id");
	}

}