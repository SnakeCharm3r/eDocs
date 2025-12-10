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
            // Determine which column to use as reference (approval_stage may not exist yet)
            // Use status as default since it exists in the base table
            $afterColumn = 'status';
            
            // Try to use approval_stage if it exists, otherwise fall back to status
            if (Schema::hasColumn('ccbrt_contracts', 'approval_stage')) {
                $afterColumn = 'approval_stage';
            }
            
            // Notification tracking fields
            if (!Schema::hasColumn('ccbrt_contracts', 'last_notification_sent_at')) {
                $table->timestamp('last_notification_sent_at')->nullable()->after($afterColumn);
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'notification_escalation_level')) {
                $table->enum('notification_escalation_level', ['none', 'line_manager', 'hec'])->default('none')->after('last_notification_sent_at');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'line_manager_rating')) {
                $table->decimal('line_manager_rating', 3, 1)->nullable()->after('notification_escalation_level')->comment('Rating from 1-5 by Line Manager');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'hec_rating')) {
                $table->decimal('hec_rating', 3, 1)->nullable()->after('line_manager_rating')->comment('Rating from 1-5 by HEC Member');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'contract_action')) {
                $table->enum('contract_action', ['renew', 'terminate', 'hold'])->nullable()->after('hec_rating')->comment('Action taken by Line Manager');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'last_notification_sent_at',
                'notification_escalation_level',
                'line_manager_rating',
                'hec_rating',
                'contract_action',
            ]);
        });
    }
};

