<?php

namespace App\Http\Controllers;

use App\Models\HecContract;
use App\Models\User;
use App\Models\HecProfile;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class HecContractsController extends Controller
{
    /**
     * Display a listing of HEC contracts
     * No approval workflow here – just stored contracts.
     */
    public function index()
    {
        $contracts = HecContract::with(['contractOwner', 'creator', 'division'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate statistics
        $totalContracts = HecContract::count();
        $activeContracts = HecContract::where('status', 'active')->count();
        $expiredContracts = HecContract::where('status', 'expired')->count();
        $soonToExpireContracts = HecContract::where('status', 'soonToExpire')->count();

        return view('hec_contracts.index', compact(
            'contracts',
            'totalContracts',
            'activeContracts',
            'expiredContracts',
            'soonToExpireContracts'
        ));
    }

    /**
     * Show the form for creating a new HEC contract
     */
    public function create()
    {
        // Contract Owner is a HEC member (COO, CFO, CMS, CCDRO) - get by roles
        // Use the dedicated HEC role names to ensure all members appear
        $hecMembers = User::whereHas('roles', function ($query) {
            // Support both legacy and current HEC role names (ICT form uses plain names)
            $query->whereIn('name', [
                'hec-cfo',
                'hec-coo',
                'hec-cms',
                'hec-ccd',
                'hec-ccdro',
                'ccdro',
                'cfo',
                'coo',
                'cms',
                'crhdo', // legacy spelling from ICT flow
            ]);
        })
            // Some legacy users may not have status set; allow null as active
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->orderBy('fname')
            ->get();

        // Get all CCBRT entities (divisions)
        $divisions = Division::where('status', 'active')
            ->orWhereNull('status')
            ->orderBy('name')
            ->get();

        return view('hec_contracts.create', compact('hecMembers', 'divisions'));
    }

    /**
     * Store a newly created HEC contract
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string|max:255',
            'division_id' => 'nullable|integer|exists:divisions,id',
            // HEC member owner is optional; if none, we must have an email for notifications
            'contract_owner_id' => 'nullable|integer|exists:users,id',
            'owner_email' => 'nullable|email|required_without:contract_owner_id',
            'contract_source' => 'nullable|string|in:new,existing',
            'cost' => 'required|numeric',
            'currency' => 'required|string|max:10',
            'duration_months' => 'required|integer',
            'status' => 'required|string|max:255',
            'contract_number' => 'nullable|string|unique:hec_contracts,contract_number',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'impact_if_not_requested' => 'required|string|in:Low,Medium,High',
            'likelihood_rating' => 'required|string|in:Low,Medium,High',
            // Allow up to 10MB per PDF attachment
            'file_path' => 'nullable|mimes:pdf|max:10240',
            'signed_contract_path' => 'nullable|mimes:pdf|max:10240',
            'terms_conditions_path' => 'nullable|mimes:pdf|max:10240',
            'sla_document_path' => 'nullable|mimes:pdf|max:10240',
        ]);

        // Generate contract number if not provided
        $contractNumber = $request->contract_number;
        if (!$contractNumber) {
            $year = date('Y');
            $lastContract = HecContract::whereYear('created_at', $year)
                ->whereNotNull('contract_number')
                ->where('contract_number', 'like', 'HEC-CNT-' . $year . '-%')
                ->latest()
                ->first();

            $sequence = 1;
            if ($lastContract && $lastContract->contract_number) {
                $parts = explode('-', $lastContract->contract_number);
                if (count($parts) >= 4 && is_numeric($parts[3])) {
                    $sequence = (int) $parts[3] + 1;
                }
            }

            do {
                $contractNumber = 'HEC-CNT-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                $exists = HecContract::where('contract_number', $contractNumber)->exists();
                if ($exists) {
                    $sequence++;
                }
            } while ($exists);
        }

        // Handle file uploads
        $fileFields = [
            'file_path' => 'contracts',
            'signed_contract_path' => 'contracts',
            'terms_conditions_path' => 'contracts',
            'sla_document_path' => 'contracts',
        ];

        $filePaths = [];
        foreach ($fileFields as $field => $folder) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePaths[$field] = $file->storeAs($folder, $fileName, 'public');
            }
        }

        $contract = HecContract::create([
            'contract_number' => $contractNumber,
            'title' => $request->title,
            'description' => $request->description,
            'contract_type' => $request->contract_type,
            'division_id' => $request->division_id,
            'contract_owner_id' => $request->contract_owner_id,
            'owner_email' => $request->owner_email,
            'contract_source' => $request->contract_source ?: 'new',
            'cost' => $request->cost,
            'currency' => $request->currency ?? 'TZS',
            'duration_months' => $request->duration_months,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'impact_if_not_requested' => $request->impact_if_not_requested,
            'likelihood_rating' => $request->likelihood_rating,
            'created_by' => Auth::id(),
        ] + $filePaths);

        return redirect()->route('hec-contracts.index')
            ->with('success', 'HEC Contract created successfully.');
    }

    /**
     * Display the specified HEC contract
     */
    public function show($id)
    {
        $contract = HecContract::with(['contractOwner', 'creator', 'division'])->findOrFail($id);
        return view('hec_contracts.show', compact('contract'));
    }

    /**
     * Show the form for editing the specified HEC contract
     */
    public function edit($id)
    {
        $contract = HecContract::findOrFail($id);

        // Get HEC members by roles
        $hecMembers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', [
                'hec-cfo',
                'hec-coo',
                'hec-cms',
                'hec-ccd',
                'hec-ccdro',
                'ccdro',
                'cfo',
                'coo',
                'cms',
                'crhdo',
            ]);
        })
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->orderBy('fname')
            ->get();

        // Get all CCBRT entities (divisions)
        $divisions = Division::where('status', 'active')
            ->orWhereNull('status')
            ->orderBy('name')
            ->get();

        return view('hec_contracts.edit', compact('contract', 'hecMembers', 'divisions'));
    }

    /**
     * Update the specified HEC contract
     */
    public function update(Request $request, $id)
    {
        $contract = HecContract::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string|max:255',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'contract_owner_id' => 'nullable|integer|exists:users,id',
            'owner_email' => 'nullable|email|required_without:contract_owner_id',
            'contract_source' => 'nullable|string|in:new,existing',
            'cost' => 'required|numeric',
            'currency' => 'required|string|max:10',
            'duration_months' => 'required|integer',
            'status' => 'required|string|max:255',
            'contract_number' => 'nullable|string|unique:hec_contracts,contract_number,' . $id,
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'impact_if_not_requested' => 'required|string|in:Low,Medium,High',
            'likelihood_rating' => 'required|string|in:Low,Medium,High',
            'file_path' => 'nullable|mimes:pdf|max:10240',
            'signed_contract_path' => 'nullable|mimes:pdf|max:10240',
            'terms_conditions_path' => 'nullable|mimes:pdf|max:10240',
            'sla_document_path' => 'nullable|mimes:pdf|max:10240',
        ]);

        // Handle file uploads
        $fileFields = [
            'file_path' => 'contracts',
            'signed_contract_path' => 'contracts',
            'terms_conditions_path' => 'contracts',
            'sla_document_path' => 'contracts',
        ];

        $filePaths = [];
        foreach ($fileFields as $field => $folder) {
            if ($request->hasFile($field)) {
                // Delete old file if exists
                if ($contract->$field) {
                    Storage::disk('public')->delete($contract->$field);
                }
                $file = $request->file($field);
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePaths[$field] = $file->storeAs($folder, $fileName, 'public');
            }
        }

        $contract->update([
            'contract_number' => $request->contract_number,
            'title' => $request->title,
            'description' => $request->description,
            'contract_type' => $request->contract_type,
            'division_id' => $request->division_id,
            'contract_owner_id' => $request->contract_owner_id,
            'owner_email' => $request->owner_email,
            'contract_source' => $request->contract_source ?: $contract->contract_source,
            'cost' => $request->cost,
            'currency' => $request->currency ?? 'TZS',
            'duration_months' => $request->duration_months,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'impact_if_not_requested' => $request->impact_if_not_requested,
            'likelihood_rating' => $request->likelihood_rating,
        ] + $filePaths);

        return redirect()->route('hec-contracts.index')
            ->with('success', 'HEC Contract updated successfully.');
    }

    /**
     * Remove the specified HEC contract
     */
    public function destroy($id)
    {
        $contract = HecContract::findOrFail($id);

        // Delete associated files
        $fileFields = ['file_path', 'signed_contract_path', 'terms_conditions_path', 'sla_document_path'];
        foreach ($fileFields as $field) {
            if ($contract->$field) {
                Storage::disk('public')->delete($contract->$field);
            }
        }

        $contract->delete();

        return redirect()->route('hec-contracts.index')
            ->with('success', 'HEC Contract deleted successfully.');
    }
}
