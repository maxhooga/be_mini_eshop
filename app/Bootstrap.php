<?php declare(strict_types=1);

namespace App;

use App\Core\DatabaseConfig;
use Nette;
use Nette\Bootstrap\Configurator;


class Bootstrap
{
	private readonly Configurator $configurator;
	private readonly string $rootDir;


	public function __construct()
	{
		$this->rootDir = dirname(__DIR__);
		$this->configurator = new Configurator;
		$this->configurator->setTempDirectory($this->rootDir . '/temp');
	}


	public function bootWebApplication(): Nette\DI\Container
	{
		$this->initializeEnvironment();
		$this->setupContainer();
		return $this->configurator->createContainer();
	}


	public function initializeEnvironment(): void
	{
		// $this->configurator->setDebugMode('secret@23.75.345.200'); // enable for your remote IP
		$this->configurator->enableTracy($this->rootDir . '/log');

		$this->configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();
	}


	private function setupContainer(): void
	{
		$configDir = $this->rootDir . '/config';
		$this->configurator->addConfig($configDir . '/common.neon');
		$this->configurator->addConfig($configDir . '/services.neon');

		$local = $configDir . '/local.neon';
		if (is_file($local)) {
			$this->configurator->addConfig($local);
		}

		$fromEnv = $this->configFromEnvironment();
		if ($fromEnv !== []) {
			$this->configurator->addConfig($fromEnv);
		}
	}


	/** @return array<string, mixed> */
	private function configFromEnvironment(): array
	{
		$config = [];

		$databaseUrl = self::env('DATABASE_URL');
		if ($databaseUrl !== null) {
			$config['database'] = DatabaseConfig::fromUrl($databaseUrl);
		}

		$origins = self::env('CORS_ALLOWED_ORIGINS');
		if ($origins !== null) {
			$list = array_values(array_filter(
				array_map('trim', explode(',', $origins)),
				static fn(string $origin): bool => $origin !== '',
			));
			if ($list !== []) {
				$config['parameters']['corsAllowedOrigins'] = $list;
			}
		}

		return $config;
	}


	private static function env(string $name): ?string
	{
		$value = getenv($name);
		if (is_string($value) && $value !== '') {
			return $value;
		}

		if (isset($_SERVER[$name]) && is_string($_SERVER[$name]) && $_SERVER[$name] !== '') {
			return $_SERVER[$name];
		}

		return null;
	}
}
