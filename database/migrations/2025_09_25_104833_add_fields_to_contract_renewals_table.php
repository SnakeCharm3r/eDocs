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
        Schema::table('contract_renewals', function (Blueprint $table) {
            $table->enum('vendor_option', ['existing', 'new'])->default('existing')->after('vendor_id');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null')->after('vendor_option');            $table->string('vendor_review')->nullable();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->onDelete('set null');           
            $table->string('contract_type')->nullable()->after('contract_id');
            $table->string('category')->nullable()->after('contract_type');
            $table->bigInteger('cost')->nullable()->after('category');
            $table->integer('duration_months')->nullable();
            $table->string('likelihood_rating')->nullable;
            $table->string('impact_if_not_requested')->nullable();
            $table->string('overall_risk')->nullable();
            $table->text('service_requirements_text')->nullable();
            $table->string('service_requirements_file')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contract_renewals', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_option',
                'department_id',
                'vendor_review',
                'division_id',
                'contract_type',
                'category',
                'cost',
                'duration_months',
                'likelihood_rating',
                'impact_if_not_requested',
                'overall_risk',
                'requirement_toggle',
                'service_requirements_text',
                'service_requirements_file',
                'service_requirements_final',
            ]);
        });
    }
};
