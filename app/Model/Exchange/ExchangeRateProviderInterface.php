<?php declare(strict_types=1);

namespace App\Model\Exchange;


interface ExchangeRateProviderInterface
{
	public function getRate(string $currency): ExchangeRate;
}
