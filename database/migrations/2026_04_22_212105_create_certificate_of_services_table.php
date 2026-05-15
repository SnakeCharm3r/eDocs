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
        Schema::create('certificate_of_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('certificate_number')->unique();
            $table->date('issue_date');
            $table->date('date_of_joining');
            $table->date('last_working_day');
            $table->string('position_held');
            $table->string('department');
            $table->text('duties_description')->nullable();
            $table->text('remarks')->nullable();
            $table->string('coo_signature_path')->nullable();
            $table->string('approver_signature_path')->nullable();
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_of_services');
    }
};
