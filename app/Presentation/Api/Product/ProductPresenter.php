<?php declare(strict_types=1);

namespace App\Presentation\Api\Product;

use App\Model\Product\Product;
use App\Model\Product\ProductRepositoryInterface;
use App\Presentation\Api\ApiPresenter;


/**
 * JSON API controller for products.
 * GET /api/products → list active products
 */
final class ProductPresenter extends ApiPresenter
{
	public function __construct(
		private ProductRepositoryInterface $products,
	) {
	}


	public function actionDefault(): void
	{
		$this->runDomain(function (): void {
			$this->requireMethod('GET');

			$items = array_map(
				static fn(Product $product): array => $product->toArray(),
				$this->products->findAllActive(),
			);

			$this->sendSuccess($items);
		});
	}
}
