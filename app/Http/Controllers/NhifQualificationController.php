<?php

namespace App\Http\Controllers;

use App\Models\NhifQualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class NhifQualificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $nhif = NhifQualification::where('delete_status',0)->get();
        return view('nhif.index', compact('nhif'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('nhif.create');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,not_active',
        ]);
        
        if($validator->fails()){
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        // Check if NHIF qualification with same name already exists
        $existingNhif = NhifQualification::where('name', $request->name)
            ->where('delete_status', '!=', '1')
            ->first();
            
        if($existingNhif){
            return redirect()->back()
                ->withInput()
                ->with('error', 'NHIF Qualification with this name already exists.');
        }
        
        $nhif = NhifQualification::create([
            'name' => $request->input('name'),
            'status' => $request->input('status'),
            'delete_status' => 0,
        ]);
        
        return redirect()->route('nhif.index')->with('success', 'NHIF qualification added successfully.');
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
        $nhif = NhifQualification::findOrFail($id);
        return view('nhif.edit', compact('nhif'));
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,not_active',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        $nhif = NhifQualification::findOrFail($id);
        
        // Check if another NHIF qualification with same name already exists (excluding current one)
        $existingNhif = NhifQualification::where('name', $request->name)
            ->where('id', '!=', $id)
            ->where('delete_status', '!=', '1')
            ->first();
            
        if($existingNhif){
            return redirect()->back()
                ->withInput()
                ->with('error', 'NHIF Qualification with this name already exists.');
        }
        
        $nhif->update([
            'name' => $request->input('name'),
            'status' => $request->input('status'),
        ]);

        return redirect()->route('nhif.index')->with('success', 'NHIF qualification updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $nhif = NhifQualification::findOrFail($id);
        
        // Check if NHIF qualification is being used in IctAccessResource
        $usageCount = \App\Models\IctAccessResource::where('nhifId', $id)
            ->where('delete_status', '!=', '1')
            ->count();
        
        // Prevent deletion if NHIF qualification is in use
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete NHIF qualification. It is currently being used by ' . $usageCount . ' ICT access resource(s). Please remove all references first before deleting.'
            ], 400);
        }
    
        // Soft delete by setting delete_status to 1
        $nhif->update([
            'delete_status' => 1
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'NHIF Qualification deleted successfully!'
        ]);
    }
}
