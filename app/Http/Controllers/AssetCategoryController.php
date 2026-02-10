<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class AssetCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = AssetCategory::orderBy('name', 'asc')->paginate(20);
        return view('asset-management.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('asset-management.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:asset_categories,name',
            'tag_prefix' => 'required|string|max:10|unique:asset_categories,tag_prefix|regex:/^[A-Za-z0-9]+$/',
            'description' => 'nullable|string',
        ], [
            'tag_prefix.regex' => 'Tag prefix must contain only letters and numbers (no spaces or special characters).',
            'tag_prefix.unique' => 'This tag prefix is already in use by another category.',
        ]);
        
        // Convert tag_prefix to uppercase
        $validated['tag_prefix'] = strtoupper($validated['tag_prefix']);

        AssetCategory::create($validated);

        Alert::success('Success', 'Asset category created successfully.');
        return redirect()->route('asset-management.asset-categories.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = AssetCategory::with('assets')->findOrFail($id);
        return view('asset-management.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $category = AssetCategory::findOrFail($id);
        return view('asset-management.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = AssetCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:asset_categories,name,' . $id,
            'tag_prefix' => 'required|string|max:10|unique:asset_categories,tag_prefix,' . $id . '|regex:/^[A-Za-z0-9]+$/',
            'description' => 'nullable|string',
        ], [
            'tag_prefix.regex' => 'Tag prefix must contain only letters and numbers (no spaces or special characters).',
            'tag_prefix.unique' => 'This tag prefix is already in use by another category.',
        ]);
        
        // Convert tag_prefix to uppercase
        $validated['tag_prefix'] = strtoupper($validated['tag_prefix']);

        $category->update($validated);

        Alert::success('Success', 'Asset category updated successfully.');
        return redirect()->route('asset-management.asset-categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = AssetCategory::findOrFail($id);

        // Check if category has assets
        if ($category->assets()->count() > 0) {
            Alert::error('Error', 'Cannot delete category with existing assets.');
            return redirect()->back();
        }

        $category->delete();

        Alert::success('Success', 'Asset category deleted successfully.');
        return redirect()->route('asset-management.asset-categories.index');
    }
}
