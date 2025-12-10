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
        Schema::table('locum_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('submited_by_incharge')->nullable()->after('user_id');
            $table->foreign('submited_by_incharge')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locum_requests', function (Blueprint $table) {
            $table->dropForeign(['submited_by_incharge']);
            $table->dropColumn('submited_by_incharge');
        });
    }
};
