<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('night_shift_claim_employees', function (Blueprint $table) {
            $table->decimal('hours', 5, 2)->nullable()->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('night_shift_claim_employees', function (Blueprint $table) {
            $table->dropColumn('hours');
        });
    }
};
