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
            // add nullable FK
            if (!Schema::hasColumn('users', 'primary_platform_id')) {
                $table->foreignId('primary_platform_id')
                    ->nullable()
                    ->constrained('platforms')
                    ->nullOnDelete()
                    ->after('platform_id'); // keep order tidy (if platform_id exists)
            }
        });

        // Backfill from existing users.platform_id if present
        if (Schema::hasColumn('users', 'platform_id')) {
            DB::statement('UPDATE users SET primary_platform_id = platform_id WHERE platform_id IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'primary_platform_id')) {
                $table->dropConstrainedForeignId('primary_platform_id');
            }
        });
    }
};
