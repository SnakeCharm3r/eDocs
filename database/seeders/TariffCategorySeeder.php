<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TariffCategory;

class TariffCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Donor',
                'description' => 'Donor-funded tariff category',
                'is_active' => true,
            ],
            [
                'name' => 'Insurance',
                'description' => 'Insurance-based tariff category',
                'is_active' => true,
            ],
            [
                'name' => 'Cash',
                'description' => 'Cash payment tariff category',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            TariffCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
