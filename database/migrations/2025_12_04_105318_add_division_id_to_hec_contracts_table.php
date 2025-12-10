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
        Schema::table('hec_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('hec_contracts', 'division_id')) {
                $table->foreignId('division_id')->nullable()->after('contract_type')->constrained('divisions')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hec_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('hec_contracts', 'division_id')) {
                $table->dropForeign(['division_id']);
                $table->dropColumn('division_id');
            }
        });
    }
};
