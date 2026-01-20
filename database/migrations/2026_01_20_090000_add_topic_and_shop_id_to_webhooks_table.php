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
        Schema::table('webhooks', function (Blueprint $table) {
            $table->string('shopify_topic', 255)->nullable()->after('headers');
            $table->unsignedBigInteger('shop_id')->nullable()->after('shopify_topic');
            
            // Add index for performance on filtering by topic or shop
            $table->index('shopify_topic');
            $table->foreign('shop_id')->references('id')->on('shopify_shops')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn(['shop_id', 'shopify_topic']);
        });
    }
};
