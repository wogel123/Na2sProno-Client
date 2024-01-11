<?php

namespace App\Controllers;

use DI\Container;

class Controller
{
	protected $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function __get($name)
	{
		if($this->container->get($name)) {
			return $this->container->get($name);
		}
	}

}