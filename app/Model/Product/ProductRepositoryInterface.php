<?php declare(strict_types=1);

namespace App\Model\Product;


interface ProductRepositoryInterface
{
	public function findById(int $id): ?Product;

	/** @return list<Product> */
	public function findAllActive(): array;
}
