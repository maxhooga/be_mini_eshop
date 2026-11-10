<?php declare(strict_types=1);

use App\Core\CorsHeaders;
use App\Presentation\Api\ApiPresenter;
use Nette\Application\Request as AppRequest;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\VoidResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Http\UrlScript;


final class PresenterHarness
{
	/**
	 * @param array<string, mixed> $params
	 * @param array<string, string> $headers
	 * @return array{code: int, headers: array<string, string>, payload: mixed}
	 */
	public static function run(
		Presenter $presenter,
		string $presenterName,
		string $method,
		array $params = [],
		?string $rawBody = null,
		string $urlPath = '/',
		array $headers = [],
		?CorsHeaders $cors = null,
	): array {
		$httpRequest = new Request(
			new UrlScript('http://localhost' . $urlPath),
			headers: $headers,
			method: strtoupper($method),
			rawBodyCallback: $rawBody === null ? null : static fn(): string => $rawBody,
		);
		$httpResponse = new TestHttpResponse();

		$presenter->autoCanonicalize = false;
		$presenter->injectPrimary($httpRequest, $httpResponse);

		if ($presenter instanceof ApiPresenter) {
			$presenter->injectCorsHeaders($cors ?? new CorsHeaders([
				'http://localhost:5173',
				'http://127.0.0.1:5173',
			]));
		}

		$response = $presenter->run(new AppRequest(
			$presenterName,
			strtoupper($method),
			$params,
		));

		return [
			'code' => $httpResponse->getCode(),
			'headers' => $httpResponse->getHeaders(),
			'payload' => self::payloadOf($response),
		];
	}


	private static function payloadOf(Response $response): mixed
	{
		if ($response instanceof JsonResponse) {
			return $response->getPayload();
		}

		if ($response instanceof VoidResponse) {
			return null;
		}

		throw new RuntimeException('Unexpected response type: ' . $response::class);
	}
}
