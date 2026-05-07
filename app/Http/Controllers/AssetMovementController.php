<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Departments;
use App\Models\Division;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class AssetMovementController extends Controller
{
    public function create($assetId)
    {
        $asset = Asset::with(['department', 'division', 'location', 'assignedTo'])->findOrFail($assetId);
        $departments = Departments::orderBy('dept_name')->get();
        $divisions = Division::where('delete_status', 0)->orderBy('name')->get();
        $locations = Location::active()->ordered()->get();
        $users = User::where('status', 'active')->orderBy('username')->get();

        return view('asset-management.movements.create', compact('asset', 'departments', 'divisions', 'locations', 'users'));
    }

    public function store(Request $request, $assetId)
    {
        $asset = Asset::findOrFail($assetId);

        $validated = $request->validate([
            'to_department_id' => 'nullable|exists:departments,id',
            'to_division_id' => 'nullable|exists:divisions,id',
            'to_location_id' => 'nullable|exists:locations,id',
            'to_custom_location' => 'nullable|string|max:255',
            'to_user_id' => 'nullable|exists:users,id',
            'movement_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Record movement
        $movement = AssetMovement::create([
            'asset_id' => $asset->id,
            'from_department_id' => $asset->department_id,
            'from_division_id' => $asset->division_id,
            'from_location_id' => $asset->location_id,
            'from_custom_location' => $asset->custom_location,
            'from_user_id' => $asset->assigned_to_user_id,
            'to_department_id' => $validated['to_department_id'],
            'to_division_id' => $validated['to_division_id'],
            'to_location_id' => $validated['to_location_id'],
            'to_custom_location' => $validated['to_custom_location'],
            'to_user_id' => $validated['to_user_id'],
            'movement_date' => $validated['movement_date'],
            'moved_by' => Auth::id(),
            'notes' => $validated['notes'],
        ]);

        // Update asset location
        $asset->update([
            'department_id' => $validated['to_department_id'],
            'division_id' => $validated['to_division_id'],
            'location_id' => $validated['to_location_id'],
            'custom_location' => $validated['to_custom_location'],
            'assigned_to_user_id' => $validated['to_user_id'],
        ]);

        Alert::success('Success', 'Asset movement recorded successfully.');
        return redirect()->route('asset-management.assets.show', $asset->id);
    }

    public function index(Request $request)
    {
        $query = AssetMovement::with([
            'asset.category',
            'fromDepartment',
            'toDepartment',
            'fromDivision',
            'toDivision',
            'fromLocation',
            'toLocation',
            'movedBy'
        ]);

        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        $movements = $query->orderBy('movement_date', 'desc')->paginate(25);
        $assets = Asset::orderBy('asset_code')->get();

        return view('asset-management.movements.index', compact('movements', 'assets'));
    }
}
