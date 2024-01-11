<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminMiddleware implements \Psr\Http\Server\MiddlewareInterface
{

	public $container;

	public function __construct($container)
	{
		$this->container = $container;
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$auth = $this->container->get("auth");
		$response = $handler->handle($request);
		if(!$auth->user['is_admin']) {
			return $response->withRedirect('/');
		}
		return $handler->handle($request);
	}
}