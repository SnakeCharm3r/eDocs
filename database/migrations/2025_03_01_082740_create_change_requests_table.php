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
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userId')->unsigned();
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('userId')->references('id')->on('users');
            $table->text('description_of_change');
            $table->string('service_type'); // New Service or Existing Service
            $table->string('insurer_tariff_name')->nullable();
            $table->text('reason_for_change');
            $table->string('priority'); // P1-High, P2-Medium, P3-Low
            $table->string('supporting_document')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
