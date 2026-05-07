<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert default locum submission deadline (5th of the month)
        DB::table('system_settings')->insertOrIgnore([
            'key' => 'locum_submission_deadline',
            'value' => '5',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert default oncall submission deadline (5th of the month)
        DB::table('system_settings')->insertOrIgnore([
            'key' => 'oncall_submission_deadline',
            'value' => '5',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', ['locum_submission_deadline', 'oncall_submission_deadline'])
            ->delete();
    }
};
