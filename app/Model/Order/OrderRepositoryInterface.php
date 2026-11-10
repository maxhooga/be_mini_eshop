<?php declare(strict_types=1);

namespace App\Model\Order;


interface OrderRepositoryInterface
{
	public function findById(int $id): ?Order;

	/**
	 * @param array{
	 *     customer_name: string,
	 *     customer_email: string,
	 *     customer_phone: string,
	 *     total_without_vat: float,
	 *     total_vat: float,
	 *     total_with_vat: float,
	 *     currency: string,
	 *     exchange_rate: float,
	 *     exchange_rate_date: string,
	 *     total_foreign: float,
	 * } $orderData
	 * @param list<array{
	 *     product_id: int,
	 *     product_name: string,
	 *     quantity: int,
	 *     unit_price_czk: float,
	 *     vat_rate: float,
	 *     line_without_vat: float,
	 *     line_vat: float,
	 *     line_with_vat: float,
	 * }> $itemsData
	 */
	public function createWithItems(array $orderData, array $itemsData): Order;
}
