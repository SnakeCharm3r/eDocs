<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entities = [
            [
                'name' => 'Hospital',
                'code' => 'HOSP',
                'description' => 'CCBRT Hospital Entity',
                'status' => 'active',
            ],
            [
                'name' => 'Mabinti',
                'code' => 'MAB',
                'description' => 'Mabinti Entity',
                'status' => 'active',
            ],
            [
                'name' => 'NGO',
                'code' => 'NGO',
                'description' => 'CCBRT NGO Entity',
                'status' => 'active',
            ],
            [
                'name' => 'Academy',
                'code' => 'ACAD',
                'description' => 'CCBRT Academy Entity',
                'status' => 'active',
            ],
            [
                'name' => 'Moshi',
                'code' => 'MOSHI',
                'description' => 'Moshi Entity',
                'status' => 'active',
            ],
        ];

        foreach ($entities as $entity) {
            Division::updateOrCreate(
                ['code' => $entity['code']],
                $entity
            );
        }
    }
}
