<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {

            // 1. Drop the foreign key if it exists
            $createTable = DB::select("SHOW CREATE TABLE divisions")[0]->{'Create Table'};

            if (str_contains($createTable, 'divisions_department_foreign')) {
                $table->dropForeign(['department']);
                $table->dropColumn('department');
            } elseif (Schema::hasColumn('divisions', 'department')) {
                // If FK does not exist but column does, drop the column anyway
                $table->dropColumn('department');
            }

            // 2. Add JSON column for multiple departments if it does not exist
            if (!Schema::hasColumn('divisions', 'departments')) {
                $table->json('departments')->nullable()->after('code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {

            // Drop JSON column if it exists
            if (Schema::hasColumn('divisions', 'departments')) {
                $table->dropColumn('departments');
            }

            // Optionally, you could recreate the old column here if needed
            // $table->foreignId('department')->nullable()->constrained('departments')->onDelete('set null');
        });
    }
};
