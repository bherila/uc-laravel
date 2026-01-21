<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Base test case for tests that require database access.
 *
 * This class extends TestCase and includes the RefreshDatabase trait,
 * which will use the SQLite schema at database/schema/sqlite-schema.sql.
 *
 * Usage:
 *   class MyDatabaseTest extends DatabaseTestCase
 *   {
 *       public function test_something(): void
 *       {
 *           // Database is automatically set up with SQLite schema
 *           $user = User::factory()->create();
 *           // ...
 *       }
 *   }
 */
abstract class DatabaseTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     */
    protected bool $seed = false;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load the SQLite schema if the migrations table is empty
        // This handles the case where RefreshDatabase uses schema loading
        $this->loadSqliteSchemaIfNeeded();
    }

    /**
     * Load the SQLite schema if not already loaded.
     */
    protected function loadSqliteSchemaIfNeeded(): void
    {
        $schemaPath = $this->getSqliteSchemaPath();

        if (!file_exists($schemaPath)) {
            $this->fail(
                "SQLite schema not found at: {$schemaPath}\n" .
                "Run the schema generation script or copy from database/schema/sqlite-schema.sql"
            );
        }
    }

    /**
     * Create a test user with optional admin status.
     */
    protected function createTestUser(bool $isAdmin = false): \App\Models\User
    {
        return \App\Models\User::create([
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'is_admin' => $isAdmin,
        ]);
    }

    /**
     * Create a test Shopify shop.
     */
    protected function createTestShop(array $attributes = []): \App\Models\ShopifyShop
    {
        return \App\Models\ShopifyShop::create(array_merge([
            'name' => 'Test Shop ' . uniqid(),
            'shop_domain' => 'test-' . uniqid() . '.myshopify.com',
            'api_version' => '2025-01',
            'webhook_version' => '2025-01',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Grant shop access to a user.
     */
    protected function grantShopAccess(
        \App\Models\User $user,
        \App\Models\ShopifyShop $shop,
        string $accessLevel = 'read-write'
    ): \App\Models\UserShopAccess {
        return \App\Models\UserShopAccess::create([
            'user_id' => $user->id,
            'shopify_shop_id' => $shop->id,
            'access_level' => $accessLevel,
        ]);
    }
}
