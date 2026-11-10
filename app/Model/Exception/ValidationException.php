<?php declare(strict_types=1);

namespace App\Model\Exception;


final class ValidationException extends \RuntimeException
{
	/** @param array<string, string> $errors field => message */
	public function __construct(
		string $message,
		private array $errors = [],
		int $code = 0,
		?\Throwable $previous = null,
	) {
		parent::__construct($message, $code, $previous);
	}


	/** @return array<string, string> */
	public function getErrors(): array
	{
		return $this->errors;
	}
}
