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
        Schema::create('user_shop_accesses', function (Blueprint $table) {
            $table->id();
            // Use bigInteger (signed) to match users.id which is bigint signed
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('shopify_shop_id');
            $table->enum('access_level', ['read-only', 'read-write'])->default('read-only');
            $table->timestamps();

            $table->unique(['user_id', 'shopify_shop_id']);

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shopify_shop_id')->references('id')->on('shopify_shops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shop_accesses');
    }
};
