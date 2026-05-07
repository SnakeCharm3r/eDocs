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
        // Add columns only if they don't exist
        if (!Schema::hasColumn('requisitions', 'hec_member_comment')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->text('hec_member_comment')->nullable()->after('funding_available');
            });
        }

        if (!Schema::hasColumn('requisitions', 'hec_objection')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->string('hec_objection')->nullable()->after('hec_member_comment');
            });
        }

        if (!Schema::hasColumn('requisitions', 'hec_justification')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->text('hec_justification')->nullable()->after('hec_objection');
            });
        }

        if (!Schema::hasColumn('requisitions', 'hec_proposed_funding')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->text('hec_proposed_funding')->nullable()->after('hec_justification');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns only if they exist
        if (Schema::hasColumn('requisitions', 'hec_proposed_funding')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('hec_proposed_funding');
            });
        }

        if (Schema::hasColumn('requisitions', 'hec_justification')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('hec_justification');
            });
        }

        if (Schema::hasColumn('requisitions', 'hec_objection')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('hec_objection');
            });
        }

        if (Schema::hasColumn('requisitions', 'hec_member_comment')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('hec_member_comment');
            });
        }
    }
};
