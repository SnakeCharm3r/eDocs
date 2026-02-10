<?php

namespace App\Http\Controllers;

use App\Models\TariffCategory;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class TariffCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage change request categories');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = TariffCategory::orderBy('name')->get();
        return view('change_request.categories.tariff.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('change_request.categories.tariff.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tariff_categories,name',
            'description' => 'nullable|string',
        ]);

        TariffCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        Alert::success('Success', 'Tariff category created successfully.');
        return redirect()->route('tariff-categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TariffCategory $tariffCategory)
    {
        return view('change_request.categories.tariff.edit', compact('tariffCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TariffCategory $tariffCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tariff_categories,name,' . $tariffCategory->id,
            'description' => 'nullable|string',
        ]);

        $tariffCategory->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        Alert::success('Success', 'Tariff category updated successfully.');
        return redirect()->route('tariff-categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TariffCategory $tariffCategory)
    {
        $tariffCategory->delete();
        Alert::success('Success', 'Tariff category deleted successfully.');
        return redirect()->route('tariff-categories.index');
    }
}
