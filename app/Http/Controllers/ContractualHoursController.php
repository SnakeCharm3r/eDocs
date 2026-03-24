<?php

namespace App\Http\Controllers;

use App\Models\ContractualHours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class ContractualHoursController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        
        $contractualHours = ContractualHours::where('year', $year)
            ->orderBy('month', 'asc')
            ->get();

        // Get available years
        $years = ContractualHours::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('contractual_hours.index', compact('contractualHours', 'year', 'years'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contractual_hours.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'working_days' => 'required|integer|min:1|max:31',
            'public_holidays' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Check if already exists
        $exists = ContractualHours::where('year', $request->year)
            ->where('month', $request->month)
            ->exists();

        if ($exists) {
            Alert::error('Error', 'Contractual hours for this month and year already exist. Please edit the existing record.');
            return back()->withInput();
        }

        // Calculate contractual hours (working days * 9)
        $contractualHours = $request->working_days * 9;

        // Parse public holidays
        $publicHolidays = null;
        if ($request->filled('public_holidays')) {
            $holidays = array_filter(array_map('trim', explode(',', $request->public_holidays)));
            $publicHolidays = !empty($holidays) ? $holidays : null;
        }

        ContractualHours::create([
            'year' => $request->year,
            'month' => $request->month,
            'working_days' => $request->working_days,
            'public_holidays' => $publicHolidays,
            'contractual_hours' => $contractualHours,
            'notes' => $request->notes,
            'created_by' => Auth::id(),
        ]);

        Alert::success('Success', 'Contractual hours created successfully.');
        return redirect()->route('contractual-hours.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contractualHours = ContractualHours::findOrFail($id);
        return view('contractual_hours.show', compact('contractualHours'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $contractualHours = ContractualHours::findOrFail($id);
        return view('contractual_hours.edit', compact('contractualHours'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'working_days' => 'required|integer|min:1|max:31',
            'public_holidays' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $contractualHours = ContractualHours::findOrFail($id);

        // Check if another record exists with same year/month
        $exists = ContractualHours::where('year', $request->year)
            ->where('month', $request->month)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            Alert::error('Error', 'Contractual hours for this month and year already exist.');
            return back()->withInput();
        }

        // Calculate contractual hours (working days * 9)
        $calculatedHours = $request->working_days * 9;

        // Parse public holidays
        $publicHolidays = null;
        if ($request->filled('public_holidays')) {
            $holidays = array_filter(array_map('trim', explode(',', $request->public_holidays)));
            $publicHolidays = !empty($holidays) ? $holidays : null;
        }

        $contractualHours->update([
            'year' => $request->year,
            'month' => $request->month,
            'working_days' => $request->working_days,
            'public_holidays' => $publicHolidays,
            'contractual_hours' => $calculatedHours,
            'notes' => $request->notes,
            'updated_by' => Auth::id(),
        ]);

        Alert::success('Success', 'Contractual hours updated successfully.');
        return redirect()->route('contractual-hours.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contractualHours = ContractualHours::findOrFail($id);
        $contractualHours->delete();

        Alert::success('Success', 'Contractual hours deleted successfully.');
        return redirect()->route('contractual-hours.index');
    }
}
