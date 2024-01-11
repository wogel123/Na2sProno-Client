<?php

namespace App\Model;

class ArticleCommentsUpvote extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'articles_comments_upvote';

	protected $fillable = ['article_id', 'comment_id', 'parent_id', 'author_id'];

	public $timestamps = false;
}