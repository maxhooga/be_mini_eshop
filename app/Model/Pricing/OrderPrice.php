<?php declare(strict_types=1);

namespace App\Model\Pricing;


final readonly class OrderPrice
{
	/**
	 * @param list<LinePrice> $lines
	 */
	public function __construct(
		public array $lines,
		public float $totalWithoutVat,
		public float $totalVat,
		public float $totalWithVat,
	) {
	}
}
