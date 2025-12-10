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
        Schema::table('work_flow_histories', function (Blueprint $table) {
            $table->date('license_valid_until')->nullable()->after('professional_reg_verification_notes')->comment('License expiration date');
            $table->string('license_provider')->nullable()->after('license_valid_until')->comment('License provider/authority name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_flow_histories', function (Blueprint $table) {
            $table->dropColumn(['license_valid_until', 'license_provider']);
        });
    }
};
