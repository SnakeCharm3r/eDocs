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
        Schema::table('change_requests', function (Blueprint $table) {
            $table->enum('change_type', ['price', 'non_price'])->default('non_price')->after('userId');
            $table->text('change_category')->nullable()->after('change_type'); // e.g., 'System Feature', 'Medicine Price', 'Service Price', etc.
            $table->text('implementation_notes')->nullable()->after('supporting_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn(['change_type', 'change_category', 'implementation_notes']);
        });
    }
};
