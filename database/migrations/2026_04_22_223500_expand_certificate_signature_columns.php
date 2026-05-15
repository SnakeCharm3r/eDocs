<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE certificate_of_services MODIFY coo_signature_path LONGTEXT NULL');
            DB::statement('ALTER TABLE certificate_of_services MODIFY approver_signature_path LONGTEXT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE certificate_of_services ALTER COLUMN coo_signature_path TYPE TEXT');
            DB::statement('ALTER TABLE certificate_of_services ALTER COLUMN approver_signature_path TYPE TEXT');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE certificate_of_services MODIFY coo_signature_path VARCHAR(255) NULL');
            DB::statement('ALTER TABLE certificate_of_services MODIFY approver_signature_path VARCHAR(255) NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE certificate_of_services ALTER COLUMN coo_signature_path TYPE VARCHAR(255)');
            DB::statement('ALTER TABLE certificate_of_services ALTER COLUMN approver_signature_path TYPE VARCHAR(255)');
        }
    }
};