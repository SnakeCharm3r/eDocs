<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Populate division_policy from existing division_id.
     */
    public function up(): void
    {
        if (Schema::hasTable('division_policy')) {
            $policies = DB::table('policies')->whereNotNull('division_id')->select('id', 'division_id')->get();
            foreach ($policies as $policy) {
                DB::table('division_policy')->insertOrIgnore([
                    [
                        'policy_id' => $policy->id,
                        'division_id' => $policy->division_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('division_policy')) {
            DB::table('division_policy')->truncate();
        }
    }
};
