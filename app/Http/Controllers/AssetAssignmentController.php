<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Departments;
use App\Models\Division;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class AssetAssignmentController extends Controller
{
    /**
     * Display available assets for assignment
     */
    public function index(Request $request)
    {
        $query = Asset::with(['category', 'department', 'division', 'location', 'assignedTo'])
            ->where('status', 'Available');

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

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $assets = $query->orderBy('created_at', 'desc')->paginate(25);
        $categories = \App\Models\AssetCategory::orderBy('name')->get();

        return view('asset-management.assignments.index', compact('assets', 'categories'));
    }

    /**
     * Show assignment form for a specific asset
     */
    public function create($assetId)
    {
        $asset = Asset::with(['category', 'department', 'division', 'location'])->findOrFail($assetId);
        
        if ($asset->status !== 'Available') {
            Alert::error('Error', 'This asset is not available for assignment.');
            return redirect()->route('asset-management.assignments.index');
        }

        $departments = Departments::orderBy('dept_name')->get();
        $divisions = Division::where('delete_status', 0)->orderBy('name')->get();
        $locations = Location::active()->ordered()->get();
        $users = User::where('status', 'active')->orderBy('username')->get();

        return view('asset-management.assignments.create', compact('asset', 'departments', 'divisions', 'locations', 'users'));
    }

    /**
     * Assign asset to user/department
     */
    public function store(Request $request, $assetId)
    {
        $asset = Asset::findOrFail($assetId);

        if ($asset->status !== 'Available') {
            Alert::error('Error', 'This asset is not available for assignment.');
            return redirect()->back();
        }

        $validated = $request->validate([
            'assigned_to_user_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'division_id' => 'nullable|exists:divisions,id',
            'location_id' => 'nullable|exists:locations,id',
            'line_manager_id' => 'nullable|exists:users,id',
            'custom_location' => 'nullable|string|max:255',
            'assignment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Record movement if asset was previously assigned
        if ($asset->assigned_to_user_id || $asset->department_id) {
            \App\Models\AssetMovement::create([
                'asset_id' => $asset->id,
                'from_department_id' => $asset->department_id,
                'from_division_id' => $asset->division_id,
                'from_location_id' => $asset->location_id,
                'from_custom_location' => $asset->custom_location,
                'from_user_id' => $asset->assigned_to_user_id,
                'to_department_id' => $validated['department_id'],
                'to_division_id' => $validated['division_id'],
                'to_location_id' => $validated['location_id'],
                'to_custom_location' => $validated['custom_location'],
                'to_user_id' => $validated['assigned_to_user_id'],
                'movement_date' => $validated['assignment_date'],
                'moved_by' => Auth::id(),
                'notes' => $validated['notes'] ?? 'Asset assigned',
            ]);
        }

        // Update asset
        $asset->update([
            'status' => 'Assigned',
            'assigned_to_user_id' => $validated['assigned_to_user_id'],
            'department_id' => $validated['department_id'],
            'division_id' => $validated['division_id'],
            'location_id' => $validated['location_id'],
            'line_manager_id' => $validated['line_manager_id'],
            'custom_location' => $validated['custom_location'],
            'notes' => $validated['notes'] ?? $asset->notes,
        ]);

        Alert::success('Success', 'Asset assigned successfully.');
        return redirect()->route('asset-management.assignments.index');
    }

    /**
     * Unassign/Return asset
     */
    public function unassign($assetId)
    {
        $asset = Asset::findOrFail($assetId);

        if ($asset->status !== 'Assigned') {
            Alert::error('Error', 'This asset is not currently assigned.');
            return redirect()->back();
        }

        // Record movement
        \App\Models\AssetMovement::create([
            'asset_id' => $asset->id,
            'from_department_id' => $asset->department_id,
            'from_division_id' => $asset->division_id,
            'from_location_id' => $asset->location_id,
            'from_custom_location' => $asset->custom_location,
            'from_user_id' => $asset->assigned_to_user_id,
            'to_department_id' => null,
            'to_division_id' => null,
            'to_location_id' => null,
            'to_custom_location' => null,
            'to_user_id' => null,
            'movement_date' => now(),
            'moved_by' => Auth::id(),
            'notes' => 'Asset returned/unassigned',
        ]);

        // Update asset
        $asset->update([
            'status' => 'Available',
            'assigned_to_user_id' => null,
            'line_manager_id' => null,
        ]);

        Alert::success('Success', 'Asset unassigned successfully.');
        return redirect()->back();
    }
}
