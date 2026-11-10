<?php declare(strict_types=1);

use Nette\Application\Request as AppRequest;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Http\UrlScript;


final class PresenterHarness
{
	/**
	 * @param array<string, mixed> $params
	 * @return array{code: int, headers: array<string, string>, payload: mixed}
	 */
	public static function run(
		Presenter $presenter,
		string $presenterName,
		string $method,
		array $params = [],
		?string $rawBody = null,
		string $urlPath = '/',
	): array {
		$httpRequest = new Request(
			new UrlScript('http://localhost' . $urlPath),
			method: strtoupper($method),
			rawBodyCallback: $rawBody === null ? null : static fn(): string => $rawBody,
		);
		$httpResponse = new TestHttpResponse();

		$presenter->autoCanonicalize = false;
		$presenter->injectPrimary($httpRequest, $httpResponse);

		$response = $presenter->run(new AppRequest(
			$presenterName,
			strtoupper($method),
			$params,
		));

		if (!$response instanceof JsonResponse) {
			throw new RuntimeException(
				'Expected JsonResponse, got ' . $response::class,
			);
		}

		return [
			'code' => $httpResponse->getCode(),
			'headers' => $httpResponse->getHeaders(),
			'payload' => $response->getPayload(),
		];
	}
}
