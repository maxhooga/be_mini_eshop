<?php declare(strict_types=1);

namespace App\Presentation\Api\Order;

use App\Model\Order\OrderService;
use App\Presentation\Api\ApiPresenter;
use Nette\Http\IResponse;


/**
 * POST /api/orders      → create order from customer + items JSON
 * GET  /api/orders/<id> → order detail (thank-you)
 */
final class OrderPresenter extends ApiPresenter
{
	public function __construct(
		private OrderService $orders,
	) {
	}


	public function actionDefault(): void
	{
		$this->runDomain(function (): void {
			$this->requireMethod('POST');

			$order = $this->orders->create($this->getJsonBody());
			$this->sendSuccess($order->toArray(), IResponse::S201_Created);
		});
	}


	public function actionDetail(int $id): void
	{
		$this->runDomain(function () use ($id): void {
			$this->requireMethod('GET');

			$order = $this->orders->get($id);
			$this->sendSuccess($order->toArray());
		});
	}
}
