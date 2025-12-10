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
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            // Contract Information
            if (!Schema::hasColumn('ccbrt_contracts', 'contract_number')) {
                $table->string('contract_number')->unique()->nullable()->after('id');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'start_date')) {
                $table->date('start_date')->nullable()->after('creation_date');
            }
            
            // Contract Parties
            if (!Schema::hasColumn('ccbrt_contracts', 'contract_manager_id')) {
                $table->foreignId('contract_manager_id')->nullable()->constrained('users')->onDelete('set null')->after('created_by');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'contact_persons')) {
                $table->json('contact_persons')->nullable()->after('contract_manager_id');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'approvers')) {
                $table->json('approvers')->nullable()->after('contact_persons');
            }
            
            // Documents & Attachments
            if (!Schema::hasColumn('ccbrt_contracts', 'signed_contract_path')) {
                $table->string('signed_contract_path')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'terms_conditions_path')) {
                $table->string('terms_conditions_path')->nullable()->after('signed_contract_path');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'sla_document_path')) {
                $table->string('sla_document_path')->nullable()->after('terms_conditions_path');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'uploaded_contract_path')) {
                $table->string('uploaded_contract_path')->nullable()->after('sla_document_path');
            }
            
            // Contract Lifecycle
            if (!Schema::hasColumn('ccbrt_contracts', 'lifecycle_stage')) {
                $table->enum('lifecycle_stage', ['drafting', 'review', 'approval', 'signing', 'execution', 'renewal', 'close', 'termination', 'suspension'])->default('drafting')->after('status');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'amendment_history')) {
                $table->json('amendment_history')->nullable()->after('lifecycle_stage');
            }
            
            // Alerts & Notifications
            if (!Schema::hasColumn('ccbrt_contracts', 'alert_30_days')) {
                $table->boolean('alert_30_days')->default(true)->after('amendment_history');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'alert_60_days')) {
                $table->boolean('alert_60_days')->default(true)->after('alert_30_days');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'alert_90_days')) {
                $table->boolean('alert_90_days')->default(true)->after('alert_60_days');
            }
            
            // Performance Monitoring
            if (!Schema::hasColumn('ccbrt_contracts', 'kpi_metrics')) {
                $table->json('kpi_metrics')->nullable()->after('alert_90_days');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'deliverables')) {
                $table->json('deliverables')->nullable()->after('kpi_metrics');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'issue_log')) {
                $table->json('issue_log')->nullable()->after('deliverables');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'evaluation_score')) {
                $table->decimal('evaluation_score', 5, 2)->nullable()->after('issue_log');
            }
            
            // Workflow tracking
            if (!Schema::hasColumn('ccbrt_contracts', 'current_approver_id')) {
                $table->foreignId('current_approver_id')->nullable()->constrained('users')->onDelete('set null')->after('evaluation_score');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'approval_stage')) {
                $table->enum('approval_stage', ['line_manager', 'hec', 'procurement', 'approved', 'rejected'])->nullable()->after('current_approver_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->dropForeign(['contract_manager_id']);
            $table->dropForeign(['current_approver_id']);
            $table->dropColumn([
                'contract_number',
                'description',
                'start_date',
                'contract_manager_id',
                'contact_persons',
                'approvers',
                'signed_contract_path',
                'terms_conditions_path',
                'sla_document_path',
                'lifecycle_stage',
                'amendment_history',
                'alert_30_days',
                'alert_60_days',
                'alert_90_days',
                'kpi_metrics',
                'deliverables',
                'issue_log',
                'evaluation_score',
                'current_approver_id',
                'approval_stage',
            ]);
        });
    }
};
