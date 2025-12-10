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
            if (!Schema::hasColumn('requisitions', 'hr_comments')) {
                $table->text('hr_comments')->nullable()->after('ceo_decision');
            }
            if (!Schema::hasColumn('requisitions', 'hr_signature_date')) {
                $table->date('hr_signature_date')->nullable()->after('hr_comments');
            }
            if (!Schema::hasColumn('requisitions', 'hr_processed')) {
                $table->boolean('hr_processed')->default(false)->after('hr_signature_date');
            }
            if (!Schema::hasColumn('requisitions', 'hr_processed_at')) {
                $table->timestamp('hr_processed_at')->nullable()->after('hr_processed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('requisitions', 'hr_processed_at')) {
                $table->dropColumn('hr_processed_at');
            }
            if (Schema::hasColumn('requisitions', 'hr_processed')) {
                $table->dropColumn('hr_processed');
            }
            if (Schema::hasColumn('requisitions', 'hr_signature_date')) {
                $table->dropColumn('hr_signature_date');
            }
            if (Schema::hasColumn('requisitions', 'hr_comments')) {
                $table->dropColumn('hr_comments');
            }
        });
    }
};
