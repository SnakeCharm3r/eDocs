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
        // Step 1: Temporarily add both old and new ENUM values
        DB::statement("
            ALTER TABLE `ccbrt_contracts`
            MODIFY COLUMN `status` ENUM(
                'draft',
                'active',
                'expired',
                'terminated',
                'soon_to_expire',
                'soonToExpire',
                'in_progress',
                'renewed'
            ) NOT NULL
        ");

        // Step 2: Update old rows to new ENUM value
        DB::statement("
            UPDATE `ccbrt_contracts`
            SET `status` = 'soonToExpire'
            WHERE `status` = 'soon_to_expire'
        ");

        // Step 3: Remove the old ENUM value from the column
        DB::statement("
            ALTER TABLE `ccbrt_contracts`
            MODIFY COLUMN `status` ENUM(
                'draft',
                'active',
                'expired',
                'terminated',
                'soonToExpire',
                'in_progress',
                'renewed'
            ) NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Temporarily add old ENUM value back so rollback can work
        DB::statement("
            ALTER TABLE `ccbrt_contracts`
            MODIFY COLUMN `status` ENUM(
                'draft',
                'active',
                'expired',
                'terminated',
                'soon_to_expire',
                'soonToExpire',
                'in_progress',
                'renewed'
            ) NOT NULL
        ");

        // Step 2: Map new statuses back to old ones
        DB::statement("
            UPDATE `ccbrt_contracts`
            SET `status` = 'draft'
            WHERE `status` = 'in_progress'
        ");
        DB::statement("
            UPDATE `ccbrt_contracts`
            SET `status` = 'active'
            WHERE `status` = 'renewed'
        ");
        DB::statement("
            UPDATE `ccbrt_contracts`
            SET `status` = 'soon_to_expire'
            WHERE `status` = 'soonToExpire'
        ");

        // Step 3: Revert ENUM to original values
        DB::statement("
            ALTER TABLE `ccbrt_contracts`
            MODIFY COLUMN `status` ENUM(
                'draft',
                'active',
                'expired',
                'terminated',
                'soon_to_expire'
            ) NOT NULL
        ");
    }
};
