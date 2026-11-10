<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Model\Order\Order;
use App\Model\Order\OrderItem;
use App\Model\Product\Product;
use Tester\Assert;

$product = new Product(
	id: 1,
	name: 'FAPI Starter',
	description: 'Basic plan',
	priceCzk: 1000.0,
	vatRate: 21.0,
	isActive: true,
	createdAt: '2026-11-10 12:00:00',
);

Assert::same([
	'id' => 1,
	'name' => 'FAPI Starter',
	'description' => 'Basic plan',
	'priceCzk' => 1000.0,
	'vatRate' => 21.0,
	'isActive' => true,
	'createdAt' => '2026-11-10 12:00:00',
], $product->toArray());

$item = new OrderItem(
	id: 10,
	orderId: 5,
	productId: 1,
	productName: 'FAPI Starter',
	quantity: 2,
	unitPriceCzk: 1000.0,
	vatRate: 21.0,
	lineWithoutVat: 2000.0,
	lineVat: 420.0,
	lineWithVat: 2420.0,
);

$order = new Order(
	id: 5,
	customerName: 'Jan Novak',
	customerEmail: 'jan@example.com',
	customerPhone: '+420777123456',
	totalWithoutVat: 2000.0,
	totalVat: 420.0,
	totalWithVat: 2420.0,
	currency: 'EUR',
	exchangeRate: 25.0,
	exchangeRateDate: '2026-11-10',
	totalForeign: 96.8,
	createdAt: '2026-11-10 12:00:00',
	items: [$item],
);

Assert::same([
	'id' => 5,
	'customerName' => 'Jan Novak',
	'customerEmail' => 'jan@example.com',
	'customerPhone' => '+420777123456',
	'totalWithoutVat' => 2000.0,
	'totalVat' => 420.0,
	'totalWithVat' => 2420.0,
	'currency' => 'EUR',
	'exchangeRate' => 25.0,
	'exchangeRateDate' => '2026-11-10',
	'totalForeign' => 96.8,
	'createdAt' => '2026-11-10 12:00:00',
	'items' => [[
		'id' => 10,
		'productId' => 1,
		'productName' => 'FAPI Starter',
		'quantity' => 2,
		'unitPriceCzk' => 1000.0,
		'vatRate' => 21.0,
		'lineWithoutVat' => 2000.0,
		'lineVat' => 420.0,
		'lineWithVat' => 2420.0,
	]],
], $order->toArray());
