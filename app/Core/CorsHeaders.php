<?php declare(strict_types=1);

namespace App\Core;

use Nette\Application\Responses\VoidResponse;
use Nette\Application\UI\Presenter;


final class CorsHeaders
{
	/**
	 * @param list<string> $allowedOrigins
	 */
	public function __construct(
		private array $allowedOrigins,
	) {
	}


	public function apply(Presenter $presenter): void
	{
		$request = $presenter->getHttpRequest();
		$response = $presenter->getHttpResponse();
		$origin = $request->getHeader('Origin');

		if ($origin !== null && in_array($origin, $this->allowedOrigins, true)) {
			$response->setHeader('Access-Control-Allow-Origin', $origin);
			$response->addHeader('Vary', 'Origin');
			$response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
			$response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept');
			$response->setHeader('Access-Control-Max-Age', '86400');
		}

		if (strtoupper($request->getMethod()) === 'OPTIONS') {
			$response->setCode(204);
			$presenter->sendResponse(new VoidResponse);
		}
	}
}
