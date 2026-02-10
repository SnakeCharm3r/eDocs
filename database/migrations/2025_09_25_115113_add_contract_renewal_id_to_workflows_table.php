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
        Schema::table('workflows', function (Blueprint $table) {
            $table->foreignId('contract_renewal_id')
                  ->nullable()
                  ->constrained('contract_renewals')
                  ->onDelete('cascade')
                  ->after('access_id'); // place it after access_id
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropForeign(['contract_renewal_id']);
            $table->dropColumn('contract_renewal_id');
        });
    }
};
