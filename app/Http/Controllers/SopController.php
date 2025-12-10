<?php

namespace App\Http\Controllers;

use App\Models\Sop;
use App\Models\User;
use App\Models\Departments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class SopController extends Controller
{

public function index(Request $request)
{
    $query = Sop::query()->with('departments');
    $query = Sop::with(['departments', 'updatedBy']);

    if ($request->filled('department_id')) {
        $query->whereHas('departments', function ($q) use ($request) {
            $q->where('departments.id', $request->department_id); // FIXED
        });
    }

    if ($request->filled('title')) {
        $query->where('title', 'like', '%' . $request->title . '%');
    }

    $sops = $query->get();
    $departments = Departments::all();

    return view('sops.index', compact('sops', 'departments'));
}




    public function create()
    {
        $departments = Departments::all();
        return view('sops.create', compact('departments'));
    }


public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'pdf' => 'required|file|mimes:pdf|max:5072',
        'departments' => 'required_without:global|array',  // Ensure departments are selected unless global is true
        'departments.*' => 'exists:departments,id',  // Validate that each selected department exists
        'global' => 'nullable|boolean',  // Allow the global flag to be optional
    ]);

    // Check if the SOP is global
    $isGlobal = $request->has('global') && $request->global == 1;

    // Handle file upload
    $pdfPath = $request->file('pdf')->store('sops', 'public');

    // Create the SOP entry
    $sop = Sop::create([
        'title' => $request->title,
        'global' => $isGlobal,
        'pdf_path' => $pdfPath,
        'created_by' => auth()->id(),  // Set the created_by field to the authenticated user's ID
    ]);

    // If the SOP is not global, attach departments with created_by field
    if (!$isGlobal) {
        foreach ($validated['departments'] as $departmentId) {
            $sop->departments()->attach($departmentId, [
                'created_by' => auth()->id(),  // Set created_by for each department-SOP relationship
            ]);
        }
    }

    // Redirect back to the SOP index with a success message
            Alert::success('success', 'SOP added successfully');
    return redirect()->route('sops.index')->with('success', 'SOP created successfully.');
}


    public function show(string $id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
$sop = SOP::with('departments')->findOrFail($id);
$departments = Departments::all();
return view('sops.edit', compact('sop', 'departments'));
    }

public function update(Request $request, $id)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'departments' => 'nullable|array',
        'departments.*' => 'exists:departments,id',
        'pdf' => 'nullable|file|mimes:pdf|max:5072',
    ]);

    $sop = SOP::findOrFail($id);
    $sop->title = $request->input('title');



    // Handle PDF upload
    if ($request->hasFile('pdf')) {
        $pdfPath = $request->file('pdf')->store('pdfs', 'public');
        $sop->pdf_path = $pdfPath;
    }

    $sop->save();
     Alert::success('success', 'SOP updated successfully');

    return redirect()->route('sops.index')->with('success', 'SOP updated successfully.');
}


    public function sops()
    {
        $user = auth()->user();
        $sops = DB::table('sops')
            ->join('departments', 'sops.deptId', '=', 'departments.id')
            ->where('departments.id', $user->deptId) // Filter by user's department
            ->select('sops.*', 'departments.dept_name')
            ->get();

        return view('sops.show', compact('sops'));
    }

    public function destroy(string $id)
    {
        $sop = Sop::findOrFail($id);
        $sopTitle = $sop->title;

        if ($sop->pdf_path && Storage::exists('public/' . $sop->pdf_path)) {
            Storage::delete('public/' . $sop->pdf_path);
        }

        $sop->delete();
        
        
     Alert::success('success', 'SOP deleted successfully');

        return redirect()->route('sops.index')->with('success', 'SOP deleted successfully.');
    }

}
