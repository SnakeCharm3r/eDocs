<?php

namespace App\Http\Controllers;

use App\Models\NetworkFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class NetworkFolderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $folders = NetworkFolder::where('delete_status', 0)
            ->orderBy('folder_name', 'asc')
            ->get();
        return view('network-folder.index', compact('folders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('network-folder.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_name' => 'required|string|max:255',
            'folder_status' => 'required|in:active,not_active',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        // Check if Network Folder with same name already exists
        $existingFolder = NetworkFolder::where('folder_name', $request->folder_name)
            ->where('delete_status', '!=', '1')
            ->first();

        if ($existingFolder) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Network Folder with this name already exists.');
        }

        $folder = NetworkFolder::create([
            'folder_name' => $request->input('folder_name'),
            'folder_status' => $request->input('folder_status'),
            'description' => $request->input('description'),
            'delete_status' => 0,
        ]);

        return redirect()->route('network-folder.index')->with('success', 'Network Folder added successfully.');
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
        $folder = NetworkFolder::findOrFail($id);
        return view('network-folder.edit', compact('folder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'folder_name' => 'required|string|max:255',
            'folder_status' => 'required|in:active,not_active',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        $folder = NetworkFolder::findOrFail($id);

        // Check if another Network Folder with same name already exists (excluding current one)
        $existingFolder = NetworkFolder::where('folder_name', $request->folder_name)
            ->where('id', '!=', $id)
            ->where('delete_status', '!=', '1')
            ->first();

        if ($existingFolder) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Network Folder with this name already exists.');
        }

        $folder->update([
            'folder_name' => $request->input('folder_name'),
            'folder_status' => $request->input('folder_status'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('network-folder.index')->with('success', 'Network Folder updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $folder = NetworkFolder::findOrFail($id);

        // Check if Network Folder is being used in IctAccessResource
        $usageCount = \App\Models\IctAccessResource::where('network_folder', $folder->folder_name)
            ->where('delete_status', '!=', '1')
            ->count();

        // Prevent deletion if Network Folder is in use
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete Network Folder. It is currently being used by ' . $usageCount . ' ICT access resource(s). Please remove all references first before deleting.'
            ], 400);
        }

        // Soft delete by setting delete_status to 1
        $folder->update([
            'delete_status' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Network Folder deleted successfully!'
        ]);
    }
}
