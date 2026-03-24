<?php

namespace App\Http\Controllers;

use App\Models\ExternalSystemLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExternalSystemLinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $links = ExternalSystemLink::orderBy('display_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.external-system-links.index', compact('links'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.external-system-links.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('external-system-logos', 'public');
            $validated['logo'] = $logoPath;
        }

        ExternalSystemLink::create($validated);

        return redirect()->route('external-system-links.index')
            ->with('success', 'External system link created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ExternalSystemLink $externalSystemLink)
    {
        return view('admin.external-system-links.show', compact('externalSystemLink'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExternalSystemLink $externalSystemLink)
    {
        return view('admin.external-system-links.edit', compact('externalSystemLink'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExternalSystemLink $externalSystemLink)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($externalSystemLink->logo && Storage::disk('public')->exists($externalSystemLink->logo)) {
                Storage::disk('public')->delete($externalSystemLink->logo);
            }
            $logoPath = $request->file('logo')->store('external-system-logos', 'public');
            $validated['logo'] = $logoPath;
        }

        $externalSystemLink->update($validated);

        return redirect()->route('external-system-links.index')
            ->with('success', 'External system link updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExternalSystemLink $externalSystemLink)
    {
        // Delete logo file if exists
        if ($externalSystemLink->logo && Storage::disk('public')->exists($externalSystemLink->logo)) {
            Storage::disk('public')->delete($externalSystemLink->logo);
        }

        $externalSystemLink->delete();

        return redirect()->route('external-system-links.index')
            ->with('success', 'External system link deleted successfully.');
    }
}
