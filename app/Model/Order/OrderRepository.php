<?php declare(strict_types=1);

namespace App\Model\Order;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;


final class OrderRepository implements OrderRepositoryInterface
{
	public function __construct(
		private Explorer $db,
	) {
	}


	public function findById(int $id): ?Order
	{
		$row = $this->db->table('orders')->get($id);
		if ($row === null) {
			return null;
		}

		$itemRows = $this->db->table('order_items')
			->where('order_id', $id)
			->order('id')
			->fetchAll();

		$items = array_values(array_map($this->mapItem(...), $itemRows));
		return $this->mapOrder($row, $items);
	}


	public function createWithItems(array $orderData, array $itemsData): Order
	{
		$this->db->beginTransaction();
		try {
			$orderRow = $this->db->table('orders')->insert($orderData);
			if (!$orderRow instanceof ActiveRow) {
				throw new \RuntimeException('Failed to insert order.');
			}

			$orderId = (int) $orderRow->id;
			foreach ($itemsData as $item) {
				$this->db->table('order_items')->insert([
					...$item,
					'order_id' => $orderId,
				]);
			}

			$this->db->commit();
		} catch (\Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}

		$order = $this->findById($orderId);
		if ($order === null) {
			throw new \RuntimeException('Order was inserted but could not be reloaded.');
		}

		return $order;
	}


	/** @param list<OrderItem> $items */
	private function mapOrder(ActiveRow $row, array $items): Order
	{
		$createdAt = $row->created_at;
		if ($createdAt instanceof \DateTimeInterface) {
			$createdAt = $createdAt->format('Y-m-d H:i:s');
		}

		$rateDate = $row->exchange_rate_date;
		if ($rateDate instanceof \DateTimeInterface) {
			$rateDate = $rateDate->format('Y-m-d');
		}

		return new Order(
			id: (int) $row->id,
			customerName: (string) $row->customer_name,
			customerEmail: (string) $row->customer_email,
			customerPhone: (string) $row->customer_phone,
			totalWithoutVat: (float) $row->total_without_vat,
			totalVat: (float) $row->total_vat,
			totalWithVat: (float) $row->total_with_vat,
			currency: (string) $row->currency,
			exchangeRate: (float) $row->exchange_rate,
			exchangeRateDate: (string) $rateDate,
			totalForeign: (float) $row->total_foreign,
			createdAt: (string) $createdAt,
			items: $items,
		);
	}


	private function mapItem(ActiveRow $row): OrderItem
	{
		return new OrderItem(
			id: (int) $row->id,
			orderId: (int) $row->order_id,
			productId: (int) $row->product_id,
			productName: (string) $row->product_name,
			quantity: (int) $row->quantity,
			unitPriceCzk: (float) $row->unit_price_czk,
			vatRate: (float) $row->vat_rate,
			lineWithoutVat: (float) $row->line_without_vat,
			lineVat: (float) $row->line_vat,
			lineWithVat: (float) $row->line_with_vat,
		);
	}
}
