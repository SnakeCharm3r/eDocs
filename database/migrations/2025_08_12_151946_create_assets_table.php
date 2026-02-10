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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Asset name
            $table->string('type')->nullable(); // e.g., Equipment, Vehicle, Furniture
            $table->string('description')->nullable(); // Description of the asset
            $table->string('model')->nullable(); // Model of the asset
            $table->string('manufacturer')->nullable(); // Manufacturer of the asset
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable(); // Purchase price of the asset
            $table->string('Vendor');// vendonr incharge of this facility maintenance
            $table->string('vendor_contact')->nullable();
            $table->unsignedBigInteger('facility_location_id'); // Foreign key
            $table->string('status')->default('available'); // available, in_use, maintenance
            $table->timestamps();
            //$table->foreign('facility_location_id')->references('id')->on('facility_locations');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }


};
