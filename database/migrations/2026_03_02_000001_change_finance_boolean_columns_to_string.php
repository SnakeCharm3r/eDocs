<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Change column types first (tinyint → varchar); MySQL will cast 0→'0', 1→'1'
        DB::statement("ALTER TABLE clearance_forms MODIFY all_salary_advances_cleared VARCHAR(10) NULL");
        DB::statement("ALTER TABLE clearance_forms MODIFY allowances_reconciled VARCHAR(10) NULL");
        DB::statement("ALTER TABLE clearance_forms MODIFY pending_claims_settled VARCHAR(10) NULL");
        DB::statement("ALTER TABLE clearance_forms MODIFY outstanding_loans_recovered VARCHAR(10) NULL");
        DB::statement("ALTER TABLE clearance_forms MODIFY employee_eligible_for_final_payment VARCHAR(10) NULL");

        // Step 2: Remap '1' → 'Yes', '0' → 'No', NULL stays NULL
        foreach (['all_salary_advances_cleared', 'allowances_reconciled', 'pending_claims_settled', 'outstanding_loans_recovered', 'employee_eligible_for_final_payment'] as $col) {
            DB::statement("UPDATE clearance_forms SET `$col` = CASE WHEN `$col` = '1' THEN 'Yes' WHEN `$col` = '0' THEN 'No' ELSE `$col` END");
        }
    }

    public function down(): void
    {
        // Convert string values back to boolean
        DB::statement("UPDATE clearance_forms SET all_salary_advances_cleared = CASE WHEN all_salary_advances_cleared = 'Yes' THEN 1 ELSE 0 END");
        DB::statement("UPDATE clearance_forms SET allowances_reconciled = CASE WHEN allowances_reconciled = 'Yes' THEN 1 ELSE 0 END");
        DB::statement("UPDATE clearance_forms SET pending_claims_settled = CASE WHEN pending_claims_settled = 'Yes' THEN 1 ELSE 0 END");
        DB::statement("UPDATE clearance_forms SET outstanding_loans_recovered = CASE WHEN outstanding_loans_recovered = 'Yes' THEN 1 ELSE 0 END");
        DB::statement("UPDATE clearance_forms SET employee_eligible_for_final_payment = CASE WHEN employee_eligible_for_final_payment = 'Yes' THEN 1 ELSE 0 END");

        Schema::table('clearance_forms', function (Blueprint $table) {
            $table->boolean('all_salary_advances_cleared')->default(false)->change();
            $table->boolean('allowances_reconciled')->default(false)->change();
            $table->boolean('pending_claims_settled')->default(false)->change();
            $table->boolean('outstanding_loans_recovered')->default(false)->change();
            $table->boolean('employee_eligible_for_final_payment')->nullable()->change();
        });
    }
};
