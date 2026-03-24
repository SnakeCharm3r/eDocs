<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_alignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goal_id');
            $table->unsignedBigInteger('aligned_goal_id');

            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->integer('delete_status')->default(0);

            $table->foreign('goal_id')->references('id')->on('goals')->cascadeOnDelete();
            $table->foreign('aligned_goal_id')->references('id')->on('goals')->cascadeOnDelete();
            $table->foreign('createdBy')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updatedBy')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['goal_id', 'aligned_goal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_alignments');
    }
};
