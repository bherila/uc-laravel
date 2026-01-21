<?php

namespace Tests\Traits;

use RuntimeException;

/**
 * Trait to enforce SQLite database usage in tests.
 * 
 * Use this trait in test classes that interact with the database to ensure
 * they never accidentally connect to MySQL (which could contain production data).
 */
trait RequiresSqlite
{
    /**
     * Boot the trait - called automatically by Laravel's TestCase.
     */
    protected function setUpRequiresSqlite(): void
    {
        $this->assertUsingSqlite();
    }

    /**
     * Assert that the current database connection is SQLite.
     *
     * @throws RuntimeException If not using SQLite
     */
    protected function assertUsingSqlite(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'sqlite') {
            throw new RuntimeException(
                "SAFETY CHECK FAILED: Tests must use SQLite, but currently using '{$driver}' driver. " .
                "Check your phpunit.xml or .env.testing file to ensure DB_CONNECTION=sqlite."
            );
        }
    }

    /**
     * Assert that the database is in-memory SQLite.
     *
     * @throws RuntimeException If not using in-memory SQLite
     */
    protected function assertUsingInMemorySqlite(): void
    {
        $this->assertUsingSqlite();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($database !== ':memory:') {
            throw new RuntimeException(
                "SAFETY CHECK FAILED: Tests should use in-memory SQLite (:memory:), " .
                "but currently using '{$database}'."
            );
        }
    }
}
