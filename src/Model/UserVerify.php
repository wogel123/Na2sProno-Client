<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserVerify extends Model
{
	protected $table = 'pending_emails';
	protected $fillable = ['userid','verif_code'];

	public $timestamps = false;
}