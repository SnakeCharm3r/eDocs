<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $table->timestamp('cos_notified_at')->nullable()->after('all_clearances_completed');
            $table->unsignedBigInteger('cos_notified_by')->nullable()->after('cos_notified_at');
            $table->foreign('cos_notified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $table->dropForeign(['cos_notified_by']);
            $table->dropColumn(['cos_notified_at', 'cos_notified_by']);
        });
    }
};
