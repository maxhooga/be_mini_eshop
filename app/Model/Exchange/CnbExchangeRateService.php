<?php declare(strict_types=1);

namespace App\Model\Exchange;

use Nette\Caching\Cache;
use Nette\Caching\Storage;


final class CnbExchangeRateService implements ExchangeRateProviderInterface
{
	private Cache $cache;


	public function __construct(
		private string $rateUrl,
		Storage $storage,
		private int $cacheTtlSeconds = 3600,
	) {
		$this->cache = new Cache($storage, 'cnb-rates');
	}


	public function getRate(string $currency): ExchangeRate
	{
		$currency = strtoupper($currency);
		$payload = $this->cache->load($this->rateUrl, function (&$dependencies) {
			$dependencies[Cache::Expire] = $this->cacheTtlSeconds;
			return $this->downloadRateList();
		});

		assert(is_array($payload));

		if (!isset($payload['rates'][$currency])) {
			throw new \RuntimeException("Currency {$currency} was not found in CNB rate list.");
		}

		/** @var array{rate: float} $row */
		$row = $payload['rates'][$currency];

		return new ExchangeRate(
			currency: $currency,
			rate: $row['rate'],
			date: (string) $payload['date'],
		);
	}


	/**
	 * @return array{date: string, rates: array<string, array{rate: float}>}
	 */
	private function downloadRateList(): array
	{
		$context = stream_context_create([
			'http' => [
				'timeout' => 10,
				'header' => "User-Agent: be-mini-eshop/1.0\r\n",
			],
		]);

		$content = @file_get_contents($this->rateUrl, false, $context);
		if ($content === false || trim($content) === '') {
			throw new \RuntimeException('Unable to download CNB exchange rates.');
		}

		return $this->parseRateList($content);
	}


	/**
	 * @return array{date: string, rates: array<string, array{rate: float}>}
	 */
	public function parseRateList(string $content): array
	{
		$lines = preg_split('~\r\n|\n|\r~', trim($content)) ?: [];
		if ($lines === []) {
			throw new \RuntimeException('CNB rate list is empty.');
		}

		$header = array_shift($lines);
		if ($header === null || !preg_match('~^(\d{2})\.(\d{2})\.(\d{4})~', $header, $m)) {
			throw new \RuntimeException('Unable to parse CNB rate list date.');
		}
		$date = sprintf('%s-%s-%s', $m[3], $m[2], $m[1]);

		array_shift($lines);

		$rates = [];
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '') {
				continue;
			}

			$parts = explode('|', $line);
			if (count($parts) < 5) {
				continue;
			}

			$code = strtoupper(trim($parts[3]));
			$amount = (float) str_replace(',', '.', trim($parts[2]));
			$rawRate = (float) str_replace(',', '.', trim($parts[4]));
			if ($amount <= 0 || $rawRate <= 0 || $code === '') {
				continue;
			}

			$rates[$code] = [
				'rate' => $rawRate / $amount,
			];
		}

		if ($rates === []) {
			throw new \RuntimeException('No rates parsed from CNB list.');
		}

		return [
			'date' => $date,
			'rates' => $rates,
		];
	}
}
