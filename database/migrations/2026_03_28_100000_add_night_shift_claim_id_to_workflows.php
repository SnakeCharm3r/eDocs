<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->unsignedBigInteger('night_shift_claim_id')->nullable()->after('on_call_request_id');
            $table->foreign('night_shift_claim_id')->references('id')->on('night_shift_claims')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropForeign(['night_shift_claim_id']);
            $table->dropColumn('night_shift_claim_id');
        });
    }
};
