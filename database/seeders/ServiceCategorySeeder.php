<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Procedure',
                'description' => 'Medical procedures',
                'payment_types' => ['Cash', 'NHIF Standard', 'Jubilee', 'BOT', 'CRDB', 'Word Vision NMB', 'Premium Insurance'],
                'is_active' => true,
            ],
            [
                'name' => 'Lab Test',
                'description' => 'Laboratory tests',
                'payment_types' => ['Cash', 'NHIF Standard', 'Jubilee', 'BOT', 'CRDB', 'Word Vision NMB', 'Premium Insurance'],
                'is_active' => true,
            ],
            [
                'name' => 'Radiology',
                'description' => 'Radiology services',
                'payment_types' => ['Cash', 'NHIF Standard', 'Jubilee', 'BOT', 'CRDB', 'Word Vision NMB', 'Premium Insurance'],
                'is_active' => true,
            ],
            [
                'name' => 'Medicine',
                'description' => 'Medicines and pharmaceuticals',
                'payment_types' => ['Cash', 'NHIF Standard', 'NHIF Supplementary', 'BOT', 'CRDB', 'Word Vision NMB', 'Premium Insurance'],
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
