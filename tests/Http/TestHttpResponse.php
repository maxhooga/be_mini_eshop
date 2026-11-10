<?php declare(strict_types=1);

use Nette\Http\IResponse;


/**
 * In-memory HTTP response for presenter tests (no real headers).
 */
final class TestHttpResponse implements IResponse
{
	private int $code = self::S200_OK;

	/** @var array<string, string> */
	private array $headers = [];


	public function setCode(int $code, ?string $reason = null): static
	{
		$this->code = $code;
		return $this;
	}


	public function getCode(): int
	{
		return $this->code;
	}


	public function setHeader(string $name, string $value): static
	{
		$this->headers[strtolower($name)] = $value;
		return $this;
	}


	public function addHeader(string $name, string $value): static
	{
		$key = strtolower($name);
		if (isset($this->headers[$key])) {
			$this->headers[$key] .= ', ' . $value;
		} else {
			$this->headers[$key] = $value;
		}
		return $this;
	}


	public function setContentType(string $type, ?string $charset = null): static
	{
		return $this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
	}


	public function redirect(string $url, int $code = self::S302_Found): void
	{
		$this->setCode($code);
		$this->setHeader('Location', $url);
	}


	public function setExpiration(?string $expire): static
	{
		return $this;
	}


	public function isSent(): bool
	{
		return false;
	}


	public function getHeader(string $header): ?string
	{
		return $this->headers[strtolower($header)] ?? null;
	}


	public function getHeaders(): array
	{
		return $this->headers;
	}


	public function setCookie(
		string $name,
		string $value,
		string|int|\DateTimeInterface|null $expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		?bool $httpOnly = null,
	): static {
		return $this;
	}


	public function deleteCookie(
		string $name,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
	): void {
	}
}
