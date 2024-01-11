<?php

namespace App\Model;

class Match extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'matchs';
	protected $fillable = ['id','time','team1','team2','ticketid'];

	public $timestamps = false;

	protected $casts = [
		'id' => 'string',
		'ticketid' => 'string'
	];


	public function prono()
	{
		return $this->hasMany('App\Model\Prono', "matchid", "id");
	}
}