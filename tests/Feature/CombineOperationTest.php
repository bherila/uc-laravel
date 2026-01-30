<?php

namespace Tests\Feature;

use App\Models\CombineOperation;
use App\Models\CombineOperationLog;
use App\Models\AuditLog;
use App\Models\ShopifyShop;
use App\Models\User;
use Tests\DatabaseTestCase;

/**
 * Tests for CombineOperation model and related functionality.
 */
class CombineOperationTest extends DatabaseTestCase
{
    public function test_can_create_combine_operation(): void
    {
        $shop = $this->createTestShop();
        $user = $this->createTestUser(isAdmin: true);

        $operation = CombineOperation::create([
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('combine_operations', [
            'order_id' => 'gid://shopify/Order/12345',
            'status' => 'pending',
        ]);
    }

    public function test_can_create_combine_operation_with_audit_log(): void
    {
        $shop = $this->createTestShop();
        $user = $this->createTestUser(isAdmin: true);

        $auditLog = AuditLog::create([
            'event_name' => 'order.combine_shipping',
            'event_ts' => now(),
            'event_userid' => $user->id,
            'event_ext' => json_encode(['order_id' => 'gid://shopify/Order/12345']),
            'order_id' => 12345,
        ]);

        $operation = CombineOperation::create([
            'audit_log_id' => $auditLog->id,
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertNotNull($operation->auditLog);
        $this->assertEquals($auditLog->id, $operation->audit_log_id);
    }

    public function test_can_add_logs_to_combine_operation(): void
    {
        $shop = $this->createTestShop();
        $user = $this->createTestUser(isAdmin: true);

        $operation = CombineOperation::create([
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        CombineOperationLog::create([
            'combine_operation_id' => $operation->id,
            'event' => 'Started combine operation',
            'time_taken_ms' => 0,
        ]);

        CombineOperationLog::create([
            'combine_operation_id' => $operation->id,
            'event' => 'Fetched fulfillment orders',
            'time_taken_ms' => 100,
            'shopify_request' => json_encode(['orderId' => 'gid://shopify/Order/12345']),
            'shopify_response' => json_encode(['fulfillmentOrders' => []]),
        ]);

        $this->assertCount(2, $operation->logs);
    }

    public function test_combine_operation_status_can_be_updated(): void
    {
        $shop = $this->createTestShop();
        $user = $this->createTestUser(isAdmin: true);

        $operation = CombineOperation::create([
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $operation->update([
            'status' => 'success',
            'original_shipping_method' => 'UPS Ground',
            'fulfillment_orders_before' => 2,
            'fulfillment_orders_after' => 1,
        ]);

        $this->assertEquals('success', $operation->fresh()->status);
        $this->assertEquals('UPS Ground', $operation->fresh()->original_shipping_method);
        $this->assertEquals(2, $operation->fresh()->fulfillment_orders_before);
        $this->assertEquals(1, $operation->fresh()->fulfillment_orders_after);
    }

    public function test_combine_operation_can_record_error(): void
    {
        $shop = $this->createTestShop();
        $user = $this->createTestUser(isAdmin: true);

        $operation = CombineOperation::create([
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $operation->update([
            'status' => 'error',
            'error_message' => 'Failed to merge fulfillment orders: API error',
        ]);

        $this->assertEquals('error', $operation->fresh()->status);
        $this->assertStringContainsString('Failed to merge', $operation->fresh()->error_message);
    }

    public function test_combine_operation_logs_are_deleted_on_cascade(): void
    {
        $shop = $this->createTestShop();
        $user = $this->createTestUser(isAdmin: true);

        $operation = CombineOperation::create([
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        CombineOperationLog::create([
            'combine_operation_id' => $operation->id,
            'event' => 'Test log',
        ]);

        $logId = $operation->logs->first()->id;
        $operation->delete();

        $this->assertDatabaseMissing('combine_operation_logs', [
            'id' => $logId,
        ]);
    }

    public function test_combine_operation_has_shop_relationship(): void
    {
        $shop = $this->createTestShop(['name' => 'Test Wine Shop']);
        $user = $this->createTestUser(isAdmin: true);

        $operation = CombineOperation::create([
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertEquals('Test Wine Shop', $operation->shop->name);
    }

    public function test_combine_operation_has_user_relationship(): void
    {
        $shop = $this->createTestShop();
        $user = $this->createTestUser(isAdmin: true);

        $operation = CombineOperation::create([
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertEquals($user->email, $operation->user->email);
    }

    public function test_can_create_combine_operation_with_webhook(): void
    {
        $shop = $this->createTestShop();
        $user = $this->createTestUser(isAdmin: true);

        // Create a webhook
        $webhook = \App\Models\Webhook::create([
            'shop_id' => $shop->id,
            'payload' => json_encode(['test' => 'data']),
            'headers' => json_encode(['Content-Type' => 'application/json']),
            'valid_hmac' => true,
            'valid_shop_matched' => true,
        ]);

        $operation = CombineOperation::create([
            'webhook_id' => $webhook->id,
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertNotNull($operation->webhook);
        $this->assertEquals($webhook->id, $operation->webhook_id);
    }

    public function test_webhook_has_combine_operations_relationship(): void
    {
        $shop = $this->createTestShop();
        $user = $this->createTestUser(isAdmin: true);

        // Create a webhook
        $webhook = \App\Models\Webhook::create([
            'shop_id' => $shop->id,
            'payload' => json_encode(['test' => 'data']),
            'headers' => json_encode(['Content-Type' => 'application/json']),
            'valid_hmac' => true,
            'valid_shop_matched' => true,
        ]);

        // Create combine operations linked to the webhook
        CombineOperation::create([
            'webhook_id' => $webhook->id,
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/12345',
            'order_id_numeric' => 12345,
            'user_id' => $user->id,
            'status' => 'success',
        ]);

        CombineOperation::create([
            'webhook_id' => $webhook->id,
            'shop_id' => $shop->id,
            'order_id' => 'gid://shopify/Order/67890',
            'order_id_numeric' => 67890,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertCount(2, $webhook->combineOperations);
    }
}
