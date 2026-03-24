<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hec_contracts') && !Schema::hasColumn('hec_contracts', 'expired_reminder_sent_at')) {
            Schema::table('hec_contracts', function (Blueprint $table) {
                $table->timestamp('expired_reminder_sent_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hec_contracts') && Schema::hasColumn('hec_contracts', 'expired_reminder_sent_at')) {
            Schema::table('hec_contracts', function (Blueprint $table) {
                $table->dropColumn('expired_reminder_sent_at');
            });
        }
    }
};
