<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to modify the ENUM column
        DB::statement("ALTER TABLE `requisitions` MODIFY COLUMN `hec_financial_decision` ENUM('no_financial_implication','proposed_funding','reject','no_objection','objection','no_objection_cfo_ceo') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `requisitions` MODIFY COLUMN `hec_financial_decision` ENUM('no_financial_implication','proposed_funding','reject','no_objection','objection') NULL");
    }
};
