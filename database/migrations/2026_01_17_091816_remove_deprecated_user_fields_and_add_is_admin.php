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
        Schema::table('users', function (Blueprint $table) {
            // Remove deprecated fields
            $table->dropColumn([
                'ax_maxmin',
                'ax_homes',
                'ax_tax',
                'ax_evdb',
                'ax_spgp',
                'ax_uc',
            ]);

            // Add is_admin field
            $table->boolean('is_admin')->default(false)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');

            // Re-add deprecated fields
            $table->boolean('ax_maxmin')->default(false);
            $table->boolean('ax_homes')->default(false);
            $table->boolean('ax_tax')->default(false);
            $table->boolean('ax_evdb')->default(false);
            $table->boolean('ax_spgp')->default(false);
            $table->boolean('ax_uc')->default(false);
        });
    }
};
