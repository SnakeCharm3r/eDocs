<?php

namespace App\Http\Controllers;

use App\Models\FacilityLocation;
use Illuminate\Http\Request;

class FacilityLocationController extends Controller
{
    /**
     * Display a listing of facility locations.
     */
    public function index()
    {
        $facilityLocations = FacilityLocation::all();
        return view('FacilityLocation.index');
    }

    /**
     * Show the form for creating a new facility location.
     */
    public function create()
    {
        return view('facility_locations.create');
    }

    /**
     * Store a newly created facility location in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        FacilityLocation::create($request->all());

        return redirect()->route('facility_locations.index')
                         ->with('success', 'Facility location created successfully.');
    }

    /**
     * Display the specified facility location.
     */
    public function show(FacilityLocation $facilityLocation)
    {
        return view('facility_locations.show', compact('facilityLocation'));
    }

    /**
     * Show the form for editing the specified facility location.
     */
    public function edit(FacilityLocation $facilityLocation)
    {
        return view('facility_locations.edit', compact('facilityLocation'));
    }

    /**
     * Update the specified facility location in storage.
     */
    public function update(Request $request, FacilityLocation $facilityLocation)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $facilityLocation->update($request->all());

        return redirect()->route('facility_locations.index')
                         ->with('success', 'Facility location updated successfully.');
    }

    /**
     * Remove the specified facility location from storage.
     */
    public function destroy(FacilityLocation $facilityLocation)
    {
        $facilityLocation->delete();
        return redirect()->route('facility_locations.index')
                         ->with('success', 'Facility location deleted successfully.');
    }
}
