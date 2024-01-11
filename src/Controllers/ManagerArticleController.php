<?php


namespace App\Controllers;


use App\Controllers;
use App\Model\Article;
use App\Model\ArticleComments;
use App\Model\ArticleHistory;
use Cocur\Slugify\Slugify;
use Selective\Config\Configuration;
use Slim\App;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Slim\Views\Twig;
use Valitron\Validator;

/**
 * Class ManagerArticleController
 * @package App\Controller\Manager
 *
 * @property Twig view
 */
class ManagerArticleController extends Controller
{

	public function indexBlogs(ServerRequest $request, Response $response) {

		$this->view->render($response, '/admin/blogindex.twig');
		return $response;
	}

	public function setImage($image_data, $fileName) {
		$dir = $this->container->get(Configuration::class)->getString("storage");
		$data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image_data));
		$pathDir =  '/articles/' . date('Y') . '/' . date("m");
		$path =  $pathDir. '/' . $fileName . '.png';
		$filePath = $dir . $path;
		$pathArray = explode('/', $pathDir); // /articles/truc/2020
		$pathTmp = "";
		foreach ($pathArray as $array){
			if(strlen($array) !== 0) {
				$pathTmp .= $array . '/';

				if(!file_exists($dir . $pathTmp))
					mkdir($dir . $pathTmp);
			}
		}
		file_put_contents($filePath, $data);
		return 'https://dev.wogel123.fr/uploads' . $path;
	}

	public function index(ServerRequest $request, Response $response, $args = [])
	{
		$articles = Article::orderBy('created_at', 'DESC')->get();

		$this->view->render($response, '/admin/blogs.twig',
			[
				'articles' => $articles
			]);
		return $response;
	}

	public function edit(ServerRequest $request, Response $response, $args = [])
	{
		$article = Article::where('slug', $args['slug'])->first();

		if (!$article) {
			return $response->withRedirect('/admin/blog/add');
		}

		$this->view->render($response, '/admin/blogedit.twig',
			[
				'article' => $article
			]);
		return $response;
	}

	public function insert(ServerRequest $request, Response $response, $args = []){
		$body = $request->getParsedBody();

		$title = $body['title'];
		$slug = $body['slug'];
//		$category = $body['category'];
		$author_id = $body['author_id'];
		$content = $body['content'];
		$img_data = $body['img_data'];

		$v = new Validator([
			"title" => $title,
			"slug" => $slug,
//			"category" => $category,
			"content" => $content,
			"author_id" => $author_id,
			'img_data' => $img_data,
		]);

		$v->stopOnFirstFail();

		$v->rule('required', "title")->message("Le champ title n'est pas valide");
		$v->rule('required', "slug")->message("Le champ slug n'est pas valide");
//		$v->rule('required', "category")->message("Le champ category n'est pas valide");
		$v->rule('required', "content")->message("Le champ content n'est pas valide");
		$v->rule('required', "author_id")->message("Le champ author n'est pas valide");
		$v->rule('required', "img_data")->message("Le champ image n'est pas valide");

		if ($v->validate()) {


			$image = $this->setImage($img_data, $slug);

			$article = Article::create([
				"author_id" => $author_id,
				"title" => $title,
				"slug" => $slug,
				"state" => 0, // state 6 = brouillon
				"img" => $image,
//				"category" => $category,
				"content" => $content,
			]);

			return $response->withJson([
				"message" => "L'article $title a été ajoutée",
				"error" => false
			]);
		} else {
			return $response->withJson([
				"message" => $v->errors(),
				"error" => true
			]);
		}
	}

	public function add(ServerRequest $request, Response $response, $args = [])
	{
		$this->view->render($response, '/admin/blogadd.twig');
		return $response;
	}

	public function getHistory(ServerRequest $request, Response $response, $args = [])
	{
		$history = ArticleHistory::where('id', $args['id'])->first();

		return $response->withJson($history);
	}

	public function getComments(ServerRequest $request, Response $response, $args = [])
	{
		$comments = ArticleComments::with('article:title,slug,id')->with("author:username,id")->get();
		$size     = sizeof($comments);

		return $response->withJson([
			"data"            => $comments,
			"recordsTotal"    => $size,
			"recordsFiltered" => $size,
		]);
	}

	public function getImage(ServerRequest $request, Response $response, $args = [])
	{
		if (filter_var($args['id'], FILTER_VALIDATE_INT)) {
			$data = ArticleHistory::select('img')->where('id', $args['id'])->first();
		} else {
			$data = ArticleHistory::select('img')->where('slug', $args['id'])->first();
		}
		return $response->withHeader("Content-type", "image/jpeg;base64")->write($data['img']);
	}

	public function checkSlug(ServerRequest $request, Response $response, $args = [])
	{
		$body = $request->getParsedBody();

		$slug  = $body['slug'];
		$title = $body['title'];

		$article = Article::where('slug', $slug)->count();
		if ($article) {
			$s    = $this->container->get(Slugify::class);
			$data = [];

			$slug_alt = $s->slugify($title);
			if ($slug != $slug_alt)
				$data[] = $slug_alt;
			$data[] = $slug . '-' . $article;

			return $response->withJson([
				"error" => true,
				"data"  => $data
			]);
		}

		return $response->withJson([
			"error" => false
		]);


	}

	public function update(ServerRequest $request, Response $response, $args = [])
	{
		$body = $request->getParsedBody();

		$title = $body['title'];
		$slug = $body['slug'];
		$content = $body['content'];
		$img_data = $body['img_data'];
		$state = $body['state'];

		$v = new Validator([
			"title" => $title,
			"slug" => $slug,
			"content" => $content,
			'img_data' => $img_data,
			'state' => $state,
		]);

		$v->stopOnFirstFail();

		$v->rule('required', "title")->message("Le champ title n'est pas valide");
		$v->rule('required', "slug")->message("Le champ slug n'est pas valide");
		$v->rule('required', "content")->message("Le champ content n'est pas valide");
		$v->rule('required', "state")->message("Le champ state n'est pas valide");
		$v->rule('requiredWith', 'img_data', ['img_url'])->message("Le champ image n'est pas valide");

		if ($v->validate()) {

			$article = Article::where('id', $args['id'])->first();

			$articleHistory = new ArticleHistory();
			$articleHistory->article_id = $article['id'];
			$articleHistory->title =  $article['title'];
			$articleHistory->slug = $article['slug'];
			$articleHistory->img = $article['img'];
			$articleHistory->content = $article['content'];
			$articleHistory->editor = $this->auth->user['id'];
			$articleHistory->save();

			if (strlen($img_data) === 0){
				$img_url = $article['img'];
			}
			$image = $img_url;
			if(!$image) {
				$image = $this->setImage($img_data, $slug . "-" . $articleHistory['id']);
			}

			$article->update([
				"title" => $title,
				"slug" => $slug,
				"state" => $state,
				"img" => $image,
				"content" => $content,
			]);

			return $response->withJson([
				"message" => "L'article $title a été modifié",
				"error" => false
			]);
		} else {
			return $response->withJson([
				"message" => $v->errors(),
				"error" => true
			]);
		}
	}


}