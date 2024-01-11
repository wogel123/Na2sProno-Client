<?php

use App\Auth\Auth;
use App\Controllers\SubscriptionController;
use App\Mail\Mail;
use Cocur\Slugify\Slugify;
use Illuminate\Database\Capsule\Manager;
use Psr\Container\ContainerInterface;
use Selective\Config\Configuration;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;

$configuration = new Configuration(require __DIR__ . '/settings.php');

$capsule = new Manager;
$capsule->addConnection($configuration->getArray('db'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

return [
	Configuration::class => function () use ($configuration) {
		return $configuration;
	},

	App::class => function (ContainerInterface $container) {
		AppFactory::setContainer($container);
		$app = AppFactory::create();

		return $app;
	},

	Slugify::class => function () {
		$slugify = new Slugify();
		$slugify->addRule('le', '');
		$slugify->addRule('Le', '');
		return $slugify;
	},

	"view" => function (ContainerInterface $container) {
		$view = Twig::create(__DIR__ . '/../views', ['cache' => false]);

		$view->getEnvironment()->addGlobal("auth", $container->get("auth"));

		return $view;
	},

	"mail" => function (ContainerInterface $container) {
		return new Mail($container->get(Configuration::class)->getArray('mail'));
	},

	"auth" => function (ContainerInterface $container) {
		$auth = new Auth($container->get(Configuration::class)->getString('auth.token_key'));

		if (isset($_COOKIE['user_token'])) {
			$auth->checkToken($_COOKIE['user_token']);
		}

		return $auth;
	},

];