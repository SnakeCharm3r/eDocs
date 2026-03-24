<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Populate division_sop and division_other_organization_policy from existing division_id.
     */
    public function up(): void
    {
        if (Schema::hasTable('division_sop')) {
            $sops = DB::table('sops')->whereNotNull('division_id')->select('id', 'division_id')->get();
            foreach ($sops as $sop) {
                DB::table('division_sop')->insertOrIgnore([
                    [
                        'sop_id' => $sop->id,
                        'division_id' => $sop->division_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }
        }

        if (Schema::hasTable('division_other_organization_policy')) {
            $policies = DB::table('other_organization_policies')->whereNotNull('division_id')->select('id', 'division_id')->get();
            foreach ($policies as $policy) {
                DB::table('division_other_organization_policy')->insertOrIgnore([
                    [
                        'other_organization_policy_id' => $policy->id,
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
        // No need to delete - pivot tables will be dropped by their migrations
    }
};
