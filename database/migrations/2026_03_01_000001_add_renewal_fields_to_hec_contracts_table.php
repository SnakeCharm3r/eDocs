<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hec_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_contract_id')->nullable()->after('id');
            $table->timestamp('renewed_at')->nullable()->after('expired_reminder_sent_at');
            $table->foreign('parent_contract_id')->references('id')->on('hec_contracts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('hec_contracts', function (Blueprint $table) {
            $table->dropForeign(['parent_contract_id']);
            $table->dropColumn(['parent_contract_id', 'renewed_at']);
        });
    }
};
