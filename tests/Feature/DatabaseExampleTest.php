<?php

namespace Tests\Feature;

use App\Models\ShopifyShop;
use App\Models\User;
use App\Models\UserShopAccess;
use Tests\DatabaseTestCase;

/**
 * Example tests demonstrating database testing patterns.
 *
 * These tests extend DatabaseTestCase which:
 * - Uses RefreshDatabase trait for automatic database setup/teardown
 * - Enforces SQLite usage to prevent accidental MySQL access
 * - Provides helper methods for creating test data
 */
class DatabaseExampleTest extends DatabaseTestCase
{
    public function test_can_create_user(): void
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_can_create_shopify_shop(): void
    {
        $shop = ShopifyShop::create([
            'name' => 'Test Shop',
            'shop_domain' => 'test-shop.myshopify.com',
            'api_version' => '2025-01',
            'webhook_version' => '2025-01',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('shopify_shops', [
            'shop_domain' => 'test-shop.myshopify.com',
        ]);
    }

    public function test_can_grant_shop_access_to_user(): void
    {
        $user = $this->createTestUser();
        $shop = $this->createTestShop();

        $access = $this->grantShopAccess($user, $shop, 'read-write');

        $this->assertDatabaseHas('user_shop_accesses', [
            'user_id' => $user->id,
            'shopify_shop_id' => $shop->id,
            'access_level' => 'read-write',
        ]);
    }

    public function test_admin_user_has_full_access(): void
    {
        $admin = $this->createTestUser(isAdmin: true);

        $this->assertTrue($admin->is_admin);
    }

    public function test_user_can_have_multiple_shop_accesses(): void
    {
        $user = $this->createTestUser();
        $shop1 = $this->createTestShop();
        $shop2 = $this->createTestShop();

        $this->grantShopAccess($user, $shop1, 'read-only');
        $this->grantShopAccess($user, $shop2, 'read-write');

        $this->assertCount(2, $user->shopAccesses);
    }
}
