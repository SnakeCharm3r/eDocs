<?php

namespace App\Http\Controllers;

use App\Models\OnCallRate;
use Illuminate\Http\Request;

class OnCallRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $yearParam = $request->get('year');
        $dateParam = $request->get('date', date('Y-m-d'));
        // Support year filter: use first day of year as selected date
        $selectedDate = $yearParam ? $yearParam . '-01-01' : $dateParam;
        $selectedDateCarbon = \Carbon\Carbon::parse($selectedDate);
        $selectedYear = (int) $selectedDateCarbon->format('Y');

        // Distinct years from rates (start_date) for dropdown, plus current year
        $yearsFromRates = OnCallRate::query()
            ->orderBy('start_date', 'desc')
            ->get()
            ->pluck('start_date')
            ->filter()
            ->map(fn ($d) => $d instanceof \Carbon\Carbon ? $d->year : (int) date('Y', strtotime($d)))
            ->unique()
            ->values()
            ->toArray();
        $availableYears = array_values(array_unique(array_merge($yearsFromRates, [(int) date('Y')])));
        rsort($availableYears);

        // All rates that overlap the selected year (for managing all on-call rates: active and not active)
        $yearStart = $selectedDateCarbon->copy()->startOfYear()->format('Y-m-d');
        $yearEnd = $selectedDateCarbon->copy()->endOfYear()->format('Y-m-d');
        $rates = OnCallRate::where('start_date', '<=', $yearEnd)
            ->where('end_date', '>=', $yearStart)
            ->orderBy('education_level')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('admin.oncall-rates.index', compact('rates', 'selectedDate', 'selectedDateCarbon', 'availableYears', 'selectedYear'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.oncall-rates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'education_level' => 'required|string|max:255',
                'start_date'      => 'required|date',
                'end_date'        => 'required|date|after_or_equal:start_date',
                'rate'            => 'required|numeric|min:0',
                'is_active'       => 'nullable',
                'notes'           => 'nullable|string|max:1000',
            ]);

            // Check unique constraint on (education_level, start_date, end_date)
            $exists = OnCallRate::where('education_level', $validated['education_level'])
                ->where('start_date', $validated['start_date'])
                ->where('end_date', $validated['end_date'])
                ->exists();

            if ($exists) {
                return redirect()
                    ->back()
                    ->withErrors(['education_level' => 'A rate for this education level and period already exists.'])
                    ->withInput();
            }

            // Handle checkbox: if not present, default to true
            $validated['is_active'] = $request->has('is_active') ? true : false;

            OnCallRate::create($validated);

            return redirect()
                ->route('oncall-rates.index', ['date' => $validated['start_date']])
                ->with('success', 'On-call rate saved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error creating on-call rate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token'])
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'An error occurred while saving the on-call rate. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OnCallRate $oncall_rate)
    {
        return view('admin.oncall-rates.edit', compact('oncall_rate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OnCallRate $oncall_rate)
    {
        $validated = $request->validate([
            'education_level' => 'required|string|max:255',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'rate'            => 'required|numeric|min:0',
            'is_active'       => 'nullable',
            'notes'           => 'nullable|string|max:1000',
        ]);

        // Check unique constraint on (education_level, start_date, end_date) excluding current record
        $exists = OnCallRate::where('education_level', $validated['education_level'])
            ->where('start_date', $validated['start_date'])
            ->where('end_date', $validated['end_date'])
            ->where('id', '!=', $oncall_rate->id)
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withErrors(['education_level' => 'A rate for this education level and period already exists.'])
                ->withInput();
        }

        // Handle checkbox: if not present, default to false
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $oncall_rate->update($validated);

        return redirect()
            ->route('oncall-rates.index', ['date' => $validated['start_date']])
            ->with('success', 'On-call rate updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     * Only administrators can delete; HR can manage (view, create, edit) but not delete.
     */
    public function destroy(OnCallRate $oncall_rate)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['Admin', 'Super-Admin', 'super-admin'])) {
            abort(403, 'Only administrators can delete on-call rates.');
        }

        $oncall_rate->delete();

        return redirect()
            ->route('oncall-rates.index')
            ->with('success', 'On-call rate deleted successfully.');
    }
}
