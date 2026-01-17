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
        Schema::create('shopify_shops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('shop_domain', 255)->unique(); // e.g. mystore.myshopify.com
            $table->string('app_name', 1024)->nullable();
            $table->string('admin_api_token', 1024)->nullable();
            $table->string('api_version', 1024)->default('2025-01');
            $table->string('api_key', 1024)->nullable();
            $table->string('api_secret_key', 1024)->nullable();
            $table->string('webhook_version', 1024)->default('2025-01');
            $table->string('webhook_secret', 1024)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_shops');
    }
};
