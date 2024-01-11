<?php

namespace App\Model;

class Prono extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'pronos';
	protected $fillable = ['id','type','prono','odd','matchid'];

	public $timestamps = false;

	protected $casts = [
		'id' => 'string',
		'matchid' => 'string'
	];

}