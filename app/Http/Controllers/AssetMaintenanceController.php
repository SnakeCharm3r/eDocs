<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class AssetMaintenanceController extends Controller
{
    public function create($assetId)
    {
        $asset = Asset::with(['category', 'department', 'division', 'location'])->findOrFail($assetId);
        $users = User::where('status', 'active')->orderBy('username')->get();

        return view('asset-management.maintenance.create', compact('asset', 'users'));
    }

    public function store(Request $request, $assetId)
    {
        $asset = Asset::findOrFail($assetId);

        $validated = $request->validate([
            'maintenance_type' => 'required|in:Repair,Upgrade,Routine,Inspection',
            'description' => 'required|string',
            'date_performed' => 'required|date',
            'performed_by' => 'nullable|string|max:255',
            'performed_by_user_id' => 'nullable|exists:users,id',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        AssetMaintenance::create($validated + ['asset_id' => $asset->id]);

        // Update asset status if it's a repair
        if ($validated['maintenance_type'] === 'Repair') {
            $asset->update(['status' => 'Maintenance']);
        }

        Alert::success('Success', 'Maintenance record created successfully.');
        return redirect()->route('asset-management.assets.show', $asset->id);
    }

    public function index(Request $request)
    {
        $query = AssetMaintenance::with(['asset.category', 'performedByUser']);

        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        if ($request->filled('maintenance_type')) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        $maintenance = $query->orderBy('date_performed', 'desc')->paginate(25);
        $assets = Asset::orderBy('asset_code')->get();

        return view('asset-management.maintenance.index', compact('maintenance', 'assets'));
    }
}
