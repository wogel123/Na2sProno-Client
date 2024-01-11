<?php

namespace App\Model;

class UserReset extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'members_reset';
	protected $fillable = ['userid','token','expiration'];

	public $timestamps = false;

	protected $casts = [
		'userid' => 'string'
	];

}