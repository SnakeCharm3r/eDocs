<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_kpis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goal_id');

            $table->string('name');
            $table->string('target')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('baseline')->nullable();
            $table->date('due_date')->nullable();

            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->integer('delete_status')->default(0);

            $table->foreign('goal_id')->references('id')->on('goals')->cascadeOnDelete();
            $table->foreign('createdBy')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updatedBy')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['goal_id', 'delete_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_kpis');
    }
};
