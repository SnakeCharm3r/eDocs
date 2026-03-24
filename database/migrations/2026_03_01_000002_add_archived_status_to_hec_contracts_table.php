<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify the enum to add 'archived' — MySQL requires redefining the full enum
        DB::statement("ALTER TABLE hec_contracts MODIFY COLUMN status ENUM('draft','active','expired','terminated','soonToExpire','in_progress','renewed','archived') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE hec_contracts MODIFY COLUMN status ENUM('draft','active','expired','terminated','soonToExpire','in_progress','renewed') NOT NULL DEFAULT 'draft'");
    }
};
