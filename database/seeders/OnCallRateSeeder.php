<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OnCallRate;

class OnCallRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rates = [
            [
                'education_level' => 'Specialists Off site',
                'rate' => 40000,
                'is_active' => true,
                'notes' => 'Default rate for Specialists Off site',
            ],
            [
                'education_level' => 'Certificate',
                'rate' => 50000,
                'is_active' => true,
                'notes' => 'Default rate for Certificate level education',
            ],
            [
                'education_level' => 'Enrolled Certificate',
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
                'education_level' => 'Hospital Supervisor',
                'rate' => 140000,
                'is_active' => true,
                'notes' => 'Default rate for Hospital Supervisor',
            ],
            [
                'education_level' => 'Masters',
                'rate' => 120000,
                'is_active' => true,
                'notes' => 'Default rate for Masters level education',
            ],
        ];

        foreach ($rates as $rateData) {
            OnCallRate::updateOrCreate(
                ['education_level' => $rateData['education_level']],
                $rateData
            );
        }

        $this->command->info('On-call rates seeded successfully!');
    }
}
