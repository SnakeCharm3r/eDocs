<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds rate_used column to preserve the rate that was used when the request was created.
     * This is important for rejected claims that need to be edited - they should use the old rate.
     */
    public function up(): void
    {
        Schema::table('locum_requests', function (Blueprint $table) {
            $table->decimal('rate_used', 10, 2)->nullable()->after('locum_agreement_id')
                ->comment('The locum rate that was used when this request was created. Preserved for rejected claims.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locum_requests', function (Blueprint $table) {
            $table->dropColumn('rate_used');
        });
    }
};
