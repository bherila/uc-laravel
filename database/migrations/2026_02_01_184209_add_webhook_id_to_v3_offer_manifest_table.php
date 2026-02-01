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
        Schema::table('v3_offer_manifest', function (Blueprint $table) {
            $table->unsignedBigInteger('webhook_id')->nullable()->after('assignment_ordering');
            $table->index('webhook_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('v3_offer_manifest', function (Blueprint $table) {
            $table->dropIndex(['webhook_id']);
            $table->dropColumn('webhook_id');
        });
    }
};
