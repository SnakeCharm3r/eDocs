<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ccbrt_vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('ccbrt_vendors', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_email');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'alternative_phone')) {
                $table->string('alternative_phone')->nullable()->after('contact_phone');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'website')) {
                $table->string('website')->nullable()->after('alternative_phone');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'attachments')) {
                $table->text('attachments')->nullable()->after('website');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'years_in_business')) {
                $table->integer('years_in_business')->nullable()->after('attachments');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'number_of_employees')) {
                $table->string('number_of_employees')->nullable()->after('years_in_business');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'country')) {
                $table->string('country')->nullable()->default('Tanzania')->after('number_of_employees');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('country');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'currency')) {
                $table->string('currency')->nullable()->default('TZS')->after('payment_terms');
            }
            if (!Schema::hasColumn('ccbrt_vendors', 'notes')) {
                $table->text('notes')->nullable()->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ccbrt_vendors', function (Blueprint $table) {
            $table->dropColumn([
                'contact_phone',
                'alternative_phone',
                'website',
                'attachments',
                'years_in_business',
                'number_of_employees',
                'country',
                'bank_name',
                'bank_account_number',
                'payment_terms',
                'currency',
                'notes',
            ]);
        });
    }
};
