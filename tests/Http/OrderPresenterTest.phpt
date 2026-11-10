<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Model\Exchange\ExchangeRate;
use App\Model\Exchange\ExchangeRateProviderInterface;
use App\Model\Order\Order;
use App\Model\Order\OrderItem;
use App\Model\Order\OrderRepositoryInterface;
use App\Model\Order\OrderService;
use App\Model\Pricing\PriceCalculator;
use App\Model\Product\Product;
use App\Model\Product\ProductRepositoryInterface;
use App\Presentation\Api\Order\OrderPresenter;
use Tester\Assert;

final class HttpOrderFakeProductRepository implements ProductRepositoryInterface
{
	public function findById(int $id): ?Product
	{
		if ($id !== 1) {
			return null;
		}

		return new Product(
			id: 1,
			name: 'FAPI Starter',
			description: null,
			priceCzk: 1000.0,
			vatRate: 21.0,
			isActive: true,
			createdAt: '2026-11-10 12:00:00',
		);
	}


	public function findAllActive(): array
	{
		return [];
	}
}

final class HttpOrderFakeOrderRepository implements OrderRepositoryInterface
{
	public function findById(int $id): ?Order
	{
		return null;
	}


	public function createWithItems(array $orderData, array $itemsData): Order
	{
		$items = [];
		foreach ($itemsData as $i => $item) {
			$items[] = new OrderItem(
				id: $i + 1,
				orderId: 42,
				productId: $item['product_id'],
				productName: $item['product_name'],
				quantity: $item['quantity'],
				unitPriceCzk: $item['unit_price_czk'],
				vatRate: $item['vat_rate'],
				lineWithoutVat: $item['line_without_vat'],
				lineVat: $item['line_vat'],
				lineWithVat: $item['line_with_vat'],
			);
		}

		return new Order(
			id: 42,
			customerName: $orderData['customer_name'],
			customerEmail: $orderData['customer_email'],
			customerPhone: $orderData['customer_phone'],
			totalWithoutVat: $orderData['total_without_vat'],
			totalVat: $orderData['total_vat'],
			totalWithVat: $orderData['total_with_vat'],
			currency: $orderData['currency'],
			exchangeRate: $orderData['exchange_rate'],
			exchangeRateDate: $orderData['exchange_rate_date'],
			totalForeign: $orderData['total_foreign'],
			createdAt: '2026-11-10 12:00:00',
			items: $items,
		);
	}
}

final class HttpOrderFakeExchangeRateProvider implements ExchangeRateProviderInterface
{
	public bool $explode = false;


	public function getRate(string $currency): ExchangeRate
	{
		if ($this->explode) {
			throw new RuntimeException('CNB unavailable');
		}

		return new ExchangeRate(strtoupper($currency), 25.0, '2026-11-10');
	}
}

$products = new HttpOrderFakeProductRepository();
$orders = new HttpOrderFakeOrderRepository();
$rates = new HttpOrderFakeExchangeRateProvider();
$service = new OrderService($products, $orders, new PriceCalculator(), $rates, 'EUR');

$validBody = json_encode([
	'customerName' => 'Jan Novak',
	'customerEmail' => 'jan@example.com',
	'customerPhone' => '+420777123456',
	'items' => [
		['productId' => 1, 'quantity' => 2],
	],
], JSON_THROW_ON_ERROR);

// POST valid → 201 + body
$created = PresenterHarness::run(
	new OrderPresenter($service),
	'Api:Order',
	'POST',
	['action' => 'default'],
	$validBody,
	'/api/orders',
);
Assert::same(201, $created['code']);
Assert::same(42, $created['payload']['data']['id']);
Assert::same('Jan Novak', $created['payload']['data']['customerName']);
Assert::same(2420.0, $created['payload']['data']['totalWithVat']);
Assert::same(96.8, $created['payload']['data']['totalForeign']);
Assert::false(isset($created['payload']['error']));

// POST broken JSON → 400
$badJson = PresenterHarness::run(
	new OrderPresenter($service),
	'Api:Order',
	'POST',
	['action' => 'default'],
	'{not-json',
	'/api/orders',
);
Assert::same(400, $badJson['code']);
Assert::same('Invalid JSON body.', $badJson['payload']['error']['message']);

// POST validation → 422 + error.errors
$invalid = PresenterHarness::run(
	new OrderPresenter($service),
	'Api:Order',
	'POST',
	['action' => 'default'],
	json_encode([
		'customerName' => '',
		'customerEmail' => 'bad',
		'customerPhone' => '1',
		'items' => [],
	], JSON_THROW_ON_ERROR),
	'/api/orders',
);
Assert::same(422, $invalid['code']);
Assert::same('Validation failed.', $invalid['payload']['error']['message']);
Assert::true(isset($invalid['payload']['error']['errors']));
Assert::true(is_array($invalid['payload']['error']['errors']));
Assert::true(count($invalid['payload']['error']['errors']) >= 1);

// GET missing order → 404
$missing = PresenterHarness::run(
	new OrderPresenter($service),
	'Api:Order',
	'GET',
	['action' => 'detail', 'id' => 999],
	urlPath: '/api/orders/999',
);
Assert::same(404, $missing['code']);
Assert::same('Order #999 was not found.', $missing['payload']['error']['message']);

// GET on create endpoint → 405 + Allow
$methodNotAllowed = PresenterHarness::run(
	new OrderPresenter($service),
	'Api:Order',
	'GET',
	['action' => 'default'],
	urlPath: '/api/orders',
);
Assert::same(405, $methodNotAllowed['code']);
Assert::same('Method not allowed.', $methodNotAllowed['payload']['error']['message']);
Assert::same('POST', $methodNotAllowed['headers']['allow'] ?? null);

// unhandled exception in runDomain → 500, no stack in JSON
$rates->explode = true;
$boom = PresenterHarness::run(
	new OrderPresenter($service),
	'Api:Order',
	'POST',
	['action' => 'default'],
	$validBody,
	'/api/orders',
);
Assert::same(500, $boom['code']);
Assert::same('Internal server error.', $boom['payload']['error']['message']);
Assert::false(isset($boom['payload']['error']['errors']));
$encoded = json_encode($boom['payload'], JSON_THROW_ON_ERROR);
Assert::false(str_contains($encoded, 'CNB unavailable'));
Assert::false(str_contains($encoded, 'RuntimeException'));
Assert::false(str_contains($encoded, 'Stack trace'));
