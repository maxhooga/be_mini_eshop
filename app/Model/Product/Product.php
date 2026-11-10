<?php declare(strict_types=1);

namespace App\Model\Product;


final readonly class Product
{
	public function __construct(
		public int $id,
		public string $name,
		public ?string $description,
		public float $priceCzk,
		public float $vatRate,
		public bool $isActive,
		public string $createdAt,
	) {
	}


	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'description' => $this->description,
			'priceCzk' => $this->priceCzk,
			'vatRate' => $this->vatRate,
			'isActive' => $this->isActive,
			'createdAt' => $this->createdAt,
		];
	}
}
