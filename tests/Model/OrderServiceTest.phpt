<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Model\Exception\NotFoundException;
use App\Model\Exception\ValidationException;
use App\Model\Exchange\ExchangeRate;
use App\Model\Exchange\ExchangeRateProviderInterface;
use App\Model\Order\Order;
use App\Model\Order\OrderItem;
use App\Model\Order\OrderRepositoryInterface;
use App\Model\Order\OrderService;
use App\Model\Pricing\PriceCalculator;
use App\Model\Product\Product;
use App\Model\Product\ProductRepositoryInterface;
use Tester\Assert;

final class FakeProductRepository implements ProductRepositoryInterface
{
	/** @param list<Product> $products */
	public function __construct(private array $products)
	{
	}


	public function findById(int $id): ?Product
	{
		foreach ($this->products as $product) {
			if ($product->id === $id) {
				return $product;
			}
		}
		return null;
	}


	public function findAllActive(): array
	{
		return array_values(array_filter(
			$this->products,
			static fn(Product $p): bool => $p->isActive,
		));
	}
}

final class FakeOrderRepository implements OrderRepositoryInterface
{
	private int $nextId = 1;

	/** @var array<int, Order> */
	private array $orders = [];


	public function findById(int $id): ?Order
	{
		return $this->orders[$id] ?? null;
	}


	public function createWithItems(array $orderData, array $itemsData): Order
	{
		$id = $this->nextId++;
		$items = [];
		foreach ($itemsData as $i => $item) {
			$items[] = new OrderItem(
				id: $i + 1,
				orderId: $id,
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

		$order = new Order(
			id: $id,
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
		$this->orders[$id] = $order;
		return $order;
	}
}

final class FakeExchangeRateProvider implements ExchangeRateProviderInterface
{
	public function getRate(string $currency): ExchangeRate
	{
		return new ExchangeRate(strtoupper($currency), 25.0, '2026-11-10');
	}
}

$starter = new Product(
	id: 1,
	name: 'FAPI Starter',
	description: null,
	priceCzk: 1000.0,
	vatRate: 21.0,
	isActive: true,
	createdAt: '2026-11-10 12:00:00',
);

$pro = new Product(
	id: 2,
	name: 'FAPI Pro',
	description: null,
	priceCzk: 2000.0,
	vatRate: 21.0,
	isActive: true,
	createdAt: '2026-11-10 12:00:00',
);

$inactive = new Product(
	id: 3,
	name: 'Archived plan',
	description: null,
	priceCzk: 500.0,
	vatRate: 21.0,
	isActive: false,
	createdAt: '2026-11-10 12:00:00',
);

$orders = new FakeOrderRepository();
$service = new OrderService(
	new FakeProductRepository([$starter, $pro, $inactive]),
	$orders,
	new PriceCalculator(),
	new FakeExchangeRateProvider(),
	'EUR',
);

$validCustomer = [
	'customerName' => 'Jan Novak',
	'customerEmail' => 'jan@example.com',
	'customerPhone' => '+420777123456',
];

// --- customer validation keys ---
$e = Assert::exception(
	fn() => $service->create([
		'customerName' => 'J',
		'customerEmail' => 'not-an-email',
		'customerPhone' => '123',
		'items' => [['productId' => 1, 'quantity' => 1]],
	]),
	ValidationException::class,
);
Assert::true($e instanceof ValidationException);
Assert::true(isset($e->getErrors()['customerName']));
Assert::true(isset($e->getErrors()['customerEmail']));
Assert::true(isset($e->getErrors()['customerPhone']));

// --- items shape ---
$e = Assert::exception(
	fn() => $service->create([...$validCustomer, 'items' => 'nope']),
	ValidationException::class,
);
Assert::same(['items' => 'At least one order item is required.'], $e->getErrors());

$e = Assert::exception(
	fn() => $service->create([...$validCustomer, 'items' => []]),
	ValidationException::class,
);
Assert::same(['items' => 'At least one order item is required.'], $e->getErrors());

$e = Assert::exception(
	fn() => $service->create([
		...$validCustomer,
		'items' => [['quantity' => 1]],
	]),
	ValidationException::class,
);
Assert::true(isset($e->getErrors()['items.0.productId']));

$e = Assert::exception(
	fn() => $service->create([
		...$validCustomer,
		'items' => [['productId' => 1, 'quantity' => 0]],
	]),
	ValidationException::class,
);
Assert::true(isset($e->getErrors()['items.0.quantity']));

// --- unknown / inactive product ---
$e = Assert::exception(
	fn() => $service->create([
		...$validCustomer,
		'items' => [['productId' => 999, 'quantity' => 1]],
	]),
	ValidationException::class,
);
Assert::true(isset($e->getErrors()['items.0.productId']));

$e = Assert::exception(
	fn() => $service->create([
		...$validCustomer,
		'items' => [['productId' => 3, 'quantity' => 1]],
	]),
	ValidationException::class,
);
Assert::true(isset($e->getErrors()['items.0.productId']));

// --- happy path: single item ---
$order = $service->create([
	...$validCustomer,
	'items' => [
		['productId' => 1, 'quantity' => 2],
	],
]);

Assert::same(1, $order->id);
Assert::same('Jan Novak', $order->customerName);
Assert::count(1, $order->items);
Assert::same(2000.0, $order->totalWithoutVat);
Assert::same(420.0, $order->totalVat);
Assert::same(2420.0, $order->totalWithVat);
Assert::same('EUR', $order->currency);
Assert::same(96.8, $order->totalForeign);

// --- multi-item ---
$multi = $service->create([
	...$validCustomer,
	'items' => [
		['productId' => 1, 'quantity' => 2],
		['productId' => 2, 'quantity' => 1],
	],
]);

Assert::count(2, $multi->items);
Assert::same(1, $multi->items[0]->productId);
Assert::same(2, $multi->items[0]->quantity);
Assert::same(2, $multi->items[1]->productId);
Assert::same(1, $multi->items[1]->quantity);
Assert::same(4000.0, $multi->totalWithoutVat);
Assert::same(840.0, $multi->totalVat);
Assert::same(4840.0, $multi->totalWithVat);
Assert::same(193.6, $multi->totalForeign);

// --- get found / not found ---
$loaded = $service->get($order->id);
Assert::same($order->id, $loaded->id);
Assert::same($order->customerEmail, $loaded->customerEmail);
Assert::count(1, $loaded->items);

Assert::exception(
	fn() => $service->get(999),
	NotFoundException::class,
);
