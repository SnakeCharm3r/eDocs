<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ServiceCategoryController extends Controller
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
        $categories = ServiceCategory::orderBy('name')->get();
        return view('change_request.categories.service.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $paymentTypes = PaymentType::where('is_active', true)->orderBy('name')->get();
        return view('change_request.categories.service.create', compact('paymentTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:service_categories,name',
            'description' => 'nullable|string',
            'payment_types' => 'nullable|array',
            'payment_types.*' => 'string',
        ]);

        ServiceCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'payment_types' => $request->payment_types ?? [],
            'is_active' => $request->has('is_active'),
        ]);

        Alert::success('Success', 'Service category created successfully.');
        return redirect()->route('service-categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceCategory $serviceCategory)
    {
        $paymentTypes = PaymentType::where('is_active', true)->orderBy('name')->get();
        return view('change_request.categories.service.edit', compact('serviceCategory', 'paymentTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:service_categories,name,' . $serviceCategory->id,
            'description' => 'nullable|string',
            'payment_types' => 'nullable|array',
            'payment_types.*' => 'string',
        ]);

        $serviceCategory->update([
            'name' => $request->name,
            'description' => $request->description,
            'payment_types' => $request->payment_types ?? [],
            'is_active' => $request->has('is_active'),
        ]);

        Alert::success('Success', 'Service category updated successfully.');
        return redirect()->route('service-categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceCategory $serviceCategory)
    {
        $serviceCategory->delete();
        Alert::success('Success', 'Service category deleted successfully.');
        return redirect()->route('service-categories.index');
    }
}
