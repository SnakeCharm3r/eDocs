<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $columns = [
                'repaid_salary_advance_details',
                'has_bonding_agreement_details',
                'loan_balances_informed_details',
                'repaid_outstanding_imprest_details',
                'cleared_imprest_or_business_advance_details',
                'all_salary_advances_cleared_details',
                'allowances_reconciled_details',
                'pending_claims_settled_details',
                'outstanding_loans_recovered_details',
                'employee_eligible_for_final_payment_details',
            ];

            foreach ($columns as $column) {
                if (!Schema::hasColumn('clearance_forms', $column)) {
                    $table->text($column)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $columns = [
                'repaid_salary_advance_details',
                'has_bonding_agreement_details',
                'loan_balances_informed_details',
                'repaid_outstanding_imprest_details',
                'cleared_imprest_or_business_advance_details',
                'all_salary_advances_cleared_details',
                'allowances_reconciled_details',
                'pending_claims_settled_details',
                'outstanding_loans_recovered_details',
                'employee_eligible_for_final_payment_details',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('clearance_forms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
