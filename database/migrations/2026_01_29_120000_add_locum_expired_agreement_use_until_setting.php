<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Admin can set a date until when users may continue using their expired
     * locum agreement for new claims (e.g. 2026-02-15).
     */
    public function up(): void
    {
        DB::table('system_settings')->insertOrIgnore([
            'key' => 'locum_expired_agreement_use_until',
            'value' => '', // Empty = no grace period; admin sets date when needed
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
            ->where('key', 'locum_expired_agreement_use_until')
            ->delete();
    }
};
