<?php declare(strict_types=1);

namespace App\Model\Order;


final readonly class OrderItem
{
	public function __construct(
		public int $id,
		public int $orderId,
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


	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'productId' => $this->productId,
			'productName' => $this->productName,
			'quantity' => $this->quantity,
			'unitPriceCzk' => $this->unitPriceCzk,
			'vatRate' => $this->vatRate,
			'lineWithoutVat' => $this->lineWithoutVat,
			'lineVat' => $this->lineVat,
			'lineWithVat' => $this->lineWithVat,
		];
	}
}
