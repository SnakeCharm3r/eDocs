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
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('ip_address', 45)->index();
            $table->string('user_agent')->nullable();
            $table->timestamp('attempted_at');
            $table->boolean('success')->default(false);
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });

        // Add lock fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('status');
            $table->timestamp('locked_until')->nullable()->after('is_locked');
            $table->integer('failed_login_attempts')->default(0)->after('locked_until');
            $table->timestamp('last_failed_login_at')->nullable()->after('failed_login_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_login_attempts');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_locked', 'locked_until', 'failed_login_attempts', 'last_failed_login_at']);
        });
    }
};
