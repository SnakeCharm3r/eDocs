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
        Schema::create('staff_contract_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('milestone');  // 3_months, 2_months, 1_month, 1_week, day_of
            $table->date('contract_end_date');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'milestone', 'contract_end_date'], 'staff_notif_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_contract_notifications');
    }
};
