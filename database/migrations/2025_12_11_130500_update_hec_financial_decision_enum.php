<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('requisitions')) {
            return;
        }

        DB::statement("ALTER TABLE `requisitions` MODIFY `hec_financial_decision` ENUM('no_financial_implication','proposed_funding','reject','no_objection','objection','no_objection_cfo_ceo') NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('requisitions')) {
            return;
        }

        DB::statement("ALTER TABLE `requisitions` MODIFY `hec_financial_decision` ENUM('no_financial_implication','proposed_funding','reject','no_objection','objection') NULL");
    }
};
