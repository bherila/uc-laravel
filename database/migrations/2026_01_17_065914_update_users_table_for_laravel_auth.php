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
            // Rename uid → id
            $table->renameColumn('uid', 'id');

            // Add new password column (keeping 'pw' and 'salt' for migration)
            $table->string('password')->nullable()->after('email');

            // Add Laravel-required columns
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->rememberToken()->after('ax_uc');
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverse changes
            $table->renameColumn('id', 'uid');
            
            $table->dropColumn('password');

            $table->dropColumn('email_verified_at');
            $table->dropRememberToken();
            $table->dropTimestamps();
        });
    }
};
