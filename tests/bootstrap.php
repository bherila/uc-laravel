<?php

/**
 * PHPUnit Bootstrap File
 *
 * This bootstrap file ensures that tests ALWAYS use SQLite, never MySQL.
 * It sets the environment variables BEFORE Laravel boots to prevent
 * any chance of accidentally connecting to the production MySQL database.
 */

// CRITICAL: Force SQLite BEFORE anything else loads
// This ensures that even if .env has MySQL credentials, tests use SQLite
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = ':memory:';

// Also set other test-specific environment variables
putenv('APP_ENV=testing');
putenv('CACHE_DRIVER=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');

$_ENV['APP_ENV'] = 'testing';
$_ENV['CACHE_DRIVER'] = 'array';
$_ENV['SESSION_DRIVER'] = 'array';
$_ENV['QUEUE_CONNECTION'] = 'sync';

$_SERVER['APP_ENV'] = 'testing';
$_SERVER['CACHE_DRIVER'] = 'array';
$_SERVER['SESSION_DRIVER'] = 'array';
$_SERVER['QUEUE_CONNECTION'] = 'sync';

// Now load the autoloader
require __DIR__ . '/../vendor/autoload.php';
