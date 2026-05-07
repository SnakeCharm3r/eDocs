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
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('ccbrt_contracts', 'parent_contract_id')) {
                $table->foreignId('parent_contract_id')->nullable()->after('id')->constrained('ccbrt_contracts')->onDelete('set null');
            }
            if (!Schema::hasColumn('ccbrt_contracts', 'renewal_term_number')) {
                $table->integer('renewal_term_number')->default(1)->after('parent_contract_id')->comment('1 = original, 2 = first renewal, 3 = second renewal, etc.');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->dropForeign(['parent_contract_id']);
            $table->dropColumn(['parent_contract_id', 'renewal_term_number']);
        });
    }
};


