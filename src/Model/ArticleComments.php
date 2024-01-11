<?php

namespace App\Model;

class ArticleComments extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'articles_comments';

	public function getAllUpvote() {
		return $this->hasMany('App\Model\ArticleCommentsUpvote');
	}

	public function author()
	{
		return $this->belongsTo('App\Model\User');
	}

	protected $fillable = ['parent_id', 'author_id', 'article_id', 'created_at','content'];

	public $timestamps = false;
}