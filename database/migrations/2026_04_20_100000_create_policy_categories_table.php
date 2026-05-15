<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('section_number', 20)->nullable(); // e.g. "1", "2", "13"
            $table->string('icon', 100)->nullable(); // FontAwesome icon class
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_categories');
    }
};
