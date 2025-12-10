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
        Schema::create('on_call_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('locum_month');
            $table->integer('locum_year');
            $table->integer('number_of_days');
            $table->decimal('total_hours', 8, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('total_amount_payable', 10, 2);
            $table->json('worked_days');
            $table->text('reason')->nullable();
            $table->foreignId('submited_by_incharge')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('education_level', ['Certificate', 'Diploma', 'Degree', 'Masters', 'PhD'])->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('on_call_requests');
    }
};
