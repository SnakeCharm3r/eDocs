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
            $table->text('rejection_status')->nullable()
            ->comment('Reason of the locum agreement rejection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locum_agreements', function (Blueprint $table) {
            //
        });
    }
};
