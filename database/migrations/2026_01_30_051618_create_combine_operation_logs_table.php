<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Main table for combine operations
        Schema::create('combine_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_log_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('order_id', 100); // Shopify order URI
            $table->unsignedBigInteger('order_id_numeric')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // User who triggered
            $table->string('status', 50)->default('pending'); // pending, success, error
            $table->text('error_message')->nullable();
            $table->string('original_shipping_method', 255)->nullable();
            $table->integer('fulfillment_orders_before')->nullable();
            $table->integer('fulfillment_orders_after')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('shop_id');
            $table->index('audit_log_id');
        });

        // Sub-logs for detailed steps (like webhook_subs)
        Schema::create('combine_operation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('combine_operation_id');
            $table->text('event')->nullable();
            $table->integer('time_taken_ms')->nullable();
            $table->text('shopify_request')->nullable();
            $table->text('shopify_response')->nullable();
            $table->integer('shopify_response_code')->nullable();
            $table->timestamps();

            $table->foreign('combine_operation_id')
                ->references('id')
                ->on('combine_operations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combine_operation_logs');
        Schema::dropIfExists('combine_operations');
    }
};
