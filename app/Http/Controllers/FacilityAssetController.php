<?php

namespace App\Http\Controllers;

use App\Models\FacilityAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacilityAssetController extends Controller
{
    /**
     * Display a listing of assets.
     */
    public function index()
    {
        try {
            // Eager load facility locations
            $assets = FacilityAsset::with('facilityLocation')->get();

            // Return the view with assets
            return view('FacilityAsset.index', compact('assets'));
        } catch (\Exception $e) {
            // Log the error with full details
            Log::error('Failed to load facility assets page: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Optionally, show a friendly error page
            return response()->view('errors.custom', [
                'message' => 'Sorry, we could not load the facility assets page.'
            ], 500);
        }
    }

    //for making a new Facility Asset view
    public function create()
    {
        try {

            // Return the view for creating a new asset
            return view('FacilityAsset.create');
        } catch (\Exception $e) {

            // Log the error with full details
            Log::error('Failed to load create facility asset page: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Optionally, show a friendly error page
            return response()->view('errors.custom', [
                'message' => 'Sorry, we could not load the create facility asset page.'
            ], 500);
        }
    }

    //for storing the new Asset into the database
    public function store(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'name' => 'required|string|max:255',
                'facility_location_id' => 'required|exists:facility_locations,id',
                'purchase_date' => 'required|date',
                'value' => 'required|numeric|min:0',
            ]);

            // Create a new asset
            FacilityAsset::create($request->all());

            // Redirect back with success message
            return redirect()->route('facilityAssets.index')->with('success', 'Asset created successfully.');
        } catch (\Exception $e) {
            // Log the error with full details
            Log::error('Failed to create facility asset: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirect back with error message
            return redirect()->back()->withErrors([
                'error' => 'Failed to create asset. Please try again.'
            ])->withInput();
        }
    }
}
