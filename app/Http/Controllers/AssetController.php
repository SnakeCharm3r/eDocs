<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Departments;
use App\Models\Division;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;
use Carbon\Carbon;
use App\Imports\AssetsImport;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Asset::with(['category', 'department', 'division', 'location', 'assignedTo', 'lineManager']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('asset_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhereHas('category', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        $assets = $query->orderBy('created_at', 'desc')->paginate(25);
        $categories = AssetCategory::orderBy('name')->get();
        $departments = Departments::orderBy('dept_name')->get();
        $divisions = Division::where('delete_status', 0)->orderBy('name')->get();
        $locations = Location::active()->ordered()->get();

        return view('asset-management.assets.index', compact('assets', 'categories', 'departments', 'divisions', 'locations'));
    }

    /**
     * Show asset tag management
     */
    public function tagManagement()
    {
        $totalAssets = Asset::count();
        
        // Get categories with their next tags
        $categories = AssetCategory::withCount('assets')->get();
        $categoryTags = [];
        foreach ($categories as $category) {
            $prefix = $category->tag_prefix ?? 'AST';
            $nextNumber = $category->assets_count + 1;
            $categoryTags[$category->id] = strtoupper($prefix) . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }
        
        // Get recent assets
        $recentAssets = Asset::orderBy('created_at', 'desc')->take(10)->get(['asset_code', 'created_at']);
        
        return view('asset-management.assets.tag-management', compact('totalAssets', 'categoryTags', 'categories', 'recentAssets'));
    }

    /**
     * Get next tag for a category (AJAX)
     */
    public function getNextTag($categoryId)
    {
        $category = AssetCategory::findOrFail($categoryId);
        $prefix = $category->tag_prefix ?? 'AST';
        
        // Count assets in this category
        $assetCount = Asset::where('category_id', $categoryId)->count();
        
        // Generate next tag (4-digit format like LT-0206)
        $nextNumber = $assetCount + 1;
        $nextTag = strtoupper($prefix) . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        return response()->json([
            'next_tag' => $nextTag,
            'prefix' => strtoupper($prefix),
            'count' => $assetCount
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = AssetCategory::orderBy('name')->get();
        $departments = Departments::orderBy('dept_name')->get();
        $divisions = Division::where('delete_status', 0)->orderBy('name')->get();
        $locations = Location::active()->ordered()->get();
        $users = User::where('status', 'active')->orderBy('username')->get();

        // Generate next asset tag (will be updated via AJAX when category is selected)
        $nextCode = 'AST-0001';

        return view('asset-management.assets.create', compact('categories', 'departments', 'divisions', 'locations', 'users', 'nextCode'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_code' => 'required|string|max:255|unique:assets,asset_code',
            'category_id' => 'required|exists:asset_categories,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'specifications' => 'nullable|string',
            'status' => 'required|in:Available,Assigned,Maintenance,Retired',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date|after_or_equal:purchase_date',
            'department_id' => 'nullable|exists:departments,id',
            'division_id' => 'nullable|exists:divisions,id',
            'location_id' => 'nullable|exists:locations,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'line_manager_id' => 'nullable|exists:users,id',
            'custom_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        Asset::create($validated);

        Alert::success('Success', 'Asset created successfully.');
        return redirect()->route('asset-management.assets.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $asset = Asset::with([
            'category',
            'department',
            'division',
            'location',
            'assignedTo',
            'lineManager',
            'movements.fromDepartment',
            'movements.toDepartment',
            'movements.fromDivision',
            'movements.toDivision',
            'movements.fromLocation',
            'movements.toLocation',
            'movements.movedBy',
            'maintenance.performedByUser',
            'retirement.retiredBy'
        ])->findOrFail($id);

        return view('asset-management.assets.show', compact('asset'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $asset = Asset::findOrFail($id);
        $categories = AssetCategory::orderBy('name')->get();
        $departments = Departments::orderBy('dept_name')->get();
        $divisions = Division::where('delete_status', 0)->orderBy('name')->get();
        $locations = Location::active()->ordered()->get();
        $users = User::where('status', 'active')->orderBy('username')->get();

        return view('asset-management.assets.edit', compact('asset', 'categories', 'departments', 'divisions', 'locations', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'asset_code' => 'required|string|max:255|unique:assets,asset_code,' . $id,
            'category_id' => 'required|exists:asset_categories,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'specifications' => 'nullable|string',
            'status' => 'required|in:Available,Assigned,Maintenance,Retired',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date|after_or_equal:purchase_date',
            'department_id' => 'nullable|exists:departments,id',
            'division_id' => 'nullable|exists:divisions,id',
            'location_id' => 'nullable|exists:locations,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'line_manager_id' => 'nullable|exists:users,id',
            'custom_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $asset->update($validated);

        Alert::success('Success', 'Asset updated successfully.');
        return redirect()->route('asset-management.assets.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $asset = Asset::findOrFail($id);

        if ($asset->movements()->count() > 0 || $asset->maintenance()->count() > 0) {
            Alert::error('Error', 'Cannot delete asset with existing movements or maintenance records.');
            return redirect()->back();
        }

        $asset->delete();

        Alert::success('Success', 'Asset deleted successfully.');
        return redirect()->route('asset-management.assets.index');
    }

    /**
     * Export assets to Excel
     */
    public function export(Request $request)
    {
        $query = Asset::with(['category', 'department', 'division', 'location', 'assignedTo', 'lineManager']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('asset_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $assets = $query->get();

        $export = new class($assets) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping {
            protected $assets;

            public function __construct($assets)
            {
                $this->assets = $assets;
            }

            public function collection()
            {
                return $this->assets;
            }

            public function headings(): array
            {
                return [
                    'Asset Tag',
                    'Category',
                    'Brand',
                    'Model',
                    'Status',
                    'Purchase Date',
                    'Warranty Expiry',
                    'Entity',
                    'Department',
                    'Location/Branch',
                    'Assigned To',
                    'Line Manager',
                    'Custom Location',
                    'Notes'
                ];
            }

            public function map($asset): array
            {
                return [
                    $asset->asset_code,
                    $asset->category->name ?? 'N/A',
                    $asset->brand ?? 'N/A',
                    $asset->model ?? 'N/A',
                    $asset->status,
                    $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : 'N/A',
                    $asset->warranty_expiry ? $asset->warranty_expiry->format('Y-m-d') : 'N/A',
                    $asset->division->name ?? 'N/A',
                    $asset->department->dept_name ?? 'N/A',
                    $asset->location->name ?? 'N/A',
                    $asset->assignedTo ? $asset->assignedTo->username : 'N/A',
                    $asset->lineManager ? $asset->lineManager->username : 'N/A',
                    $asset->custom_location ?? 'N/A',
                    $asset->notes ?? 'N/A',
                ];
            }
        };

        $filename = 'assets_export_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download($export, $filename);
    }

    /**
     * Show import form
     */
    public function showImport()
    {
        return view('asset-management.assets.import');
    }

    /**
     * Import assets from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new \App\Imports\AssetsImport, $request->file('file'));

            Alert::success('Success', "Assets imported successfully.");
            return redirect()->route('asset-management.assets.index');
        } catch (\Exception $e) {
            Alert::error('Error', 'Import failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}
