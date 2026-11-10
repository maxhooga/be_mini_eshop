<?php declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Core\DatabaseConfig;
use Tester\Assert;

$local = DatabaseConfig::fromUrl('postgresql://fapi:secret@127.0.0.1:5432/fapi_orders');
Assert::same('pgsql:host=127.0.0.1;port=5432;dbname=fapi_orders', $local['dsn']);
Assert::same('fapi', $local['user']);
Assert::same('secret', $local['password']);

$neon = DatabaseConfig::fromUrl('postgres://owner:p%40ss@ep.example.neon.tech/neondb?sslmode=require');
Assert::same('pgsql:host=ep.example.neon.tech;port=5432;dbname=neondb;sslmode=require', $neon['dsn']);
Assert::same('p@ss', $neon['password']);

$encoded = DatabaseConfig::fromUrl('postgresql://user:secr@et@dpg-abc:5433/fapi_orders');
Assert::same('secr@et', $encoded['password']);
Assert::same('pgsql:host=dpg-abc;port=5433;dbname=fapi_orders', $encoded['dsn']);

Assert::exception(
	fn() => DatabaseConfig::fromUrl('mysql://fapi:secret@127.0.0.1/fapi_orders'),
	InvalidArgumentException::class,
);
