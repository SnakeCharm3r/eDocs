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
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('ccbrt_contracts', 'terms_of_reference_path')) {
                // Determine which column to use as reference
                // Try to use sla_document_path if it exists, otherwise use file_path (which exists in base table)
                $afterColumn = 'file_path'; // Default to file_path which exists in base table
                if (Schema::hasColumn('ccbrt_contracts', 'sla_document_path')) {
                    $afterColumn = 'sla_document_path';
                } elseif (Schema::hasColumn('ccbrt_contracts', 'terms_conditions_path')) {
                    $afterColumn = 'terms_conditions_path';
                } elseif (Schema::hasColumn('ccbrt_contracts', 'signed_contract_path')) {
                    $afterColumn = 'signed_contract_path';
                }
                
                $table->string('terms_of_reference_path')->nullable()->after($afterColumn);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->dropColumn('terms_of_reference_path');
        });
    }
};

