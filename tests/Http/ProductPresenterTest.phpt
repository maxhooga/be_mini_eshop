<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Model\Product\Product;
use App\Model\Product\ProductRepositoryInterface;
use App\Presentation\Api\Product\ProductPresenter;
use Tester\Assert;

final class HttpFakeProductRepository implements ProductRepositoryInterface
{
	public function findById(int $id): ?Product
	{
		return null;
	}


	public function findAllActive(): array
	{
		return [
			new Product(
				id: 1,
				name: 'FAPI Starter',
				description: 'Basic plan',
				priceCzk: 1000.0,
				vatRate: 21.0,
				isActive: true,
				createdAt: '2026-11-10 12:00:00',
			),
		];
	}
}

$result = PresenterHarness::run(
	new ProductPresenter(new HttpFakeProductRepository()),
	'Api:Product',
	'GET',
	['action' => 'default'],
	urlPath: '/api/products',
);

Assert::same(200, $result['code']);
Assert::true(is_array($result['payload']));
Assert::true(isset($result['payload']['data']));
Assert::count(1, $result['payload']['data']);
Assert::same('FAPI Starter', $result['payload']['data'][0]['name']);
Assert::same(1000.0, $result['payload']['data'][0]['priceCzk']);
