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
        Schema::table('locum_agreements', function (Blueprint $table) {
            $table->integer('status')->default(0)->comment('0: Pending to line manager, 1: Penging to HR, 2: Approved , 3: Rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locum_agreements', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
