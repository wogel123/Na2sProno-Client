<?php

namespace App\Model;

class ArticleHistory extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'articles_history_edit';

	protected $fillable = ['article_id', 'title', 'slug', 'img', 'extract', 'content', 'editor'];

	public $timestamps = false;

	public function author()
	{
		return $this->belongsTo('App\Model\User', 'editor', 'id');
	}
}