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
        Schema::table('clearance_forms', function (Blueprint $table) {
            // Change boolean columns to string to support "Yes", "No", "N/A"
            $table->string('handover_report_received')->nullable()->change();
            $table->string('work_responsibilities_transferred')->nullable()->change();
            $table->string('projects_tasks_closed')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert string values back to boolean before changing column type
        DB::table('clearance_forms')->whereIn('handover_report_received', ['Yes', '1'])->update(['handover_report_received' => '1']);
        DB::table('clearance_forms')->whereIn('handover_report_received', ['No', '0', 'N/A', null])->update(['handover_report_received' => '0']);

        DB::table('clearance_forms')->whereIn('work_responsibilities_transferred', ['Yes', '1'])->update(['work_responsibilities_transferred' => '1']);
        DB::table('clearance_forms')->whereIn('work_responsibilities_transferred', ['No', '0', 'N/A', null])->update(['work_responsibilities_transferred' => '0']);

        DB::table('clearance_forms')->whereIn('projects_tasks_closed', ['Yes', '1'])->update(['projects_tasks_closed' => '1']);
        DB::table('clearance_forms')->whereIn('projects_tasks_closed', ['No', '0', 'N/A', null])->update(['projects_tasks_closed' => '0']);

        Schema::table('clearance_forms', function (Blueprint $table) {
            $table->boolean('handover_report_received')->default(false)->change();
            $table->boolean('work_responsibilities_transferred')->default(false)->change();
            $table->boolean('projects_tasks_closed')->default(false)->change();
        });
    }
};
