<?php

namespace App\Http\Controllers;

use App\Models\HMISAccessLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class HmisAccessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = HMISAccessLevel::where('delete_status', 0);

        if (request()->has('name') && request()->get('name') !== null) {
            $query->where('names', 'like', '%' . request()->get('name') . '%');
        }

        $hmis = $query->orderBy('names', 'asc')->get();
        return view('hmis-access.index', compact('hmis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hmis-access.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'names' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        // Check if HMIS access level with same name already exists
        $existingHmis = HMISAccessLevel::where('names', $request->names)
            ->where('delete_status', '!=', '1')
            ->first();
            
        if ($existingHmis) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'HMIS access level with this name already exists.');
        }

        $hmis = HMISAccessLevel::create([
            'names' => $request->input('names'),
            'status' => $request->input('status'),
            'delete_status' => 0,
        ]);
        
        return redirect()->route('hmis.index')->with('success', 'HMIS access level added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $hmis = HMISAccessLevel::findOrFail($id);
        return view('hmis-access.edit', compact('hmis'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'names' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        $hmis = HMISAccessLevel::findOrFail($id);
        
        // Check if another HMIS access level with same name already exists (excluding current one)
        $existingHmis = HMISAccessLevel::where('names', $request->names)
            ->where('id', '!=', $id)
            ->where('delete_status', '!=', '1')
            ->first();
            
        if ($existingHmis) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'HMIS access level with this name already exists.');
        }
        
        $hmis->update([
            'names' => $request->input('names'),
            'status' => $request->input('status'),
        ]);

        return redirect()->route('hmis.index')->with('success', 'HMIS access level updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $hmis = HMISAccessLevel::findOrFail($id);
        
        // Check if HMIS access level is being used in IctAccessResource
        // HMIS IDs are stored as JSON array in hmisId column
        $usageCount = \App\Models\IctAccessResource::where('delete_status', '!=', '1')
            ->get()
            ->filter(function($resource) use ($id) {
                $hmisIds = is_string($resource->hmisId) ? json_decode($resource->hmisId, true) : $resource->hmisId;
                return is_array($hmisIds) && in_array($id, $hmisIds);
            })
            ->count();
        
        // Prevent deletion if HMIS access level is in use
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete HMIS access level. It is currently being used by ' . $usageCount . ' ICT access resource(s). Please remove all references first before deleting.'
            ], 400);
        }
    
        // Soft delete by setting delete_status to 1
        $hmis->update([
            'delete_status' => 1
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'HMIS access level deleted successfully!'
        ]);
    }
 }
