<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Model\Exchange\ExchangeRate;
use Tester\Assert;

$rate = new ExchangeRate('EUR', 25.0, '2026-11-10');
Assert::same(40.0, $rate->convertFromCzk(1000.0));

Assert::exception(
	fn() => (new ExchangeRate('EUR', 0.0, '2026-11-10'))->convertFromCzk(100.0),
	InvalidArgumentException::class,
);

Assert::exception(
	fn() => (new ExchangeRate('EUR', -1.0, '2026-11-10'))->convertFromCzk(100.0),
	InvalidArgumentException::class,
);
