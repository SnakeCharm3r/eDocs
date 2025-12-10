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
            $table->boolean('professional_reg_verified')->nullable()->after('comments')->comment('Whether professional registration number is verified');
            $table->text('professional_reg_verification_notes')->nullable()->after('professional_reg_verified')->comment('Notes about professional registration verification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_flow_histories', function (Blueprint $table) {
            $table->dropColumn(['professional_reg_verified', 'professional_reg_verification_notes']);
        });
    }
};
