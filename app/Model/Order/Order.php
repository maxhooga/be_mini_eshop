<?php declare(strict_types=1);

namespace App\Model\Order;


final readonly class Order
{
	/**
	 * @param list<OrderItem> $items
	 */
	public function __construct(
		public int $id,
		public string $customerName,
		public string $customerEmail,
		public string $customerPhone,
		public float $totalWithoutVat,
		public float $totalVat,
		public float $totalWithVat,
		public string $currency,
		public float $exchangeRate,
		public string $exchangeRateDate,
		public float $totalForeign,
		public string $createdAt,
		public array $items = [],
	) {
	}


	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'customerName' => $this->customerName,
			'customerEmail' => $this->customerEmail,
			'customerPhone' => $this->customerPhone,
			'totalWithoutVat' => $this->totalWithoutVat,
			'totalVat' => $this->totalVat,
			'totalWithVat' => $this->totalWithVat,
			'currency' => $this->currency,
			'exchangeRate' => $this->exchangeRate,
			'exchangeRateDate' => $this->exchangeRateDate,
			'totalForeign' => $this->totalForeign,
			'createdAt' => $this->createdAt,
			'items' => array_map(
				static fn(OrderItem $item): array => $item->toArray(),
				$this->items,
			),
		];
	}
}
