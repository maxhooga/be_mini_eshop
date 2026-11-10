<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Model\Pricing\PriceCalculator;
use App\Model\Product\Product;
use Tester\Assert;

$calculator = new PriceCalculator;

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

$price = $calculator->calculate([
	['product' => $starter, 'quantity' => 2],
	['product' => $pro, 'quantity' => 1],
]);

Assert::count(2, $price->lines);
Assert::same(2000.0, $price->lines[0]->lineWithoutVat);
Assert::same(420.0, $price->lines[0]->lineVat);
Assert::same(2420.0, $price->lines[0]->lineWithVat);
Assert::same(4000.0, $price->totalWithoutVat);
Assert::same(840.0, $price->totalVat);
Assert::same(4840.0, $price->totalWithVat);

Assert::exception(
	fn() => $calculator->calculate([]),
	InvalidArgumentException::class,
);

Assert::exception(
	fn() => $calculator->calculate([
		['product' => $starter, 'quantity' => 0],
	]),
	InvalidArgumentException::class,
);
