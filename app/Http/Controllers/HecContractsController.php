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
     * True if current user can fully manage HEC contracts (see all + create/edit/delete).
     * Criteria: has role Admin-Secretary or super-admin, OR has permission manage_hec_contract.
     * CEO with view-only permission CANNOT manage.
     */
    private function canManage(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        // CEO with view-only permission cannot manage
        if ($user->hasPermissionTo('ceo_view_only')) return false;
        return $user->hasAnyRole(['Admin-Secretary', 'super-admin'])
            || $user->hasPermissionTo('manage_hec_contract');
    }

    /**
     * True if current user can view a specific contract.
     * Managers can view any; others only if they are the contract owner.
     */
    private function canView(HecContract $contract): bool
    {
        if ($this->canManage()) return true;
        return $contract->contract_owner_id === Auth::id();
    }

    /**
     * Display a listing of HEC contracts
     * No approval workflow here – just stored contracts.
     */
    public function index()
    {
        $today    = Carbon::today();
        $isManager = $this->canManage();
        $userId   = Auth::id();

        // Managers see all; HEC members see only their own
        $baseQuery = $isManager
            ? HecContract::with(['division', 'vendor', 'contractOwner'])
            : HecContract::with(['division', 'vendor', 'contractOwner'])->where('contract_owner_id', $userId);

        $contracts = (clone $baseQuery)
            ->with(['contractOwner', 'creator', 'division', 'renewals'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $terminalStatuses = ['renewed', 'terminated', 'archived'];

        $totalContracts = (clone $baseQuery)->count();

        // Expired: end_date < today and not in terminal status
        $expiredContracts = (clone $baseQuery)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->whereNotIn('status', $terminalStatuses)
            ->count();

        // Soon to expire: end_date within next 30 days, not expired, not terminal
        $soonToExpireContracts = (clone $baseQuery)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $today->copy()->addDays(30))
            ->whereNotIn('status', $terminalStatuses)
            ->count();

        // Active: end_date > 30 days away OR no end_date, not terminal
        $activeContracts = (clone $baseQuery)
            ->whereNotIn('status', array_merge($terminalStatuses, ['draft']))
            ->where(function($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhereDate('end_date', '>', $today->copy()->addDays(30));
            })->count();

        $totalValue  = (clone $baseQuery)->sum('cost');

        // Active value: same logic as active count
        $activeValue = (clone $baseQuery)
            ->whereNotIn('status', array_merge($terminalStatuses, ['draft']))
            ->where(function($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhereDate('end_date', '>', $today->copy()->addDays(30));
            })->sum('cost');

        return view('hec_contracts.index', compact(
            'contracts', 'totalContracts', 'activeContracts',
            'expiredContracts', 'soonToExpireContracts',
            'totalValue', 'activeValue', 'isManager'
        ));
    }

    /**
     * Show the form for creating a new HEC contract
     */
    public function create()
    {
        if (!$this->canManage()) abort(403, 'Unauthorized.');

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

        // Get all vendors
        $vendors = \App\Models\CcbrtVendor::orderBy('name')->get();

        return view('hec_contracts.create', compact('hecMembers', 'divisions', 'vendors'));
    }

    /**
     * Store a newly created HEC contract
     */
    public function store(Request $request)
    {
        if (!$this->canManage()) abort(403, 'Unauthorized.');

        $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string|max:255',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'vendor_id' => 'nullable|integer|exists:ccbrt_vendors,id',
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
            'vendor_id' => $request->vendor_id,
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
        $contract = HecContract::with(['contractOwner', 'creator', 'division', 'vendor', 'parentContract', 'renewals'])->findOrFail($id);
        if (!$this->canView($contract)) abort(403, 'Unauthorized.');
        $isManager = $this->canManage();
        return view('hec_contracts.show', compact('contract', 'isManager'));
    }

    /**
     * Show the form for editing the specified HEC contract
     */
    public function edit($id)
    {
        $contract = HecContract::findOrFail($id);
        if (!$this->canManage()) abort(403, 'Unauthorized.');

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

        // Get all vendors
        $vendors = \App\Models\CcbrtVendor::orderBy('name')->get();

        return view('hec_contracts.edit', compact('contract', 'hecMembers', 'divisions', 'vendors'));
    }

    /**
     * Update the specified HEC contract
     */
    public function update(Request $request, $id)
    {
        $contract = HecContract::findOrFail($id);
        if (!$this->canManage()) abort(403, 'Unauthorized.');

        $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string|max:255',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'vendor_id' => 'nullable|integer|exists:ccbrt_vendors,id',
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
            'vendor_id' => $request->vendor_id,
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
     * Renew an HEC contract: create a new contract pre-filled from the original,
     * mark the original as renewed. Original details are never modified.
     */
    public function renew(Request $request, $id)
    {
        $original = HecContract::findOrFail($id);
        if (!$this->canView($original)) abort(403, 'Unauthorized.');

        $request->validate([
            'new_start_date'          => 'required|date',
            'new_end_date'            => 'required|date|after:new_start_date',
            'new_duration_months'     => 'required|integer|min:1',
            'new_cost'                => 'required|numeric|min:0',
            'new_currency'            => 'required|string|max:10',
            'new_description'         => 'nullable|string',
            'new_contract_type'       => 'nullable|string|max:255',
            'new_impact'              => 'nullable|string|in:Low,Medium,High',
            'new_likelihood'          => 'nullable|string|in:Low,Medium,High',
            'new_file_path'           => 'nullable|mimes:pdf|max:10240',
            'new_signed_contract'     => 'nullable|mimes:pdf|max:10240',
            'new_terms_conditions'    => 'nullable|mimes:pdf|max:10240',
            'new_sla_document'        => 'nullable|mimes:pdf|max:10240',
        ]);

        // Generate a new contract number
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
            if ($exists) $sequence++;
        } while ($exists);

        // Handle uploaded documents
        $fileFields = [
            'new_file_path'        => 'file_path',
            'new_signed_contract'  => 'signed_contract_path',
            'new_terms_conditions' => 'terms_conditions_path',
            'new_sla_document'     => 'sla_document_path',
        ];
        $filePaths = [];
        foreach ($fileFields as $inputName => $dbField) {
            if ($request->hasFile($inputName)) {
                $file = $request->file($inputName);
                $filePaths[$dbField] = $file->storeAs('contracts', time() . '_' . $file->getClientOriginalName(), 'public');
            }
        }

        // Create the renewed contract — copy all original fields, apply new values
        $renewed = HecContract::create(array_merge([
            'parent_contract_id'      => $original->id,
            'contract_number'         => $contractNumber,
            'title'                   => $original->title,
            'description'             => $request->new_description ?? $original->description,
            'contract_type'           => $request->new_contract_type ?? $original->contract_type,
            'division_id'             => $original->division_id,
            'vendor_id'               => $original->vendor_id,
            'contract_owner_id'       => $original->contract_owner_id,
            'owner_email'             => $original->owner_email,
            'contract_source'         => 'existing',
            'cost'                    => $request->new_cost,
            'currency'                => $request->new_currency,
            'duration_months'         => $request->new_duration_months,
            'start_date'              => $request->new_start_date,
            'end_date'                => $request->new_end_date,
            'status'                  => 'active',
            'renewal_status'          => 'not_renewed',
            'impact_if_not_requested' => $request->new_impact ?? $original->impact_if_not_requested,
            'likelihood_rating'       => $request->new_likelihood ?? $original->likelihood_rating,
            'created_by'              => Auth::id(),
        ], $filePaths));

        // Mark original as renewed (do not change any other field)
        $original->update([
            'status'         => 'renewed',
            'renewal_status' => 'renewed',
            'renewed_at'     => Carbon::now(),
        ]);

        return redirect()
            ->route('hec-contracts.show', $renewed->id)
            ->with('success', 'Contract renewed successfully. New contract ' . $contractNumber . ' has been created.');
    }

    /**
     * Remove the specified HEC contract
     */
    public function archive($id)
    {
        $contract = HecContract::findOrFail($id);
        if (!$this->canManage()) abort(403, 'Unauthorized.');

        $contract->update(['status' => 'archived']);

        return redirect()->route('hec-contracts.show', $contract->id)
            ->with('success', 'Contract archived successfully.');
    }

    public function destroy($id)
    {
        $contract = HecContract::findOrFail($id);
        if (!$this->canManage()) abort(403, 'Unauthorized.');

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

    /**
     * Export HEC contracts to Excel with 2-sheet report (Summary + Details)
     */
    public function export(Request $request)
    {
        if (!$this->canManage()) abort(403, 'Unauthorized.');

        $today = Carbon::today();
        $query = HecContract::with(['division', 'vendor', 'contractOwner', 'renewals']);

        // Apply filters
        if ($request->filled('export_status') && $request->export_status !== 'all') {
            $status = $request->export_status;
            if ($status === 'soonToExpire') {
                $query->whereNotNull('end_date')
                    ->whereDate('end_date', '>=', $today)
                    ->whereDate('end_date', '<=', $today->copy()->addDays(30))
                    ->whereNotIn('status', ['renewed', 'terminated', 'archived']);
            } elseif ($status === 'expired') {
                $query->whereNotNull('end_date')
                    ->whereDate('end_date', '<', $today)
                    ->whereNotIn('status', ['renewed', 'terminated', 'archived']);
            } elseif ($status === 'active') {
                $query->whereNotIn('status', ['renewed', 'terminated', 'archived', 'draft'])
                    ->where(function ($q) use ($today) {
                        $q->whereNull('end_date')
                          ->orWhereDate('end_date', '>', $today->copy()->addDays(30));
                    });
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('entity')) {
            $query->whereHas('division', function ($q) use ($request) {
                $q->where('name', $request->entity);
            });
        }

        if ($request->filled('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        $contracts = $query->orderBy('end_date', 'asc')->get();

        // Build spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ===== Sheet 1: Summary =====
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Summary');

        $summary->setCellValue('A1', 'CCBRT eDocs — HEC Contracts Report');
        $summary->setCellValue('A2', 'Generated: ' . now()->format('d M Y, H:i'));
        $summary->mergeCells('A1:D1');
        $summary->mergeCells('A2:D2');
        $summary->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '007A33']],
        ]);
        $summary->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6B7280']],
        ]);

        // Applied filters
        $row = 4;
        $summary->setCellValue("A{$row}", 'Applied Filters');
        $summary->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $row++;
        $filterList = [];
        if ($request->filled('export_status') && $request->export_status !== 'all') $filterList[] = 'Status: ' . ucfirst($request->export_status);
        if ($request->filled('entity')) $filterList[] = 'Entity: ' . $request->entity;
        if ($request->filled('contract_type')) $filterList[] = 'Contract Type: ' . $request->contract_type;
        if (empty($filterList)) $filterList[] = 'None (All Contracts)';
        foreach ($filterList as $f) {
            $summary->setCellValue("A{$row}", $f);
            $row++;
        }

        // Status breakdown
        $row += 1;
        $summary->setCellValue("A{$row}", 'Status Breakdown');
        $summary->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $row++;
        $greenHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '007A33']],
        ];
        $summary->setCellValue("A{$row}", 'Status');
        $summary->setCellValue("B{$row}", 'Count');
        $summary->setCellValue("C{$row}", 'Total Value');
        $summary->getStyle("A{$row}:C{$row}")->applyFromArray($greenHeader);
        $row++;

        // Compute status groups from dates (like the view does)
        $statusGroups = ['active' => [], 'soonToExpire' => [], 'expired' => [], 'draft' => [], 'renewed' => [], 'archived' => [], 'terminated' => []];
        foreach ($contracts as $c) {
            $endDate = $c->end_date;
            if (in_array($c->status, ['renewed', 'terminated', 'archived', 'draft'])) {
                $realStatus = $c->status;
            } elseif ($endDate && $endDate->lt($today)) {
                $realStatus = 'expired';
            } elseif ($endDate && $endDate->diffInDays($today, false) >= -30 && $endDate->gte($today)) {
                $realStatus = 'soonToExpire';
            } else {
                $realStatus = 'active';
            }
            $statusGroups[$realStatus][] = $c;
        }
        $statusLabels = ['active' => 'Active', 'soonToExpire' => 'Soon to Expire', 'expired' => 'Expired', 'draft' => 'Draft', 'renewed' => 'Renewed', 'archived' => 'Archived', 'terminated' => 'Terminated'];
        $total = $contracts->count();
        foreach ($statusGroups as $key => $items) {
            if (count($items) === 0) continue;
            $val = collect($items)->sum('cost');
            $summary->setCellValue("A{$row}", $statusLabels[$key] ?? ucfirst($key));
            $summary->setCellValue("B{$row}", count($items));
            $summary->setCellValue("C{$row}", number_format($val, 2));
            $row++;
        }
        $summary->setCellValue("A{$row}", 'Total');
        $summary->setCellValue("B{$row}", $total);
        $summary->setCellValue("C{$row}", number_format($contracts->sum('cost'), 2));
        $summary->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);

        // Entity breakdown
        $row += 2;
        $summary->setCellValue("A{$row}", 'Entity Breakdown');
        $summary->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $row++;
        $summary->setCellValue("A{$row}", 'Entity');
        $summary->setCellValue("B{$row}", 'Count');
        $summary->setCellValue("C{$row}", 'Total Value');
        $summary->getStyle("A{$row}:C{$row}")->applyFromArray($greenHeader);
        $row++;
        $byEntity = $contracts->groupBy(fn($c) => optional($c->division)->name ?? 'Unknown');
        foreach ($byEntity->sortDesc() as $entity => $items) {
            $summary->setCellValue("A{$row}", $entity);
            $summary->setCellValue("B{$row}", $items->count());
            $summary->setCellValue("C{$row}", number_format($items->sum('cost'), 2));
            $row++;
        }

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $summary->getColumnDimension($col)->setAutoSize(true);
        }

        // ===== Sheet 2: Detailed Records =====
        $detail = $spreadsheet->createSheet();
        $detail->setTitle('Contract Details');

        $headers = ['#', 'Contract No.', 'Title', 'Type', 'Entity', 'Vendor', 'Owner', 'Start Date', 'End Date', 'Days Left', 'Cost', 'Currency', 'Status'];
        $detail->setCellValue('A1', 'CCBRT eDocs — HEC Contracts Detail Report');
        $detail->setCellValue('A2', 'Generated: ' . now()->format('d M Y, H:i') . ' | Records: ' . $total);
        $detail->mergeCells('A1:M1');
        $detail->mergeCells('A2:M2');
        $detail->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '007A33']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $detail->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $headerRow = 3;
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $detail->setCellValue("{$col}{$headerRow}", $h);
        }
        $detail->getStyle("A{$headerRow}:M{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '007A33']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 4;
        foreach ($contracts as $i => $c) {
            $endDate = $c->end_date;
            $daysLeft = $endDate ? (int) $today->diffInDays($endDate, false) : null;

            // Human-readable status from dates
            if (in_array($c->status, ['renewed', 'terminated', 'archived', 'draft'])) {
                $realStatus = ucfirst($c->status);
            } elseif ($endDate && $endDate->lt($today)) {
                $realStatus = 'Expired';
            } elseif ($endDate && $daysLeft <= 30 && $daysLeft >= 0) {
                $realStatus = 'Soon to Expire';
            } else {
                $realStatus = 'Active';
            }

            $daysLabel = '—';
            if ($daysLeft !== null) {
                if ($daysLeft < 0) {
                    $daysLabel = 'Expired ' . abs($daysLeft) . 'd ago';
                } elseif ($daysLeft === 0) {
                    $daysLabel = 'Expires today';
                } else {
                    $months = intdiv($daysLeft, 30);
                    $remainDays = $daysLeft % 30;
                    if ($daysLeft < 30) {
                        $daysLabel = $daysLeft . ' days';
                    } elseif ($remainDays == 0) {
                        $daysLabel = $months . ' month' . ($months > 1 ? 's' : '');
                    } else {
                        $daysLabel = $months . 'mo ' . $remainDays . 'd';
                    }
                }
            }

            $detail->setCellValue("A{$row}", $i + 1);
            $detail->setCellValue("B{$row}", $c->contract_number ?? '—');
            $detail->setCellValue("C{$row}", $c->title);
            $detail->setCellValue("D{$row}", $c->contract_type ?? '—');
            $detail->setCellValue("E{$row}", optional($c->division)->name ?? '—');
            $detail->setCellValue("F{$row}", optional($c->vendor)->name ?? '—');
            $detail->setCellValue("G{$row}", optional($c->contractOwner)->fname . ' ' . optional($c->contractOwner)->lname);
            $detail->setCellValue("H{$row}", $c->start_date ? $c->start_date->format('d M Y') : '—');
            $detail->setCellValue("I{$row}", $endDate ? $endDate->format('d M Y') : '—');
            $detail->setCellValue("J{$row}", $daysLabel);
            $detail->setCellValue("K{$row}", $c->cost ?? 0);
            $detail->getStyle("K{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $detail->setCellValue("L{$row}", $c->currency ?? 'TZS');
            $detail->setCellValue("M{$row}", $realStatus);

            // Alternating rows
            if ($row % 2 === 0) {
                $detail->getStyle("A{$row}:M{$row}")->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
            }
            $row++;
        }

        // Borders
        $lastRow = $row - 1;
        if ($lastRow >= $headerRow) {
            $detail->getStyle("A{$headerRow}:M{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            ]);
        }

        foreach (range('A', 'M') as $col) {
            $detail->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        $filename = 'HEC_Contracts_Report_' . now()->format('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
