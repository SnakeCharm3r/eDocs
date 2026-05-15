<?php

namespace App\Http\Controllers;

use RealRashid\SweetAlert\Facades\Alert;
use App\Models\CcbrtVendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = CcbrtVendor::all();
        return view('vendors.index', compact('vendors'));
    }

    public function create()
    {
        //take me to the vendors view for addition
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,external,goods,services,goods_and_services',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|unique:ccbrt_vendors,contact_email',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        // Check if vendor already exists (optional - unique rule above already handles this)
        $existingVendor = CcbrtVendor::where('contact_email', $validated['contact_email'])->first();
        if ($existingVendor) {
            return redirect()->back()->withErrors(['contact_email' => 'This email is already associated with a vendor.'])->withInput();
        }

        // Create vendor using validated data
        CcbrtVendor::create($validated);

        // Redirect back with success message
        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully!');
    }
}
