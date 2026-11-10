<?php declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
		// GET /api/products
		$router->addRoute('api/products', 'Api:Product:default');
		// POST /api/orders
		$router->addRoute('api/orders', 'Api:Order:default');
		// GET /api/orders/<id>
		$router->addRoute('api/orders/<id \d+>', 'Api:Order:detail');
		$router->addRoute('<presenter>/<action>[/<id>]', 'Home:default');
		return $router;
	}
}
