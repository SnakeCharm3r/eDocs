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
            AND COLUMN_NAME = 'hmisId' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        // Drop the foreign key constraint if it exists
        if (!empty($foreignKeys)) {
            foreach ($foreignKeys as $fk) {
                \DB::statement("ALTER TABLE `ict_access_resources` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
        }
        
        // Get all indexes on the hmisId column
        $indexes = \DB::select("
            SELECT DISTINCT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'ict_access_resources' 
            AND COLUMN_NAME = 'hmisId'
            AND INDEX_NAME != 'PRIMARY'
        ");
        
        // Drop all indexes on hmisId column
        foreach ($indexes as $index) {
            \DB::statement("ALTER TABLE `ict_access_resources` DROP INDEX `{$index->INDEX_NAME}`");
        }
        
        // Now change the column type to JSON (nullable since we have existing data)
        \DB::statement('ALTER TABLE `ict_access_resources` MODIFY `hmisId` JSON NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, convert JSON data back to BIGINT if possible
        // This is a simplified rollback - you may need to handle data conversion differently
        \DB::statement('ALTER TABLE `ict_access_resources` MODIFY `hmisId` BIGINT UNSIGNED NULL');
        
        Schema::table('ict_access_resources', function (Blueprint $table) {
            // Re-add the foreign key constraint (this will also create an index)
            $table->foreign('hmisId')->references('id')->on('h_m_i_s_access_levels')->onDelete('cascade');
        });
    }
};

