<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentType;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentTypes = [
            'Cash',
            'NHIF Standard',
            'NHIF Supplementary',
            'Jubilee',
            'BOT',
            'CRDB',
            'Word Vision NMB',
            'Premium Insurance',
        ];

        foreach ($paymentTypes as $type) {
            PaymentType::firstOrCreate(
                ['name' => $type],
                [
                    'name' => $type,
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
