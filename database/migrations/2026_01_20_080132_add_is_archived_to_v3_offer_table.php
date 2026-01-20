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
        Schema::table('v3_offer', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('v3_offer', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });
    }
};