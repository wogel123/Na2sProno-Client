<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
	protected $table = 'members_token';
	protected $fillable = ['tokenid','userid'];

	public $timestamps = false;
}