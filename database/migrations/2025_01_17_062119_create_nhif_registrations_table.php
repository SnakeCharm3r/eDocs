
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
        if (!Schema::hasTable('nhif_registrations')){
        Schema::create('nhif_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('userId')->references('id')->on('users');

            $table->timestamps();
        });}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhif_registrations');
    }
};
