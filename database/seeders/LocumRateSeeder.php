<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LocumRate;

class LocumRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rates = [
            [
                'education_level' => 'Certificate',
                'rate' => 50000,
                'is_active' => true,
                'notes' => 'Default rate for Certificate level education',
            ],
            [
                'education_level' => 'Enrolled_Certificate',
                'rate' => 60000,
                'is_active' => true,
                'notes' => 'Default rate for Enrolled Certificate level education',
            ],
            [
                'education_level' => 'Diploma',
                'rate' => 80000,
                'is_active' => true,
                'notes' => 'Default rate for Diploma level education',
            ],
            [
                'education_level' => 'Degree',
                'rate' => 100000,
                'is_active' => true,
                'notes' => 'Default rate for Degree level education',
            ],
            [
                'education_level' => 'Masters',
                'rate' => 120000,
                'is_active' => true,
                'notes' => 'Default rate for Masters level education',
            ],
        ];

        foreach ($rates as $rateData) {
            LocumRate::updateOrCreate(
                ['education_level' => $rateData['education_level']],
                $rateData
            );
        }

        $this->command->info('Locum rates seeded successfully!');
    }
}

