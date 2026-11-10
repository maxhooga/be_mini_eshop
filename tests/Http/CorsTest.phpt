<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Model\Product\Product;
use App\Model\Product\ProductRepositoryInterface;
use App\Presentation\Api\Product\ProductPresenter;
use Tester\Assert;

final class CorsFakeProductRepository implements ProductRepositoryInterface
{
	public function findById(int $id): ?Product
	{
		return null;
	}


	public function findAllActive(): array
	{
		return [];
	}
}

$presenter = new ProductPresenter(new CorsFakeProductRepository());

// Preflight from allowed Vite origin
$preflight = PresenterHarness::run(
	$presenter,
	'Api:Product',
	'OPTIONS',
	['action' => 'default'],
	urlPath: '/api/products',
	headers: [
		'Origin' => 'http://localhost:5173',
		'Access-Control-Request-Method' => 'GET',
	],
);

Assert::same(204, $preflight['code']);
Assert::same('http://localhost:5173', $preflight['headers']['access-control-allow-origin'] ?? null);
Assert::true(str_contains($preflight['headers']['access-control-allow-methods'] ?? '', 'GET'));
Assert::null($preflight['payload']);

// GET with Origin echoes Allow-Origin
$get = PresenterHarness::run(
	new ProductPresenter(new CorsFakeProductRepository()),
	'Api:Product',
	'GET',
	['action' => 'default'],
	urlPath: '/api/products',
	headers: [
		'Origin' => 'http://localhost:5173',
	],
);

Assert::same(200, $get['code']);
Assert::same('http://localhost:5173', $get['headers']['access-control-allow-origin'] ?? null);
Assert::true(isset($get['payload']['data']));

// Unknown origin → no ACAO header
$blocked = PresenterHarness::run(
	new ProductPresenter(new CorsFakeProductRepository()),
	'Api:Product',
	'GET',
	['action' => 'default'],
	urlPath: '/api/products',
	headers: [
		'Origin' => 'http://evil.example',
	],
);

Assert::same(200, $blocked['code']);
Assert::false(isset($blocked['headers']['access-control-allow-origin']));
