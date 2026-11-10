<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Model\Exchange\CnbExchangeRateService;
use App\Model\Exchange\ExchangeRate;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;

$service = new CnbExchangeRateService(
	'https://example.invalid/rates.txt',
	new MemoryStorage(),
);

$sample = <<<'TXT'
10.11.2026 #216
země|měna|množství|kód|kurz
EMU|euro|1|EUR|25,120
USA|dolar|1|USD|23,050
Maďarsko|forint|100|HUF|6,450
TXT;

$parsed = $service->parseRateList($sample);

Assert::same('2026-11-10', $parsed['date']);
Assert::same(25.12, $parsed['rates']['EUR']['rate']);
Assert::same(23.05, $parsed['rates']['USD']['rate']);
Assert::equal(0.0645, round($parsed['rates']['HUF']['rate'], 6));

$rate = new ExchangeRate('EUR', 25.12, '2026-11-10');
Assert::same(100.0, $rate->convertFromCzk(2512.0));

Assert::exception(
	fn() => $service->parseRateList(''),
	RuntimeException::class,
	'Unable to parse CNB rate list date.',
);

Assert::exception(
	fn() => $service->parseRateList("not-a-date\nheader\nEMU|euro|1|EUR|25,120"),
	RuntimeException::class,
	'Unable to parse CNB rate list date.',
);

Assert::exception(
	fn() => $service->parseRateList("10.11.2026 #216\nzemě|měna|množství|kód|kurz\nbroken|line"),
	RuntimeException::class,
	'No rates parsed from CNB list.',
);
