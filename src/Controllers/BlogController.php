<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Model\Article;
use App\Model\ArticleComments;
use App\Model\ArticleCommentsUpvote;
use App\Model\User;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Slim\Views\Twig;

/**
 * Class ArticlesController
 * @package App\Controller
 *
 * @property Twig view
 * @property Auth auth
 */
class BlogController extends Controller
{

	public function blogView(ServerRequest $request, Response $response, $args = [])
	{
		$article = Article::where('slug', $args['slug'])->first();

		if (count($article) == 0){
			$this->view->render($response, '/pages/error404.twig', []);
		} else {
			$this->view->render($response, '/pages/blog.twig',
				[
					'article' => $article
				]);
		}
		return $response;
	}

	public function getComments(ServerRequest $request, Response $response, $args = [])
	{
		$data = [];

		foreach (ArticleComments::where('article_id', $args['id'])->get() as $value) {
			$author = User::where('id', $value['author_id'])->first();

			$_this["id"]                      = $value['id'];
			$_this["parent"]                  = $value['parent_id'];
			$_this["creator"]                 = $value['author_id'];
			$_this["created"]                 = $value['created_at'];
			$_this["modified"]                = $value['created_at'];
			$_this["fullname"]                = $author['username'];
			$_this["profile_picture_url"]     = '/assets/img/user.png';
			$_this["content"]                 = $value['content'];
			$_this["created_by_current_user"] = $this->auth->logged && ($author['id'] === $this->auth->user['id']);

			$upvote = ArticleCommentsUpvote::select('author_id')->where('article_id', $args['id'])->where('comment_id', $_this["id"])->get();

			$arr                       = array_column($upvote->toArray(), 'author_id');
			$_this["upvote_count"]     = count($arr);
			$_this["user_has_upvoted"] = in_array($this->auth->user['id'], $arr);

			$data[] = $_this;
		}

		return $response->withJson($data);
	}

	public function postComment(ServerRequest $request, Response $response, $args = [])
	{
		$new    = $args['id'];
		$parent = $_POST['parent'];
		$date   = date('Y-m-d H:i:s');
		if ($parent == "")
			$parent = null;
		$content = $_POST['content'];

		if ($this->auth->logged) {
			$author   = $this->auth->user['id'];
			$fullname = $this->auth->user['username'];
			$avatar   = '/assets/img/user.png';

			$create = ArticleComments::create([
				'parent_id'  => $parent,
				'author_id'  => $author,
				'article_id'    => $new,
				'created_at' => $date,
				'content' => $content
			]);

			$return['id']                  = $create->id;
			$return['parent']              = $parent;
			$return['created']             = $date;
			$return['content']             = $content;
			$return['fullname']            = $fullname;
			$return['profile_picture_url'] = $avatar;
			$return['upvote_count']        = 0;
			$return['user_has_upvoted']    = false;
		} else {
			$return['id']               = '0';
			$return['parent']           = $parent;
			$return['created']          = $date;
			$return['content']          = 'Vous devez être connecté.';
			$return['fullname']         = 'Erreur';
			$return['upvote_count']     = 0;
			$return['user_has_upvoted'] = false;
		}

		return $response->withJson($return);
	}

	public function upvoteComment(ServerRequest $request, Response $response, $args = [])
	{
		$data = $request->getParsedBody();
		if (empty($data['parent'])) $parent = null; else $parent = $data['parent'];
		if ($this->auth->logged)
			ArticleCommentsUpvote::create([
				'article_id'     => $data['new'],
				'comment_id' => $args['id'],
				'parent_id'  => $parent,
				'author_id'     => $this->auth->user['id'],
			]);
		return $response->withJson([]);
	}

	public function downvoteComment(ServerRequest $request, Response $response, $args = [])
	{
		if ($this->auth->logged)
			ArticleCommentsUpvote::where('comment_id', $args['id'])->where('author_id', $this->auth->user['id'])->delete();

		return $response->withJson([]);
	}

	public function deleteComment(ServerRequest $request, Response $response, $args = [])
	{
		if ($this->auth->logged){
			$comment_count = ArticleComments::where('author_id', $this->auth->user['id'])
				->where('id', $args['id'])
				->count();

			if($comment_count === 1){
				ArticleCommentsUpvote::where('comment_id', $args['id'])
					->orWhere('parent_id', $args['id'])
					->delete();
				ArticleComments::where('id', $args['id'])
					->orWhere('parent_id', $args['id'])
					->delete();
			}
		}

		return $response->withJson([]);
	}

	public function getImage(ServerRequest $request, Response $response, $args = [])
	{
		if (filter_var($args['slug'], FILTER_VALIDATE_INT)) {
			$data = Article::select('img')->where('id', $args['slug'])->first();
		} else {
			$data = Article::select('img')->where('slug', $args['slug'])->first();
		}
		return $response->withHeader("Content-type", "image/jpeg;base64")->write($data['img']);
	}

	public function blogsView(ServerRequest $request, Response $response, $args = [])
	{

		$perPage = 2;
		$currentPage = 1;

		if (isset($args['page']))
			$currentPage = $args['page'];


		$article = Article::select(['id', 'author_id', 'title', 'slug', 'created_at', 'img'])
			->with('author')
			->orderBy('created_at', 'DESC')
			->where('state', '1')
			->get();
		$article_count = count($article);



		$article_comments = ArticleComments::with(["author"])->orderBy('created_at', 'DESC')->take(5)->get();

		$maxPages = ceil($article_count / $perPage);

		$this->view->render($response, '/pages/blogs.twig', [
			"articles" => array_slice(json_decode(json_encode($article,true)),($currentPage - 1) * $perPage,$perPage ),
			"comments" => $article_comments,
			"currentPage" => $currentPage,
			"pages" => $maxPages,
		]);
		return $response;
	}

	public function searchArticle(ServerRequest $request, Response $response, $args = [])
	{
		if (strlen($args['article']) >= 3)
			return $response->withJson(Article::select('title', 'slug')->where('title', 'like', '%' . $args['article'] . '%')->get());
		else
			return $response->withJson($args);
	}

}