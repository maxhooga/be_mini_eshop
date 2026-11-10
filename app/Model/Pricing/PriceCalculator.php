<?php declare(strict_types=1);

namespace App\Model\Pricing;

use App\Model\Product\Product;


final class PriceCalculator
{
	/**
	 * @param list<array{product: Product, quantity: int}> $lines
	 */
	public function calculate(array $lines): OrderPrice
	{
		if ($lines === []) {
			throw new \InvalidArgumentException('Order must contain at least one item.');
		}

		$priced = [];
		$totalWithoutVat = 0.0;
		$totalVat = 0.0;

		foreach ($lines as $line) {
			$product = $line['product'];
			$quantity = $line['quantity'];
			if ($quantity < 1) {
				throw new \InvalidArgumentException('Quantity must be at least 1.');
			}

			$lineWithoutVat = round($product->priceCzk * $quantity, 2);
			$lineVat = round($lineWithoutVat * ($product->vatRate / 100), 2);
			$lineWithVat = round($lineWithoutVat + $lineVat, 2);

			$priced[] = new LinePrice(
				productId: $product->id,
				productName: $product->name,
				quantity: $quantity,
				unitPriceCzk: round($product->priceCzk, 2),
				vatRate: $product->vatRate,
				lineWithoutVat: $lineWithoutVat,
				lineVat: $lineVat,
				lineWithVat: $lineWithVat,
			);

			$totalWithoutVat += $lineWithoutVat;
			$totalVat += $lineVat;
		}

		return new OrderPrice(
			$priced,
			round($totalWithoutVat, 2),
			round($totalVat, 2),
			round($totalWithoutVat + $totalVat, 2),
		);
	}
}
