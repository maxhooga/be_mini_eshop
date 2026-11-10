<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

$logDir = dirname(__DIR__) . '/temp/log';
if (!is_dir($logDir)) {
	mkdir($logDir, 0777, true);
}
Tracy\Debugger::$logDirectory = $logDir;

require __DIR__ . '/Http/TestHttpResponse.php';
require __DIR__ . '/Http/PresenterHarness.php';
