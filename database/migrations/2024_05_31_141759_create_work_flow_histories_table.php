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
        if (!Schema::hasTable('work_flow_histories')){
        Schema::create('work_flow_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_flow_id');
            $table->unsignedBigInteger('forwarded_by')->nullable();
            $table->unsignedBigInteger('attended_by')->nullable();
            $table->string('status')->nullable();
            $table->string('remark')->nullable();
            $table->string('attend_date')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->date('decision_date')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreign('work_flow_id')->references('id')->on('workflows');
            $table->foreign('forwarded_by')->references('id')->on('users');
            $table->foreign('attended_by')->references('id')->on('users');
            $table->timestamps();
        });}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_flow_histories');
    }
};