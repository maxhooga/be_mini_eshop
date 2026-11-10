<?php declare(strict_types=1);

namespace App\Model\Product;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;


final class ProductRepository implements ProductRepositoryInterface
{
	public function __construct(
		private Explorer $db,
	) {
	}


	public function findById(int $id): ?Product
	{
		$row = $this->db->table('products')->get($id);
		return $row === null ? null : $this->map($row);
	}


	public function findAllActive(): array
	{
		$rows = $this->db->table('products')
			->where('is_active', true)
			->order('id')
			->fetchAll();

		return array_values(array_map($this->map(...), $rows));
	}


	private function map(ActiveRow $row): Product
	{
		$createdAt = $row->created_at;
		if ($createdAt instanceof \DateTimeInterface) {
			$createdAt = $createdAt->format('Y-m-d H:i:s');
		}

		return new Product(
			id: (int) $row->id,
			name: (string) $row->name,
			description: $row->description !== null ? (string) $row->description : null,
			priceCzk: (float) $row->price_czk,
			vatRate: (float) $row->vat_rate,
			isActive: (bool) $row->is_active,
			createdAt: (string) $createdAt,
		);
	}
}
