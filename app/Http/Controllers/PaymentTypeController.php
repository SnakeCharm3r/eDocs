<?php

namespace App\Http\Controllers;

use App\Models\PaymentType;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PaymentTypeController extends Controller
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
        $paymentTypes = PaymentType::orderBy('name')->get();
        return view('payment_types.index', compact('paymentTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('payment_types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:payment_types,name',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        PaymentType::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        Alert::success('Success', 'Payment type created successfully.');
        return redirect()->route('payment-types.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentType $paymentType)
    {
        return view('payment_types.edit', compact('paymentType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentType $paymentType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:payment_types,name,' . $paymentType->id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $paymentType->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        Alert::success('Success', 'Payment type updated successfully.');
        return redirect()->route('payment-types.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentType $paymentType)
    {
        $paymentType->delete();
        Alert::success('Success', 'Payment type deleted successfully.');
        return redirect()->route('payment-types.index');
    }
}
