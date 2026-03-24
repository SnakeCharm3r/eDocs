<?php

namespace App\Http\Controllers;

use App\Models\LocumRate;
use App\Models\LocumAgreement;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LocumRateController extends Controller
{
    /**
     * Display a listing of the resource (with optional year filter by period).
     */
    public function index(Request $request)
    {
        $query = LocumRate::orderBy('start_date', 'desc')->orderBy('education_level');

        // Filter by year (derived from start_date), starting from 2025
        $availableYears = LocumRate::selectRaw('YEAR(start_date) as year')
            ->whereRaw('YEAR(start_date) >= 2025')
            ->distinct()
            ->orderByRaw('YEAR(start_date) DESC')
            ->pluck('year');

        $selectedYear = $request->get('year');
        if ($selectedYear !== null && $selectedYear !== '') {
            $query->whereYear('start_date', (int) $selectedYear);
        }

        $rates = $query->get();
        $today = Carbon::today();

        // "Valid to use until" date: from admin (Expire All / deadline setting). If set, stats use it; else fallback to max agreement end_date.
        $validUntilRaw = trim((string) (\App\Http\Controllers\SettingsController::getSetting('locum_expired_agreement_use_until', '') ?? ''));
        $validUntilDate = null;
        $validUntilDateFormatted = null;
        $graceDays = null;

        if ($validUntilRaw !== '') {
            try {
                $validUntilDate = Carbon::parse($validUntilRaw)->endOfDay();
                if ($today->lte($validUntilDate)) {
                    $validUntilDateFormatted = $validUntilDate->format('d M Y');
                    $graceDays = (int) $today->diffInDays($validUntilDate, false);
                }
            } catch (\Exception $e) {
            }
        }

        // Fallback: if no setting, use max end_date from agreements (e.g. after "Expire All" with 4 Feb)
        if ($validUntilDateFormatted === null) {
            $maxEnd = LocumAgreement::where('has_contract', true)
                ->whereNotNull('end_date')
                ->where('end_date', '>=', $today)
                ->max('end_date');
            if ($maxEnd) {
                try {
                    $validUntilDate = Carbon::parse($maxEnd)->endOfDay();
                    $validUntilDateFormatted = $validUntilDate->format('d M Y');
                    $graceDays = (int) $today->diffInDays($validUntilDate, false);
                } catch (\Exception $e) {
                }
            }
        }

        if ($graceDays === null) {
            $graceDays = 20; // fallback when no "valid until" date set
        }

        // Agreement statistics
        $agreementBase = LocumAgreement::where('has_contract', true);
        $totalAgreements = (clone $agreementBase)->count();

        $activeAgreements = (clone $agreementBase)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->count();

        // Valid for claiming: expired (end_date < today) but still within the admin-set "valid until" date
        if ($validUntilDate !== null && $today->lte($validUntilDate)) {
            $validForClaimingAgreements = (clone $agreementBase)
                ->whereNotNull('end_date')
                ->where('end_date', '<', $today)
                ->count();
        } else {
            $validForClaimingAgreements = (clone $agreementBase)
                ->whereNotNull('end_date')
                ->where('end_date', '<', $today)
                ->where('end_date', '>=', $today->copy()->subDays($graceDays))
                ->count();
        }

        $expiredAgreements = (clone $agreementBase)
            ->whereNotNull('end_date')
            ->where('end_date', '<', $today);
        if ($validUntilDate !== null && $today->lte($validUntilDate)) {
            $expiredAgreements = $expiredAgreements->whereRaw('1 = 0'); // none "fully" expired until after validUntilDate
        } else {
            $expiredAgreements = $expiredAgreements->where('end_date', '<', $today->copy()->subDays($graceDays));
        }
        $expiredAgreements = $expiredAgreements->count();

        return view('admin.locum-rates.index', compact(
            'rates', 'totalAgreements', 'activeAgreements', 'expiredAgreements',
            'validForClaimingAgreements', 'graceDays', 'validUntilDateFormatted',
            'availableYears', 'selectedYear', 'today'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.locum-rates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'education_level' => 'required|string|max:255',
                'year'            => 'required|integer|min:2025|max:2100',
                'rate'            => 'required|numeric|min:0',
                'is_active'       => 'nullable',
                'notes'           => 'nullable|string|max:1000',
            ]);

            $year = (int) $validated['year'];
            $validated['start_date'] = $year . '-01-01';
            $validated['end_date'] = $year . '-12-31';
            unset($validated['year']);

            // Unique per (education_level, start_date, end_date)
            $exists = LocumRate::where('education_level', $validated['education_level'])
                ->where('start_date', $validated['start_date'])
                ->where('end_date', $validated['end_date'])
                ->exists();
            if ($exists) {
                return redirect()->back()
                    ->withErrors(['year' => 'A rate for this education level already exists for the selected year.'])
                    ->withInput();
            }

            // Handle checkbox: if not present, default to true
            $validated['is_active'] = $request->has('is_active') ? true : false;

            LocumRate::create($validated);

            return redirect()
                ->route('locum-rates.index')
                ->with('success', 'Locum rate saved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error creating locum rate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token'])
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'An error occurred while saving the locum rate. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LocumRate $locumRate)
    {
        return view('admin.locum-rates.edit', compact('locumRate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LocumRate $locumRate)
    {
        $validated = $request->validate([
            'education_level' => 'required|string|max:255',
            'year'            => 'required|integer|min:2025|max:2100',
            'rate'            => 'required|numeric|min:0',
            'is_active'       => 'nullable',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $year = (int) $validated['year'];
        $validated['start_date'] = $year . '-01-01';
        $validated['end_date'] = $year . '-12-31';
        unset($validated['year']);

        // Unique per (education_level, start_date, end_date), excluding current row
        $exists = LocumRate::where('education_level', $validated['education_level'])
            ->where('start_date', $validated['start_date'])
            ->where('end_date', $validated['end_date'])
            ->where('id', '!=', $locumRate->id)
            ->exists();
        if ($exists) {
            return redirect()->back()
                ->withErrors(['year' => 'A rate for this education level already exists for the selected year.'])
                ->withInput();
        }

        // Handle checkbox: if not present, default to false
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $locumRate->update($validated);

        return redirect()
            ->route('locum-rates.index')
            ->with('success', 'Locum rate updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     * Only administrators can delete; HR can manage (view, create, edit) but not delete.
     */
    public function destroy(LocumRate $locumRate)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['Admin', 'Super-Admin', 'super-admin'])) {
            abort(403, 'Only administrators can delete locum rates.');
        }

        $locumRate->delete();

        return redirect()
            ->route('locum-rates.index')
            ->with('success', 'Locum rate deleted successfully.');
    }

    /**
     * Expire all existing locum agreements from a chosen "valid until" date.
     * Admin agrees that current contracts remain valid until that date; after that they are expired.
     * Note: Pending locum requests are NOT cancelled - they will continue through the approval process.
     * Only pending agreement workflows are cancelled.
     */
    public function expireAllAgreements(Request $request)
    {
        $validated = $request->validate([
            'valid_until' => 'required|date|after_or_equal:today',
        ]);
        $validUntil = Carbon::parse($validated['valid_until'])->format('Y-m-d');

        // Sync "use until" setting so Locum Claims and this page show the same date
        $exists = DB::table('system_settings')->where('key', 'locum_expired_agreement_use_until')->exists();
        if ($exists) {
            DB::table('system_settings')->where('key', 'locum_expired_agreement_use_until')->update(['value' => $validUntil, 'updated_at' => now()]);
        } else {
            DB::table('system_settings')->insert(['key' => 'locum_expired_agreement_use_until', 'value' => $validUntil, 'created_at' => now(), 'updated_at' => now()]);
        }
        Cache::forget('locum_expired_agreement_use_until');

        DB::beginTransaction();
        try {
            $currentUser = auth()->user();

            // Step 1: Set all agreements to end on the chosen "valid until" date (they remain valid until then, then expire)
            $expiredCount = DB::table('locum_agreements')
                ->where('has_contract', true)
                ->where(function ($query) use ($validUntil) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>', $validUntil);
                })
                ->update([
                    'end_date' => $validUntil,
                    'updated_at' => now(),
                ]);

            // Step 2: Cancel all pending locum agreement workflows (agreements being approved)
            // Note: We do NOT cancel pending locum requests - they will continue through approval
            $pendingAgreementWorkflows = Workflow::whereNotNull('locum_agreement_id')
                ->where('work_flow_completed', 0) // Not completed
                ->with(['locumAgreement'])
                ->get();

            $cancelledAgreementCount = 0;
            $agreementRejectionReason = "Agreement expired on 29 January but valid to use until {$validUntil}. Please create a new agreement.";

            // Cancel pending locum agreement workflows
            foreach ($pendingAgreementWorkflows as $workflow) {
                // Get the current pending history entry
                $pendingHistory = $workflow->histories()
                    ->where('status', 0) // Pending
                    ->whereNotNull('locum_agreement_status')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($pendingHistory) {
                    // Mark current pending history as rejected
                    $pendingHistory->update([
                        'status' => 2, // Rejected
                        'rejection_reason' => $agreementRejectionReason,
                        'attend_date' => now(),
                    ]);

                    // Create a new history entry to document the cancellation
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $pendingHistory->attended_by ?? $currentUser->id,
                        'attended_by' => $currentUser->id,
                        'step_name' => 'System Cancellation',
                        'action_taken' => 'Cancelled',
                        'status' => 2, // Rejected
                        'remark' => $agreementRejectionReason,
                        'rejection_reason' => $agreementRejectionReason,
                        'locum_agreement_status' => 4, // Rejected by HR status code
                        'attend_date' => now(),
                    ]);

                    // Update the agreement status to rejected
                    if ($workflow->locumAgreement) {
                        $workflow->locumAgreement->update([
                            'status' => 4, // Rejected by HR
                            'rejection_status' => $agreementRejectionReason,
                        ]);
                    }

                    // Mark workflow as completed and rejected
                    $workflow->update([
                        'work_flow_completed' => 1,
                        'work_flow_status' => 2, // Rejected
                    ]);

                    $cancelledAgreementCount++;
                }
            }

            DB::commit();

            Log::info('Expired all locum agreements and cancelled pending agreement workflows', [
                'valid_until' => $validUntil,
                'expired_agreements_count' => $expiredCount,
                'cancelled_agreements_count' => $cancelledAgreementCount,
                'expired_by' => $currentUser->id,
                'expired_at' => now(),
            ]);

            $validUntilFormatted = Carbon::parse($validUntil)->format('d M Y');
            $message = "Contracts expired on 29 January but are valid to use until {$validUntilFormatted}.";
            if ($cancelledAgreementCount > 0) {
                $message .= " Cancelled {$cancelledAgreementCount} pending agreement workflow(s).";
            }
            $message .= " Pending locum requests will continue through the approval process.";

            return redirect()
                ->route('locum-rates.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error expiring all locum agreements: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('locum-rates.index')
                ->withErrors(['error' => 'An error occurred while expiring agreements. Please try again.']);
        }
    }

    /**
     * Make HR-approved locum agreements active again: clear "valid until" setting only.
     * Does not change agreements' end_date; each agreement keeps its previous validity (existing end_date).
     */
    public function reactivateAgreements(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['Admin', 'Super-Admin', 'super-admin', 'hr'])) {
            abort(403, 'You do not have permission to reactivate agreements.');
        }

        DB::beginTransaction();
        try {
            // Clear "valid until" setting so grace period is removed; agreements use their existing end_date again
            $exists = DB::table('system_settings')->where('key', 'locum_expired_agreement_use_until')->exists();
            if ($exists) {
                DB::table('system_settings')->where('key', 'locum_expired_agreement_use_until')->update(['value' => '', 'updated_at' => now()]);
            }
            Cache::forget('locum_expired_agreement_use_until');

            DB::commit();

            Log::info('Reactivated locum agreements (cleared valid-until setting only)', [
                'reactivated_by' => $user->id,
            ]);

            $message = "Agreements have been made active again. The \"valid to use until\" date has been cleared; each agreement is valid according to its own end date.";

            return redirect()
                ->route('locum-rates.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reactivating agreements: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('locum-rates.index')
                ->withErrors(['error' => 'An error occurred while reactivating agreements. Please try again.']);
        }
    }
}


