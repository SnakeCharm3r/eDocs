<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('clearance_forms', 'loan_balance_before_clearance')) {
                $table->decimal('loan_balance_before_clearance', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('clearance_forms', 'loan_amount_recovered')) {
                $table->decimal('loan_amount_recovered', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('clearance_forms', 'imprest_amount_issued')) {
                $table->decimal('imprest_amount_issued', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('clearance_forms', 'imprest_amount_cleared')) {
                $table->decimal('imprest_amount_cleared', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('clearance_forms', 'allowances_amount')) {
                $table->decimal('allowances_amount', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('clearance_forms', 'pending_claims_amount')) {
                $table->decimal('pending_claims_amount', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('clearance_forms', 'finance_outstanding_issues')) {
                $table->text('finance_outstanding_issues')->nullable();
            }
            if (!Schema::hasColumn('clearance_forms', 'finance_documents_attached')) {
                $table->boolean('finance_documents_attached')->nullable();
            }
            if (!Schema::hasColumn('clearance_forms', 'finance_checklist_reviewed')) {
                $table->boolean('finance_checklist_reviewed')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $cols = [
                'loan_balance_before_clearance', 'loan_amount_recovered',
                'imprest_amount_issued', 'imprest_amount_cleared',
                'allowances_amount', 'pending_claims_amount',
                'finance_outstanding_issues', 'finance_documents_attached',
                'finance_checklist_reviewed',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('clearance_forms', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
