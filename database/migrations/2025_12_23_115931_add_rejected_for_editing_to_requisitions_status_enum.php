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
        // Add 'rejected_for_editing' to the status enum
        DB::statement("ALTER TABLE `requisitions` MODIFY COLUMN `status` ENUM(
            'draft',
            'pending_payroll',
            'pending_hec',
            'pending_cfo',
            'pending_ceo',
            'pending_hr',
            'approved',
            'rejected',
            'rejected_for_editing',
            'filed'
        ) DEFAULT 'draft'");

        // Add 'rejected' to the current_step enum
        DB::statement("ALTER TABLE `requisitions` MODIFY COLUMN `current_step` ENUM(
            'initiation',
            'payroll_review',
            'hec_review',
            'cfo_review',
            'ceo_review',
            'hr_review',
            'rejected',
            'completed'
        ) DEFAULT 'initiation'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'rejected_for_editing' from status enum
        DB::statement("ALTER TABLE `requisitions` MODIFY COLUMN `status` ENUM(
            'draft',
            'pending_payroll',
            'pending_hec',
            'pending_cfo',
            'pending_ceo',
            'pending_hr',
            'approved',
            'rejected',
            'filed'
        ) DEFAULT 'draft'");

        // Remove 'rejected' from current_step enum
        DB::statement("ALTER TABLE `requisitions` MODIFY COLUMN `current_step` ENUM(
            'initiation',
            'payroll_review',
            'hec_review',
            'cfo_review',
            'ceo_review',
            'hr_review',
            'completed'
        ) DEFAULT 'initiation'");
    }
};
