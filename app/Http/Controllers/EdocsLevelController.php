<?php

namespace App\Http\Controllers;

use App\Models\EdocsLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class EdocsLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $edocs = EdocsLevel::where('delete_status', 0)
            ->orderBy('edocs_name', 'asc')
            ->get();
        return view('edocs-level.index', compact('edocs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('edocs-level.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'edocs_name' => 'required|string|max:255',
            'edocs_status' => 'required|in:active,not_active',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        // Check if eDocs Level with same name already exists
        $existingEdocs = EdocsLevel::where('edocs_name', $request->edocs_name)
            ->where('delete_status', '!=', '1')
            ->first();

        if ($existingEdocs) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'eDocs Level with this name already exists.');
        }

        $edocs = EdocsLevel::create([
            'edocs_name' => $request->input('edocs_name'),
            'edocs_status' => $request->input('edocs_status'),
            'delete_status' => 0,
        ]);

        Alert::success('Success', 'eDocs Level added successfully.');
        return redirect()->route('edocs.index');
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
        $edocs = EdocsLevel::findOrFail($id);
        return view('edocs-level.edit', compact('edocs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'edocs_name' => 'required|string|max:255',
            'edocs_status' => 'required|in:active,not_active',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        $edocs = EdocsLevel::findOrFail($id);

        // Check if another eDocs Level with same name already exists (excluding current one)
        $existingEdocs = EdocsLevel::where('edocs_name', $request->edocs_name)
            ->where('id', '!=', $id)
            ->where('delete_status', '!=', '1')
            ->first();

        if ($existingEdocs) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'eDocs Level with this name already exists.');
        }

        $edocs->update([
            'edocs_name' => $request->input('edocs_name'),
            'edocs_status' => $request->input('edocs_status'),
        ]);

        Alert::success('Success', 'eDocs Level updated successfully.');
        return redirect()->route('edocs.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $edocs = EdocsLevel::findOrFail($id);

        // Check if eDocs Level is being used in IctAccessResource
        $usageCount = \App\Models\IctAccessResource::whereJsonContains('edocs', (string)$id)
            ->where('delete_status', '!=', '1')
            ->count();

        // Prevent deletion if eDocs Level is in use
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete eDocs Level. It is currently being used by ' . $usageCount . ' ICT access resource(s). Please remove all references first before deleting.'
            ], 400);
        }

        // Soft delete by setting delete_status to 1
        $edocs->update([
            'delete_status' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'eDocs Level deleted successfully!'
        ]);
    }
}
