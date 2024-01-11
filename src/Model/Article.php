<?php

namespace App\Model;

class Article extends \Illuminate\Database\Eloquent\Model
{

	protected $table = 'articles';

	protected $fillable = ['author_id', 'title', 'slug', 'state','img', 'content'];

	protected $appends = [
		"state_name"
	];

	public function author()
	{
		return $this->belongsTo('App\Model\User');
	}

	public function history() {
		return $this->hasMany('App\Model\ArticleHistory')->with('author');
	}


	public function getStateNameAttribute()
	{
		switch ($this->state) {
			case 1:
				return "Publié";
			default:
				return "Non publié";
		}
	}

}