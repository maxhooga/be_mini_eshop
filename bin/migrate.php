<?php declare(strict_types=1);

use App\Core\DatabaseConfig;

require dirname(__DIR__) . '/vendor/autoload.php';

$url = getenv('DATABASE_URL');
if (!is_string($url) || $url === '') {
	fwrite(STDERR, "DATABASE_URL is not set.\n");
	exit(1);
}

$database = DatabaseConfig::fromUrl($url);
$pdo = connectWithRetry($database);

$root = dirname(__DIR__);
execSqlFile($pdo, $root . '/migrations/deploy/001_schema.sql');

$count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
if ($count === 0) {
	execSqlFile($pdo, $root . '/seeds/products.sql');
	fwrite(STDOUT, "Seeded demo products.\n");
}

fwrite(STDOUT, "Database schema is ready.\n");


/**
 * @param array{dsn: string, user: string, password: string} $database
 */
function connectWithRetry(array $database): PDO
{
	$last = null;
	for ($attempt = 1; $attempt <= 30; $attempt++) {
		try {
			return new PDO($database['dsn'], $database['user'], $database['password'], [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			]);
		} catch (PDOException $exception) {
			$last = $exception;
			fwrite(STDERR, "Database not ready ({$attempt}/30): {$exception->getMessage()}\n");
			sleep(2);
		}
	}

	throw $last ?? new RuntimeException('Database connection failed.');
}


function execSqlFile(PDO $pdo, string $path): void
{
	$sql = file_get_contents($path);
	if ($sql === false) {
		throw new RuntimeException("Cannot read {$path}.");
	}

	$withoutComments = preg_replace('/--.*$/m', '', $sql);
	$parts = preg_split('/;\s*/', $withoutComments ?? $sql) ?: [];
	foreach ($parts as $statement) {
		$statement = trim($statement);
		if ($statement === '') {
			continue;
		}

		$pdo->exec($statement);
	}
}
