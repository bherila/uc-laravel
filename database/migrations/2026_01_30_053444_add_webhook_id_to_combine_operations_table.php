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
        Schema::table('combine_operations', function (Blueprint $table) {
            // Add webhook_id column to support combine operations triggered by webhooks
            $table->unsignedBigInteger('webhook_id')->nullable()->after('audit_log_id');
            $table->index('webhook_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combine_operations', function (Blueprint $table) {
            $table->dropIndex(['webhook_id']);
            $table->dropColumn('webhook_id');
        });
    }
};
