<?php

use App\Controllers\ManagerArticleController;
use App\Controllers\UploadsController;
use App\Controllers\AccountController;
use App\Controllers\AdminController;
use App\Controllers\BlogController;
use App\Controllers\HomeController;
use App\Controllers\ProfilController;
use App\Controllers\PronoController;
use App\Controllers\SubscriptionController;
use App\Middleware\AdminMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
	$app->get('/', HomeController::class);
	$app->get('/daily-prono', PronoController::class . ':dailyPronoView');
	$app->get('/prono/{id}', PronoController::class . ':pronoView');
	$app->get('/archive', PronoController::class . ':archiveView');
	$app->get('/register', AccountController::class . ':registerView');
	$app->post('/register', AccountController::class . ':createUser');
	$app->get('/login', AccountController::class . ':loginView');
	$app->post('/login', AccountController::class . ':checkLogin');
	$app->post('/logout', AccountController::class . ':logout');
	$app->get('/reset-password', AccountController::class . ':resetPasswordView');
	$app->get('/forget-password', AccountController::class . ':forgetPasswordView');
	$app->get('/profil', ProfilController::class . ':profilView');
	$app->get('/contact', HomeController::class . ':contact');
	$app->get('/blogs[/{page}]', BlogController::class . ':blogsView');

	$app->get("/form-helper", UploadsController::class . ':formHelper');
	$app->get("/uploads[/{path:.*}]", UploadsController::class . ':getFile');

	$app->get('/blog/{slug}', BlogController::class . ':blogView');
	$app->get('/api/search/blog/{article}', BlogController::class . ':searchArticle');

	$app->group('/api', function(RouteCollectorProxy $group) {
		$group->group('/auth', function (RouteCollectorProxy $group) {
			$group->post('/createAccount', AccountController::class . ':createAccount');
			$group->post('/verifyAccount', AccountController::class . ':verifyAccount');
			$group->post('/updateEmail', AccountController::class . ':updateEmail');
			$group->post('/updatePassword', AccountController::class . ':updatePassword');
			$group->post('/resendVerification', AccountController::class . ':resendVerification');
			$group->post('/sendPasswordReset', AccountController::class . ':sendPasswordReset');
			$group->post('/resetPassword', AccountController::class . ':resetPassword');
		});
		$group->group('/member', function (RouteCollectorProxy $group) {
			$group->post('/listMembers', AccountController::class . ':listMembers');
			$group->post('/deleteMember', AccountController::class . ':deleteMember');
			$group->post('/edit/{field}', AccountController::class . ':edit');
		});
		$group->group('/subscription', function (RouteCollectorProxy $group) {
			$group->post('/createSubscription', SubscriptionController::class . ':createSubscription');
			$group->post('/cancelSubscription', SubscriptionController::class . ':cancelSubscription');
			$group->post('/listSubscription', SubscriptionController::class . ':listSubscription');
		});
		$group->group("/blog", function (RouteCollectorProxy $group) {
			$group->get('/getComments/{id}', BlogController::class . ':getComments');
			$group->post('/postComment/{id}', BlogController::class . ':postComment');
			$group->post('/upvoteComment/{id}', BlogController::class . ':upvoteComment');
			$group->delete('/upvoteComment/{id}', BlogController::class . ':downvoteComment');
			$group->delete('/deleteComment/{id}', BlogController::class . ':deleteComment');
			$group->get('/getImage/{slug}', BlogController::class . ':getImage');


			$group->post("/add", ManagerArticleController::class . ':insert');
			$group->post("/checkSlug", ManagerArticleController::class . ':checkSlug');
			$group->get("/getHistory/{id}", ManagerArticleController::class . ':getHistory');
			$group->get("/getHistoryImage/{id}", ManagerArticleController::class . ':getImage');
			$group->post("/edit/{id}", ManagerArticleController::class . ':update');
			$group->get("/getLatestComments", ManagerArticleController::class . ':getComments');


		});
		$group->group('/prono', function (RouteCollectorProxy $group) {
			$group->post('/submitProno', PronoController::class . ':submitProno');
			$group->post('/listProno', PronoController::class . ':listProno');
			$group->post('/editMatch', PronoController::class . ':editMatch');
			$group->post('/editProno', PronoController::class . ':editProno');
			$group->post('/editState', PronoController::class . ':editState');
			$group->post('/edit/{field}', PronoController::class . ':edit');
			$group->post('/deleteMatch', PronoController::class . ':deleteMatch');
			$group->post('/deleteTicket', PronoController::class . ':deleteTicket');
			$group->post('/deleteProno', PronoController::class . ':deleteProno');
			$group->post('/getTicket', PronoController::class . ':getTicket');
		});
	});


	$app->group('/admin', function(RouteCollectorProxy $group) {
		$group->get('/', AdminController::class . ':statsView');
		$group->group('/pronos', function(RouteCollectorProxy $group) {
			$group->get('/', AdminController::class . ':homeView');
			$group->get('/add', AdminController::class . ':pronosView');
			$group->get('/list', AdminController::class . ':pronolistView');
			$group->get('/{id}', AdminController::class . ':pronoeditView');
		});
		$group->get('/blogs', ManagerArticleController::class . ':index');
		$group->group('/blog', function (RouteCollectorProxy $group) {
			$group->get("/", ManagerArticleController::class . ':indexBlogs');
			$group->get("/add", ManagerArticleController::class . ':add');
			$group->get("/edit/{slug}", ManagerArticleController::class . ':edit');
		});
		$group->get('/members', AdminController::class . ':membersView');
		$group->get('/member/{id}', AdminController::class . ':editMember');
		$group->get('/subscriptions', AdminController::class . ':subscriptionsView');
	})->add(new AdminMiddleware($app->getContainer()));

	$app->get('/design', HomeController::class . ':design');

	$app->any('{route:.*}', HomeController::class . ':error404');

};











