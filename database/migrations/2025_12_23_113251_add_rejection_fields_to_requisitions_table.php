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
        Schema::table('requisitions', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('hr_reviewed_at');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            $table->string('rejection_stage', 50)->nullable()->after('rejected_by'); // payroll, hec, cfo, ceo, hr
            $table->timestamp('can_edit_until')->nullable()->after('rejection_stage'); // One month after rejection
            
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'rejection_reason',
                'rejected_at',
                'rejected_by',
                'rejection_stage',
                'can_edit_until'
            ]);
        });
    }
};
