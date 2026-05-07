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
        Schema::table('clearance_forms', function (Blueprint $table) {
            // Add end_of_contract field (if not exists)
            if (!Schema::hasColumn('clearance_forms', 'end_of_contract')) {
                $table->date('end_of_contract')->nullable()->after('last_working_day');
            }
            
            // Exit interview question fields
            if (!Schema::hasColumn('clearance_forms', 'exit_reason')) {
                $table->text('exit_reason')->nullable()->after('reason_for_leaving_other');
            }
            if (!Schema::hasColumn('clearance_forms', 'exit_explanation')) {
                $table->text('exit_explanation')->nullable()->after('exit_reason');
            }
            if (!Schema::hasColumn('clearance_forms', 'suggestions')) {
                $table->text('suggestions')->nullable()->after('exit_explanation');
            }
            if (!Schema::hasColumn('clearance_forms', 'would_recommend')) {
                $table->text('would_recommend')->nullable()->after('suggestions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            if (Schema::hasColumn('clearance_forms', 'end_of_contract')) {
                $table->dropColumn('end_of_contract');
            }
            if (Schema::hasColumn('clearance_forms', 'exit_reason')) {
                $table->dropColumn('exit_reason');
            }
            if (Schema::hasColumn('clearance_forms', 'exit_explanation')) {
                $table->dropColumn('exit_explanation');
            }
            if (Schema::hasColumn('clearance_forms', 'suggestions')) {
                $table->dropColumn('suggestions');
            }
            if (Schema::hasColumn('clearance_forms', 'would_recommend')) {
                $table->dropColumn('would_recommend');
            }
        });
    }
};
