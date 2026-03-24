<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goal_cycle_id');
            $table->unsignedBigInteger('parent_goal_id')->nullable();

            $table->enum('level', ['hec', 'department', 'unit', 'staff']);

            $table->string('title');
            $table->text('description')->nullable();

            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable();

            $table->enum('status', [
                'draft',
                'submitted_to_line_manager',
                'line_manager_approved',
                'submitted_to_hec',
                'hec_approved',
                'approved',
                'rejected',
            ])->default('draft');

            $table->dateTime('submitted_to_line_manager_at')->nullable();
            $table->unsignedBigInteger('line_manager_approved_by')->nullable();
            $table->dateTime('line_manager_approved_at')->nullable();

            $table->dateTime('submitted_to_hec_at')->nullable();
            $table->unsignedBigInteger('hec_approved_by')->nullable();
            $table->dateTime('hec_approved_at')->nullable();

            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->dateTime('submitted_to_hrbp_at')->nullable();

            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->integer('delete_status')->default(0);

            $table->foreign('goal_cycle_id')->references('id')->on('goal_cycles')->cascadeOnDelete();
            $table->foreign('parent_goal_id')->references('id')->on('goals')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
            $table->foreign('owner_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('line_manager_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('hec_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('createdBy')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updatedBy')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['goal_cycle_id', 'level', 'status']);
            $table->index(['department_id', 'unit_id', 'owner_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
