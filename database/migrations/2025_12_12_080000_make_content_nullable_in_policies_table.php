<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make content nullable since PDF policies don't need text content
        if (Schema::hasColumn('policies', 'content')) {
            DB::statement('ALTER TABLE `policies` MODIFY COLUMN `content` LONGTEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            // Revert to NOT NULL (but this might fail if there are NULL values)
            if (Schema::hasColumn('policies', 'content')) {
                DB::statement('ALTER TABLE `policies` MODIFY COLUMN `content` LONGTEXT NOT NULL');
            }
        });
    }
};
