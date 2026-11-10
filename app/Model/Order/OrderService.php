<?php declare(strict_types=1);

namespace App\Model\Order;

use App\Model\Exception\NotFoundException;
use App\Model\Exception\ValidationException;
use App\Model\Exchange\CnbExchangeRateService;
use App\Model\Pricing\PriceCalculator;
use App\Model\Product\ProductRepositoryInterface;


final class OrderService
{
	public function __construct(
		private ProductRepositoryInterface $products,
		private OrderRepositoryInterface $orders,
		private PriceCalculator $priceCalculator,
		private CnbExchangeRateService $cnb,
		private string $targetCurrency = 'EUR',
	) {
	}


	/**
	 * @param array<string, mixed> $payload
	 */
	public function create(array $payload): Order
	{
		$customer = $this->validateCustomer($payload);
		$rawItems = $this->validateItems($payload['items'] ?? null);

		$lines = [];
		foreach ($rawItems as $index => $rawItem) {
			$product = $this->products->findById($rawItem['productId']);
			if ($product === null || !$product->isActive) {
				throw new ValidationException('Validation failed.', [
					"items.$index.productId" => "Product #{$rawItem['productId']} was not found.",
				]);
			}

			$lines[] = [
				'product' => $product,
				'quantity' => $rawItem['quantity'],
			];
		}

		$price = $this->priceCalculator->calculate($lines);
		$rate = $this->cnb->getRate($this->targetCurrency);
		$totalForeign = $rate->convertFromCzk($price->totalWithVat);

		$itemsData = [];
		foreach ($price->lines as $line) {
			$itemsData[] = [
				'product_id' => $line->productId,
				'product_name' => $line->productName,
				'quantity' => $line->quantity,
				'unit_price_czk' => $line->unitPriceCzk,
				'vat_rate' => $line->vatRate,
				'line_without_vat' => $line->lineWithoutVat,
				'line_vat' => $line->lineVat,
				'line_with_vat' => $line->lineWithVat,
			];
		}

		return $this->orders->createWithItems([
			'customer_name' => $customer['name'],
			'customer_email' => $customer['email'],
			'customer_phone' => $customer['phone'],
			'total_without_vat' => $price->totalWithoutVat,
			'total_vat' => $price->totalVat,
			'total_with_vat' => $price->totalWithVat,
			'currency' => $rate->currency,
			'exchange_rate' => $rate->rate,
			'exchange_rate_date' => $rate->date,
			'total_foreign' => $totalForeign,
		], $itemsData);
	}


	public function get(int $id): Order
	{
		$order = $this->orders->findById($id);
		if ($order === null) {
			throw new NotFoundException("Order #{$id} was not found.");
		}

		return $order;
	}


	/**
	 * @param array<string, mixed> $payload
	 * @return array{name: string, email: string, phone: string}
	 */
	private function validateCustomer(array $payload): array
	{
		$errors = [];

		$name = isset($payload['customerName']) ? trim((string) $payload['customerName']) : '';
		if ($name === '') {
			$errors['customerName'] = 'Name is required.';
		} elseif (mb_strlen($name) < 2) {
			$errors['customerName'] = 'Name must have at least 2 characters.';
		}

		$email = isset($payload['customerEmail']) ? trim((string) $payload['customerEmail']) : '';
		if ($email === '') {
			$errors['customerEmail'] = 'Email is required.';
		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['customerEmail'] = 'Email is not valid.';
		}

		$phone = isset($payload['customerPhone']) ? trim((string) $payload['customerPhone']) : '';
		if ($phone === '') {
			$errors['customerPhone'] = 'Phone is required.';
		} elseif (!preg_match('~^\+?[0-9\s\-/]{9,20}$~', $phone)) {
			$errors['customerPhone'] = 'Phone is not valid.';
		}

		if ($errors !== []) {
			throw new ValidationException('Validation failed.', $errors);
		}

		return [
			'name' => $name,
			'email' => $email,
			'phone' => $phone,
		];
	}


	/**
	 * @return list<array{productId: int, quantity: int}>
	 */
	private function validateItems(mixed $items): array
	{
		if (!is_array($items) || $items === []) {
			throw new ValidationException('Validation failed.', [
				'items' => 'At least one order item is required.',
			]);
		}

		$normalized = [];
		$errors = [];

		foreach (array_values($items) as $index => $item) {
			if (!is_array($item)) {
				$errors["items.$index"] = 'Item must be an object.';
				continue;
			}

			$productId = isset($item['productId']) ? (int) $item['productId'] : 0;
			$quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

			if ($productId < 1) {
				$errors["items.$index.productId"] = 'Product id is required.';
			}
			if ($quantity < 1) {
				$errors["items.$index.quantity"] = 'Quantity must be at least 1.';
			}

			if (!isset($errors["items.$index.productId"]) && !isset($errors["items.$index.quantity"])) {
				$normalized[] = [
					'productId' => $productId,
					'quantity' => $quantity,
				];
			}
		}

		if ($errors !== []) {
			throw new ValidationException('Validation failed.', $errors);
		}

		return $normalized;
	}
}
