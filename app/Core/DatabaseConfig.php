<?php declare(strict_types=1);

namespace App\Core;


final class DatabaseConfig
{
	/**
	 * @return array{dsn: string, user: string, password: string}
	 */
	public static function fromUrl(string $url): array
	{
		$url = trim($url);
		if (!preg_match('#^(?:postgres|postgresql)://(.+)$#i', $url, $matches)) {
			throw new \InvalidArgumentException('DATABASE_URL must start with postgres:// or postgresql://.');
		}

		$rest = $matches[1];
		$at = strrpos($rest, '@');
		if ($at === false) {
			throw new \InvalidArgumentException('DATABASE_URL is missing credentials and host.');
		}

		$userInfo = substr($rest, 0, $at);
		$authorityAndPath = substr($rest, $at + 1);
		$colon = strpos($userInfo, ':');
		if ($colon === false) {
			$user = rawurldecode($userInfo);
			$password = '';
		} else {
			$user = rawurldecode(substr($userInfo, 0, $colon));
			$password = rawurldecode(substr($userInfo, $colon + 1));
		}

		$query = [];
		$question = strpos($authorityAndPath, '?');
		if ($question !== false) {
			$parsed = [];
			parse_str(substr($authorityAndPath, $question + 1), $parsed);
			foreach ($parsed as $key => $value) {
				if (is_string($key) && is_string($value)) {
					$query[$key] = $value;
				}
			}
			$authorityAndPath = substr($authorityAndPath, 0, $question);
		}

		$slash = strpos($authorityAndPath, '/');
		if ($slash === false || $slash === 0) {
			throw new \InvalidArgumentException('DATABASE_URL is missing a database name.');
		}

		$hostPort = substr($authorityAndPath, 0, $slash);
		$dbName = rawurldecode(substr($authorityAndPath, $slash + 1));
		if ($hostPort === '' || $dbName === '') {
			throw new \InvalidArgumentException('DATABASE_URL is missing a host or database name.');
		}

		[$host, $port] = self::splitHostPort($hostPort);

		$params = [
			'host' => $host,
			'port' => $port,
			'dbname' => $dbName,
		];
		foreach (['sslmode', 'channel_binding', 'sslrootcert', 'connect_timeout'] as $key) {
			if (isset($query[$key]) && $query[$key] !== '') {
				$params[$key] = $query[$key];
			}
		}

		$dsn = 'pgsql:' . implode(';', array_map(
			static fn(string $key, string $value): string => $key . '=' . $value,
			array_keys($params),
			array_values($params),
		));

		return [
			'dsn' => $dsn,
			'user' => $user,
			'password' => $password,
		];
	}


	/**
	 * @return array{0: string, 1: string}
	 */
	private static function splitHostPort(string $hostPort): array
	{
		if (str_starts_with($hostPort, '[')) {
			$end = strpos($hostPort, ']');
			if ($end === false) {
				throw new \InvalidArgumentException('DATABASE_URL has an invalid host.');
			}

			$host = substr($hostPort, 1, $end - 1);
			$after = substr($hostPort, $end + 1);
			$port = str_starts_with($after, ':') ? substr($after, 1) : '5432';
		} else {
			$host = $hostPort;
			$port = '5432';
			$separator = strrpos($hostPort, ':');
			if ($separator !== false) {
				$host = substr($hostPort, 0, $separator);
				$port = substr($hostPort, $separator + 1);
			}
		}

		if ($host === '' || !ctype_digit($port)) {
			throw new \InvalidArgumentException('DATABASE_URL has an invalid host or port.');
		}

		return [$host, $port];
	}
}
