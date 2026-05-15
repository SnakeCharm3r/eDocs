<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $cols = [
                'personal_email'                      => 'string',
                'repaid_salary_advance_initial_amount' => 'decimal',
                'repaid_salary_advance_balance'        => 'decimal',
                'bonding_agreement_initial_amount'     => 'decimal',
                'bonding_agreement_balance'            => 'decimal',
                'loan_amount_loaned'                   => 'decimal',
                'loan_amount_paid'                     => 'decimal',
                'loan_balance_remaining'               => 'decimal',
                'allowances_initial_amount'            => 'decimal',
                'allowances_balance'                   => 'decimal',
                'hr_force_approve_reason'              => 'text',
            ];

            foreach ($cols as $col => $type) {
                if (!Schema::hasColumn('clearance_forms', $col)) {
                    if ($type === 'decimal') {
                        $table->decimal($col, 15, 2)->nullable();
                    } elseif ($type === 'text') {
                        $table->text($col)->nullable();
                    } else {
                        $table->string($col)->nullable();
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $cols = [
                'personal_email', 'repaid_salary_advance_initial_amount',
                'repaid_salary_advance_balance', 'bonding_agreement_initial_amount',
                'bonding_agreement_balance', 'loan_amount_loaned', 'loan_amount_paid',
                'loan_balance_remaining', 'allowances_initial_amount',
                'allowances_balance', 'hr_force_approve_reason',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('clearance_forms', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
