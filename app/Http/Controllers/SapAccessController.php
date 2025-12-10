<?php

namespace App\Http\Controllers;

use App\Models\SAPLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class SapAccessController extends Controller
{
    public function index()
    {
        $acc = SAPLevels::orderBy('access_name', 'asc')->get();
        return view('Asp-access.index', compact('acc'));
    }

    public function create()
    {
        return view('Asp-access.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'access_name' => 'required|string|max:255',
            'access_status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }
        
        // Check if SAP Level with same name already exists
        $existingSap = SAPLevels::where('access_name', $request->access_name)->first();
        if ($existingSap) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'SAP Access Level with this name already exists.');
        }

        $sap = SAPLevels::create([
            'access_name' => $request->input('access_name'),
            'access_status' => $request->input('access_status'),
        ]);
        
        return redirect()->route('sap.index')->with('success', 'SAP Access Level added successfully.');
    }

    public function edit(string $id)
    {
        $sap = SAPLevels::findOrFail($id);
        return view('Asp-access.edit', compact('sap'));
    }

    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'access_name' => 'required|string|max:255',
            'access_status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        $sap = SAPLevels::findOrFail($id);
        
        // Check if another SAP Level with same name already exists (excluding current one)
        $existingSap = SAPLevels::where('access_name', $request->access_name)
            ->where('id', '!=', $id)
            ->first();
            
        if ($existingSap) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'SAP Access Level with this name already exists.');
        }
        
        $sap->update([
            'access_name' => $request->input('access_name'),
            'access_status' => $request->input('access_status'),
        ]);

        return redirect()->route('sap.index')->with('success', 'SAP Access Level updated successfully.');
    }

    public function destroy(string $id)
    {
        $sap = SAPLevels::findOrFail($id);
        
        // Check if SAP Level is being used in IctAccessResource
        $usageCount = \App\Models\IctAccessResource::where('ASPId', $id)
            ->where('delete_status', '!=', '1')
            ->count();
        
        // Prevent deletion if SAP Level is in use
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete SAP Access Level. It is currently being used by ' . $usageCount . ' ICT access resource(s). Please remove all references first before deleting.'
            ], 400);
        }
    
        // Hard delete (SAPLevels doesn't have delete_status)
        $sap->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'SAP Access Level deleted successfully!'
        ]);
    }
}
