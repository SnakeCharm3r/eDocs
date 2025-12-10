<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Departments;
use App\Models\Division;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                // Get or create category
                $category = AssetCategory::firstOrCreate(
                    ['name' => $row['category'] ?? 'Uncategorized'],
                    [
                        'description' => 'Imported category',
                        'tag_prefix' => 'AST' // Default prefix if not set
                    ]
                );

                // Get division if provided
                $division = null;
                if (!empty($row['division'])) {
                    $division = Division::where('name', $row['division'])->first();
                }

                // Get department if provided
                $department = null;
                if (!empty($row['department'])) {
                    $department = Departments::where('dept_name', $row['department'])->first();
                }

                // Get location if provided
                $location = null;
                if (!empty($row['location'])) {
                    $location = Location::where('name', $row['location'])->first();
                }

                // Get assigned user if provided
                $assignedUser = null;
                if (!empty($row['assigned_to'])) {
                    $assignedUser = User::where('username', $row['assigned_to'])->orWhere('ccbrt_code', $row['assigned_to'])->first();
                }

                // Get line manager if provided
                $lineManager = null;
                if (!empty($row['line_manager'])) {
                    $lineManager = User::where('username', $row['line_manager'])->orWhere('ccbrt_code', $row['line_manager'])->first();
                }

                // Generate tag if not provided
                $assetCode = $row['asset_code'] ?? null;
                if (!$assetCode) {
                    $prefix = $category->tag_prefix ?? 'AST';
                    $assetCount = Asset::where('category_id', $category->id)->count();
                    $nextNumber = $assetCount + 1;
                    $assetCode = strtoupper($prefix) . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                }

                Asset::create([
                    'asset_code' => $assetCode,
                    'category_id' => $category->id,
                    'brand' => $row['brand'] ?? null,
                    'model' => $row['model'] ?? null,
                    'specifications' => $row['specifications'] ?? null,
                    'status' => $row['status'] ?? 'Available',
                    'purchase_date' => !empty($row['purchase_date']) ? Carbon::parse($row['purchase_date']) : null,
                    'warranty_expiry' => !empty($row['warranty_expiry']) ? Carbon::parse($row['warranty_expiry']) : null,
                    'division_id' => $division?->id,
                    'department_id' => $department?->id,
                    'location_id' => $location?->id,
                    'assigned_to_user_id' => $assignedUser?->id,
                    'line_manager_id' => $lineManager?->id,
                    'custom_location' => $row['custom_location'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]);
            } catch (\Exception $e) {
                \Log::error('Asset import error: ' . $e->getMessage(), ['row' => $row->toArray()]);
            }
        }
    }
}
