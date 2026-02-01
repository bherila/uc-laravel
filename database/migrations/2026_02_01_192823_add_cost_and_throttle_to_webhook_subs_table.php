<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('webhook_subs', function (Blueprint $column) {
            $column->integer('requested_query_cost')->nullable();
            $column->integer('actual_query_cost')->nullable();
            $column->integer('throttle_max')->nullable();
            $column->integer('throttle_current')->nullable();
            $column->integer('throttle_restore_rate')->nullable();
            $column->unsignedBigInteger('current_time_ms')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_subs', function (Blueprint $column) {
            $column->dropColumn([
                'requested_query_cost',
                'actual_query_cost',
                'throttle_max',
                'throttle_current',
                'throttle_restore_rate',
                'current_time_ms',
            ]);
        });
    }
};
