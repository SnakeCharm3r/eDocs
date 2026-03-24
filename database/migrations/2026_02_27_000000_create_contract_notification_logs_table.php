<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('notification_type'); // near_expiry, expired, manual_reminder, test_email, renewal_initiated
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_role')->nullable(); // line_manager, hec, procurement
            $table->unsignedBigInteger('recipient_user_id')->nullable();
            $table->string('status')->default('queued'); // queued, sent, failed
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable(); // user who triggered (null for automated)
            $table->string('trigger_source')->default('automated'); // automated, manual, test
            $table->timestamps();

            $table->index('contract_id');
            $table->index('notification_type');
            $table->index('status');
            $table->index('recipient_email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_notification_logs');
    }
};
