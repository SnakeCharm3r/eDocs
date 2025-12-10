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
        // First, get the foreign key constraint name
        $foreignKeys = \DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'ict_access_resources' 
            AND COLUMN_NAME = 'access_key_card_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        // Drop the foreign key constraint if it exists
        if (!empty($foreignKeys)) {
            foreach ($foreignKeys as $fk) {
                \DB::statement("ALTER TABLE `ict_access_resources` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
        }
        
        // Get all indexes on the access_key_card_id column
        $indexes = \DB::select("
            SELECT DISTINCT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'ict_access_resources' 
            AND COLUMN_NAME = 'access_key_card_id'
            AND INDEX_NAME != 'PRIMARY'
        ");
        
        // Drop all indexes on access_key_card_id column
        foreach ($indexes as $index) {
            \DB::statement("ALTER TABLE `ict_access_resources` DROP INDEX `{$index->INDEX_NAME}`");
        }
        
        // Now change the column type to JSON (nullable since we have existing data)
        \DB::statement('ALTER TABLE `ict_access_resources` MODIFY `access_key_card_id` JSON NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change back to unsignedBigInteger
        \DB::statement('ALTER TABLE `ict_access_resources` MODIFY `access_key_card_id` UNSIGNED BIGINT NULL');
        
        // Re-add foreign key constraint if access_key_cards table exists
        if (Schema::hasTable('access_key_cards')) {
            Schema::table('ict_access_resources', function (Blueprint $table) {
                $table->foreign('access_key_card_id')->references('id')->on('access_key_cards')->onDelete('set null');
            });
        }
    }
};

