<?php declare(strict_types=1);

namespace App\Model\Exchange;


final readonly class ExchangeRate
{
	public function __construct(
		public string $currency,
		public float $rate,
		public string $date,
	) {
	}


	public function convertFromCzk(float $amountCzk): float
	{
		if ($this->rate <= 0) {
			throw new \InvalidArgumentException('Exchange rate must be positive.');
		}

		return round($amountCzk / $this->rate, 2);
	}
}
