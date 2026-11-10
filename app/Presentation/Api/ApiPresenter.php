<?php declare(strict_types=1);

namespace App\Presentation\Api;

use App\Core\CorsHeaders;
use App\Model\Exception\NotFoundException;
use App\Model\Exception\ValidationException;
use Nette;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use Tracy\Debugger;
use Tracy\ILogger;


abstract class ApiPresenter extends Nette\Application\UI\Presenter
{
	/** @var list<string> */
	public array $allowedMethods = ['GET', 'POST', 'HEAD', 'OPTIONS'];

	private ?CorsHeaders $cors = null;


	public function injectCorsHeaders(CorsHeaders $cors): void
	{
		$this->cors = $cors;
	}


	protected function startup(): void
	{
		parent::startup();
		$this->cors?->apply($this);
	}


	/** @param mixed $data */
	protected function sendSuccess(mixed $data, int $code = IResponse::S200_OK): void
	{
		$this->getHttpResponse()->setCode($code);
		$this->sendJson(['data' => $data]);
	}


	/**
	 * @param array<string, string> $errors
	 * @throws AbortException
	 */
	protected function sendError(
		string $message,
		int $code = IResponse::S400_BadRequest,
		array $errors = [],
	): never {
		$payload = ['error' => ['message' => $message]];
		if ($errors !== []) {
			$payload['error']['errors'] = $errors;
		}

		$this->getHttpResponse()->setCode($code);
		$this->sendJson($payload);
	}


	/** @return array<string, mixed> */
	protected function getJsonBody(): array
	{
		$raw = $this->getHttpRequest()->getRawBody();
		if ($raw === null || trim($raw) === '') {
			return [];
		}

		try {
			$decoded = Nette\Utils\Json::decode($raw, forceArrays: true);
		} catch (Nette\Utils\JsonException) {
			$this->sendError('Invalid JSON body.', IResponse::S400_BadRequest);
		}

		if (!is_array($decoded)) {
			$this->sendError('JSON body must be an object.', IResponse::S400_BadRequest);
		}

		/** @var array<string, mixed> $decoded */
		return $decoded;
	}


	protected function requireMethod(string ...$methods): void
	{
		$method = strtoupper($this->getHttpRequest()->getMethod());
		$allowed = array_map(strtoupper(...), $methods);
		if (!in_array($method, $allowed, true)) {
			$this->getHttpResponse()->setHeader('Allow', implode(', ', $allowed));
			$this->sendError('Method not allowed.', IResponse::S405_MethodNotAllowed);
		}
	}


	/** @throws AbortException */
	protected function runDomain(callable $callback): void
	{
		try {
			$callback();
		} catch (ValidationException $e) {
			$this->sendError($e->getMessage(), IResponse::S422_UnprocessableEntity, $e->getErrors());
		} catch (NotFoundException $e) {
			$this->sendError($e->getMessage(), IResponse::S404_NotFound);
		} catch (BadRequestException $e) {
			throw $e;
		} catch (AbortException $e) {
			throw $e;
		} catch (\Throwable $e) {
			Debugger::log($e, ILogger::EXCEPTION);
			$this->sendError('Internal server error.', IResponse::S500_InternalServerError);
		}
	}
}
