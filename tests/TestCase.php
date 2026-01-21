<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot the testing helper traits.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Always verify we're using SQLite for safety
        $this->assertDatabaseIsSqlite();
    }

    /**
     * Ensure the test database is SQLite to prevent accidental MySQL usage.
     *
     * This is a critical safety check because:
     * 1. The .env file may contain MySQL credentials for the production database
     * 2. Using RefreshDatabase on MySQL would destroy real data
     * 3. Tests should be isolated and repeatable using in-memory SQLite
     *
     * @throws RuntimeException If the database connection is not SQLite
     */
    protected function assertDatabaseIsSqlite(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'sqlite') {
            throw new RuntimeException(
                "\n\n" .
                "╔══════════════════════════════════════════════════════════════════╗\n" .
                "║  SAFETY CHECK FAILED: Test database must be SQLite!             ║\n" .
                "║                                                                  ║\n" .
                "║  Current driver: {$driver}                                      \n" .
                "║                                                                  ║\n" .
                "║  Tests MUST use SQLite to prevent accidental data loss.         ║\n" .
                "║  The .env file may contain MySQL production credentials.        ║\n" .
                "║                                                                  ║\n" .
                "║  To fix: Ensure phpunit.xml has these settings:                 ║\n" .
                "║    <env name=\"DB_CONNECTION\" value=\"sqlite\"/>                  ║\n" .
                "║    <env name=\"DB_DATABASE\" value=\":memory:\"/>                  ║\n" .
                "╚══════════════════════════════════════════════════════════════════╝\n"
            );
        }
    }

    /**
     * Get the SQLite schema path for testing.
     */
    protected function getSqliteSchemaPath(): string
    {
        return database_path('schema/sqlite-schema.sql');
    }
}
