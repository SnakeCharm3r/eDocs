<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hec_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('hec_contracts', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('division_id')->constrained('ccbrt_vendors')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hec_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('hec_contracts', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};
