<?php declare(strict_types=1);

namespace App\Model\Pricing;


final readonly class LinePrice
{
	public function __construct(
		public int $productId,
		public string $productName,
		public int $quantity,
		public float $unitPriceCzk,
		public float $vatRate,
		public float $lineWithoutVat,
		public float $lineVat,
		public float $lineWithVat,
	) {
	}
}
