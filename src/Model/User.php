<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
	protected $table = 'members';
	protected $fillable = ['id','username','email','password','verified','verified_code','verified_expires','is_admin'];

	protected $casts = [
		'id' => 'string'
	];

	public function subscription()
	{
		return $this->hasOne('App\Model\Subscription', "userid", "id");
	}

}