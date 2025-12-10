<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRetirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class AssetRetirementController extends Controller
{
    public function create($assetId)
    {
        $asset = Asset::with(['category', 'department', 'division', 'location'])->findOrFail($assetId);

        return view('asset-management.retirement.create', compact('asset'));
    }

    public function store(Request $request, $assetId)
    {
        $asset = Asset::findOrFail($assetId);

        if ($asset->status === 'Retired') {
            Alert::error('Error', 'Asset is already retired.');
            return redirect()->back();
        }

        $validated = $request->validate([
            'retirement_date' => 'required|date',
            'reason' => 'required|in:Obsolete,Damaged,Broken,End of Life,Sold,Donated',
            'notes' => 'nullable|string',
        ]);

        AssetRetirement::create($validated + [
            'asset_id' => $asset->id,
            'retired_by' => Auth::id(),
        ]);

        // Update asset status
        $asset->update(['status' => 'Retired']);

        Alert::success('Success', 'Asset retired successfully.');
        return redirect()->route('asset-management.assets.show', $asset->id);
    }

    public function index(Request $request)
    {
        $query = AssetRetirement::with(['asset.category', 'asset.department', 'asset.division', 'retiredBy']);

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        $retirements = $query->orderBy('retirement_date', 'desc')->paginate(25);

        return view('asset-management.retirement.index', compact('retirements'));
    }
}
