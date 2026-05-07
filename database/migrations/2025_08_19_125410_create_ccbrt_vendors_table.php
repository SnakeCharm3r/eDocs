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
        Schema::create('ccbrt_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['internal', 'external', 'goods', 'services', 'goods_and_services'])
                ->default('external');
            $table->string('owner_name')->nullable();
            $table->string('address')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('industry')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ccbrt_vendors');
    }
};
