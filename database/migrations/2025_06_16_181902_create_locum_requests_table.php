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
        Schema::create('locum_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('locum_agreement_id')->constrained()->onDelete('cascade');
            $table->string('locum_month');
            $table->integer('number_of_days');
            $table->integer('total_hours')->nullable();
            $table->decimal('total_amount_payable', 10, 2);
            $table->json('worked_days')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locum_requests');
    }
};
