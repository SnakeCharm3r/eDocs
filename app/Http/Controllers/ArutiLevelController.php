<?php

namespace App\Http\Controllers;

use App\Models\ArutiLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class ArutiLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $aruti = ArutiLevel::where('delete_status', 0)
            ->orderBy('aruti_name', 'asc')
            ->get();
        return view('aruti-level.index', compact('aruti'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('aruti-level.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'aruti_name' => 'required|string|max:255',
            'aruti_status' => 'required|in:active,not_active',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        // Check if ARUT Level with same name already exists
        $existingAruti = ArutiLevel::where('aruti_name', $request->aruti_name)
            ->where('delete_status', '!=', '1')
            ->first();

        if ($existingAruti) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'ARUT Level with this name already exists.');
        }

        $aruti = ArutiLevel::create([
            'aruti_name' => $request->input('aruti_name'),
            'aruti_status' => $request->input('aruti_status'),
            'delete_status' => 0,
        ]);

        return redirect()->route('aruti.index')->with('success', 'ARUT Level added successfully.');
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
        $aruti = ArutiLevel::findOrFail($id);
        return view('aruti-level.edit', compact('aruti'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'aruti_name' => 'required|string|max:255',
            'aruti_status' => 'required|in:active,not_active',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        $aruti = ArutiLevel::findOrFail($id);

        // Check if another ARUT Level with same name already exists (excluding current one)
        $existingAruti = ArutiLevel::where('aruti_name', $request->aruti_name)
            ->where('id', '!=', $id)
            ->where('delete_status', '!=', '1')
            ->first();

        if ($existingAruti) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'ARUT Level with this name already exists.');
        }

        $aruti->update([
            'aruti_name' => $request->input('aruti_name'),
            'aruti_status' => $request->input('aruti_status'),
        ]);

        return redirect()->route('aruti.index')->with('success', 'ARUT Level updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $aruti = ArutiLevel::findOrFail($id);

        // Check if ARUT Level is being used in IctAccessResource
        $usageCount = \App\Models\IctAccessResource::where('aruti', $id)
            ->where('delete_status', '!=', '1')
            ->count();

        // Prevent deletion if ARUT Level is in use
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete ARUT Level. It is currently being used by ' . $usageCount . ' ICT access resource(s). Please remove all references first before deleting.'
            ], 400);
        }

        // Soft delete by setting delete_status to 1
        $aruti->update([
            'delete_status' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ARUT Level deleted successfully!'
        ]);
    }
}
