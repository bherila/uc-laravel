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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rerun_of_id')->nullable();
            $table->timestamps();
            $table->text('payload')->nullable(); // 64KB
            $table->text('headers')->nullable(); // 32KB fits in text
            $table->boolean('valid_hmac')->nullable();
            $table->boolean('valid_shop_matched')->nullable();
            $table->timestamp('error_ts')->nullable();
            $table->timestamp('success_ts')->nullable();
            $table->text('error_message')->nullable();
        });

        Schema::create('webhook_subs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->cascadeOnDelete();
            $table->timestamps();
            $table->text('event')->nullable(); // 32KB fits in text
            $table->text('shopify_request')->nullable(); // 64KB
            $table->text('shopify_response')->nullable(); // 64KB
            $table->integer('shopify_response_code')->nullable();
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_subs');
        Schema::dropIfExists('webhooks');
    }
};