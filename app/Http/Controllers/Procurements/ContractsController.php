<?php

namespace App\Http\Controllers\Procurements;

use App\Http\Controllers\Controller;
use App\Models\CcbrtContract;
use App\Models\User;
use App\Models\CcbrtVendor;
use App\Models\Division;
use App\Models\Departments;
use App\Models\Hec;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use App\Models\ContractRenewal;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\ApprovalRequestNotification;
use App\Mail\ContractAddedMail;
use App\Mail\ContractRenewalCreated;
use App\Mail\ReportMail;
use App\Mail\ContractsReport;
use App\Mail\ContractReminderMail;
use Illuminate\Support\Facades\Artisan;

class ContractsController extends Controller
{
    /**
     * Get allowed department IDs for the current user based on their role
     * Line Manager: only their department
     * HEC members (COO/CFO/CMS): departments under their HEC level
     * Others: all departments
     */
    private function allowedDepartmentIds(User $user): array
    {
        // HR and Procurement Officers: see all departments
        if ($user->hasAnyRole(['hr', 'procurement-officer', 'super-admin'])) {
            return Departments::pluck('id')->all();
        }

        // Line Manager: see only their department
        if ($user->hasRole('line-manager')) {
            $own = $user->deptId ? [(int) $user->deptId] : [];
            return array_values(array_unique(array_filter($own)));
        }

        // HEC roles (COO/CFO/CMS/CRHDO): see departments mapped to their HEC level
        if ($user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo'])) {
            $roleToHec = [];
            if ($user->hasRole('coo')) $roleToHec[] = 'COO';
            if ($user->hasRole('cfo')) $roleToHec[] = 'CFO';
            if ($user->hasRole('cms')) $roleToHec[] = 'CMS';
            if ($user->hasRole('crhdo')) $roleToHec[] = 'CRHDO';

            return Departments::query()
                ->join('hecs', 'departments.hec_id', '=', 'hecs.id')
                ->whereIn(DB::raw('UPPER(TRIM(hecs.hec_level_name))'), collect($roleToHec)->map(fn($r) => strtoupper(trim($r)))->all())
                ->pluck('departments.id')->all();
        }

        // Default: all departments (for other roles)
        return Departments::pluck('id')->all();
    }

    /**
     * Display a listing of contracts
     */
    public function index()
    {
        $user = Auth::user();

        // Get allowed department IDs based on user role
        $allowedDepartmentIds = $this->allowedDepartmentIds($user);

        // Build query with department filtering
        // Eager load relationships (check if parentContract and renewals exist to avoid errors on older model versions)
        $relationships = ['division', 'department', 'vendor', 'creator'];

        // Only add parentContract and renewals if they exist in the model
        if (method_exists(CcbrtContract::class, 'parentContract')) {
            $relationships[] = 'parentContract';
        }
        if (method_exists(CcbrtContract::class, 'renewals')) {
            $relationships[] = 'renewals';
        }

        $contractsQuery = CcbrtContract::with($relationships);

        // COO can view all contracts (no department filter)
        if ($user->hasRole('coo')) {
            // No filter - show all contracts
        }
        // Apply department filter if user is Line Manager or other HEC members (CFO, CMS, CRHDO)
        elseif ($user->hasRole('line-manager') || $user->hasAnyRole(['cfo', 'cms', 'crhdo'])) {
            if (!empty($allowedDepartmentIds)) {
                $contractsQuery->whereIn('department_id', $allowedDepartmentIds);
            } else {
                // If no allowed departments, return empty collection
                $contractsQuery->whereRaw('1 = 0');
            }
        }

        $contracts = $contractsQuery->get();

        $today = Carbon::now();
        $soonToExpire = $today->copy()->addDays(30);

        // Filter contracts by status and end_date
        // First, identify expired contracts: either status is 'expired' OR end_date is in the past
        $expiredContractIds = $contracts->filter(function ($contract) use ($today) {
            // Check if status is explicitly 'expired'
            if (strtolower($contract->status ?? '') === 'expired') {
                return true;
            }
            // Or check if end_date is in the past
            if ($contract->end_date) {
                return Carbon::parse($contract->end_date)->lt($today);
            }
            return false;
        })->pluck('id')->toArray();

        $expiredContracts = $contracts->filter(function ($contract) use ($expiredContractIds) {
            return in_array($contract->id, $expiredContractIds);
        });

        // Expiring soon: contracts with end_date within 30 days (but not expired status)
        $soonToExpireContracts = $contracts->filter(function ($contract) use ($today, $soonToExpire, $expiredContractIds) {
            // Skip if already in expired list
            if (in_array($contract->id, $expiredContractIds)) {
                return false;
            }
            if (!$contract->end_date) return false;
            $endDate = Carbon::parse($contract->end_date);
            return $endDate->gte($today) && $endDate->lte($soonToExpire);
        });

        // Active contracts: status is 'active' OR (not expired, not expiring soon, and end_date is in the future or no end_date)
        // Also include renewed contracts (they are active contracts that were renewed)
        $soonToExpireContractIds = $soonToExpireContracts->pluck('id')->toArray();
        $activeContracts = $contracts->filter(function ($contract) use ($today, $expiredContractIds, $soonToExpireContractIds) {
            $contractStatus = strtolower($contract->status ?? '');

            // If status is explicitly 'active', always include it (highest priority)
            // This includes renewed contracts which have status 'active' and renewal_status 'renewed'
            if ($contractStatus === 'active') {
                return true;
            }

            // Exclude contracts with status 'expired'
            if ($contractStatus === 'expired') {
                return false;
            }

            // Exclude contracts in expired list (based on end_date)
            if (in_array($contract->id, $expiredContractIds)) {
                return false;
            }

            // Exclude expiring soon contracts
            if (in_array($contract->id, $soonToExpireContractIds)) {
                return false;
            }

            // Exclude contracts still in workflow (pending approval) - they're not active yet
            if (
                in_array($contract->approval_stage ?? '', ['line_manager', 'hec', 'procurement'])
                && in_array($contract->status ?? '', ['in_progress', 'pending'])
            ) {
                return false;
            }

            // Contracts without end_date are considered active
            if (!$contract->end_date) return true;

            // Contracts with end_date in the future are active
            return Carbon::parse($contract->end_date)->gt($today);
        });

        // Counts
        $activeContractsCount = $activeContracts->count();
        $expiredContractsCount = $expiredContracts->count();
        $soonToExpireContractsCount = $soonToExpireContracts->count();

        // Financials
        $activeContractsValue = $activeContracts->sum('cost');
        $expiredContractsValue = $expiredContracts->sum('cost');
        $soonToExpireContractsValue = $soonToExpireContracts->sum('cost');

        // Set total values - Total Value should be from active contracts only
        $totalContracts = $contracts->count();
        $totalValue = $activeContractsValue; // Only active contracts value

        // Filter contracts pending HEC review (for HEC members)
        $pendingHecReviewContracts = collect();
        if ($user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo'])) {
            $pendingHecReviewContracts = $contracts->filter(function ($contract) use ($user) {
                // Contract must be pending HEC review and assigned to this HEC member
                return $contract->approval_stage === 'hec'
                    && $contract->current_approver_id == $user->id
                    && in_array($contract->status, ['in_progress', 'pending']);
            });
        }

        $pendingHecReviewCount = $pendingHecReviewContracts->count();
        $pendingHecReviewValue = $pendingHecReviewContracts->sum('cost');

        // Filter contracts pending Line Manager review (for Line Managers)
        $pendingLineManagerReviewContracts = collect();
        if ($user->hasRole('line-manager')) {
            $pendingLineManagerReviewContracts = $contracts->filter(function ($contract) use ($user) {
                // Contract must be pending Line Manager review and assigned to this Line Manager
                return $contract->approval_stage === 'line_manager'
                    && $contract->current_approver_id == $user->id
                    && in_array($contract->status, ['in_progress', 'pending', 'draft']);
            });
        }

        $pendingLineManagerReviewCount = $pendingLineManagerReviewContracts->count();
        $pendingLineManagerReviewValue = $pendingLineManagerReviewContracts->sum('cost');

        // Filter contracts pending Procurement Officer processing (for Procurement Officers)
        $pendingProcurementReviewContracts = collect();
        if ($user->hasRole('procurement-officer')) {
            $pendingProcurementReviewContracts = $contracts->filter(function ($contract) use ($user) {
                // Contract must be pending Procurement processing and assigned to this Procurement Officer
                return $contract->approval_stage === 'procurement'
                    && $contract->current_approver_id == $user->id
                    && in_array($contract->status, ['in_progress', 'pending']);
            });
        }

        $pendingProcurementReviewCount = $pendingProcurementReviewContracts->count();
        $pendingProcurementReviewValue = $pendingProcurementReviewContracts->sum('cost');

        // Extract available years from contract dates (start_date, end_date, created_at)
        $availableYears = collect();
        foreach ($contracts as $contract) {
            if ($contract->start_date) {
                $availableYears->push(Carbon::parse($contract->start_date)->year);
            }
            if ($contract->end_date) {
                $availableYears->push(Carbon::parse($contract->end_date)->year);
            }
            if ($contract->created_at) {
                $availableYears->push(Carbon::parse($contract->created_at)->year);
            }
        }
        $availableYears = $availableYears->unique()->sort()->values();

        // Get the view type from query parameter (default to 'active' if not set)
        $viewType = request()->query('view', 'active');

        // Validate view type
        $validViewTypes = ['active', 'expiring', 'expired', 'pending-line-manager', 'pending-hec', 'pending-procurement'];
        if (!in_array($viewType, $validViewTypes)) {
            $viewType = 'active';
        }

        // Return view with all variables
        return view('procurements.contracts.index', [
            'contracts' => $contracts,
            'activeContracts' => $activeContracts,
            'expiredContracts' => $expiredContracts,
            'soonToExpireContracts' => $soonToExpireContracts,
            'totalContracts' => $totalContracts,
            'totalValue' => $totalValue,
            'activeContractsCount' => $activeContractsCount,
            'activeContractsValue' => $activeContractsValue,
            'expiredContractsCount' => $expiredContractsCount,
            'expiredContractsValue' => $expiredContractsValue,
            'soonToExpireContractsCount' => $soonToExpireContractsCount,
            'soonToExpireContractsValue' => $soonToExpireContractsValue,
            'pendingHecReviewContracts' => $pendingHecReviewContracts,
            'pendingHecReviewCount' => $pendingHecReviewCount,
            'pendingHecReviewValue' => $pendingHecReviewValue,
            'pendingLineManagerReviewContracts' => $pendingLineManagerReviewContracts,
            'pendingLineManagerReviewCount' => $pendingLineManagerReviewCount,
            'pendingLineManagerReviewValue' => $pendingLineManagerReviewValue,
            'pendingProcurementReviewContracts' => $pendingProcurementReviewContracts,
            'pendingProcurementReviewCount' => $pendingProcurementReviewCount,
            'pendingProcurementReviewValue' => $pendingProcurementReviewValue,
            'noContracts' => $contracts->isEmpty(),
            'availableYears' => $availableYears,
            'viewType' => $viewType
        ]);
    }

    /**
     * Show the form for creating a new contract
     */
    public function create()
    {
        $vendors = CcbrtVendor::all();
        $divisions = Division::all();
        $departments = Departments::all();
        $users = User::where('status', 'active')->get();
        return view('procurements.contracts.create', compact('vendors', 'divisions', 'departments', 'users'));
    }

    /**
     * Get departments by entity (AJAX)
     */
    public function getDepartmentsByEntity($divisionId)
    {
        $division = Division::findOrFail($divisionId);
        $departments = $division->departments()->get();
        return response()->json($departments);
    }

    /**
     * Get line manager for department (AJAX)
     */
    public function getLineManager($departmentId)
    {
        try {
            // Try using whereHas first (more reliable)
            $lineManager = User::whereHas('roles', function ($query) {
                $query->where('name', 'line-manager');
            })
                ->where('deptId', $departmentId)
                ->where('status', 'active')
                ->first();

            // Fallback: Try using Spatie's role() method if whereHas doesn't work
            if (!$lineManager) {
                $lineManager = User::role('line-manager')
                    ->where('deptId', $departmentId)
                    ->where('status', 'active')
                    ->first();
            }

            // Another fallback: Check if status field exists, if not, remove that condition
            if (!$lineManager) {
                $lineManager = User::whereHas('roles', function ($query) {
                    $query->where('name', 'line-manager');
                })
                    ->where('deptId', $departmentId)
                    ->first();
            }

            if ($lineManager) {
                $fullName = trim(
                    ($lineManager->fname ?? '') . ' ' .
                        ($lineManager->mname ?? '') . ' ' .
                        ($lineManager->lname ?? '')
                );

                return response()->json([
                    'success' => true,
                    'line_manager' => [
                        'id' => $lineManager->id,
                        'name' => $fullName ?: 'Line Manager',
                        'email' => $lineManager->email ?? 'No email'
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'line_manager' => null,
                'message' => 'No line manager found for this department (ID: ' . $departmentId . ')'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading line manager: ' . $e->getMessage(), [
                'department_id' => $departmentId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'line_manager' => null,
                'message' => 'Error loading line manager: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for adding a new contract
     */
    public function addNewContract()
    {
        return view('procurements.contracts.store');
    }

    /**
     * Store a newly created contract
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string|max:255',
            'vendor_id' => 'nullable|integer',
            'department_id' => 'required|integer|exists:departments,id',
            'division_id' => 'required|integer|exists:divisions,id',
            'cost' => 'required|numeric',
            'currency' => 'required|string|max:10',
            'duration_months' => 'required|integer',
            'status' => 'required|string|max:255',
            'contract_number' => 'nullable|string|unique:ccbrt_contracts,contract_number',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'contract_manager_id' => 'nullable|integer|exists:users,id',
            'impact_if_not_requested' => 'required|string|in:Low,Medium,High',
            'likelihood_rating' => 'required|string|in:Low,Medium,High',
            'file_path' => 'nullable|mimes:pdf|max:10240',
            'signed_contract_path' => 'nullable|mimes:pdf|max:10240',
            'terms_conditions_path' => 'nullable|mimes:pdf|max:10240',
            'sla_document_path' => 'nullable|mimes:pdf|max:10240',
        ]);

        // Generate contract number if not provided
        $contractNumber = $request->contract_number;
        if (!$contractNumber) {
            $year = date('Y');
            $lastContract = CcbrtContract::whereYear('created_at', $year)
                ->whereNotNull('contract_number')
                ->where('contract_number', 'like', 'CNT-' . $year . '-%')
                ->latest()
                ->first();

            $sequence = 1;
            if ($lastContract && $lastContract->contract_number) {
                // Extract sequence from last contract number (format: CNT-YYYY-####)
                $parts = explode('-', $lastContract->contract_number);
                if (count($parts) >= 3 && is_numeric($parts[2])) {
                    $sequence = (int) $parts[2] + 1;
                }
            }

            // Keep trying until we find a unique number
            do {
                $contractNumber = 'CNT-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                $exists = CcbrtContract::where('contract_number', $contractNumber)->exists();
                if ($exists) {
                    $sequence++;
                }
            } while ($exists);
        } else {
            // Check if provided contract number already exists
            if (CcbrtContract::where('contract_number', $contractNumber)->exists()) {
                // Generate a new unique one
                $year = date('Y');
                $lastContract = CcbrtContract::whereYear('created_at', $year)
                    ->whereNotNull('contract_number')
                    ->where('contract_number', 'like', 'CNT-' . $year . '-%')
                    ->latest()
                    ->first();

                $sequence = 1;
                if ($lastContract && $lastContract->contract_number) {
                    $parts = explode('-', $lastContract->contract_number);
                    if (count($parts) >= 3 && is_numeric($parts[2])) {
                        $sequence = (int) $parts[2] + 1;
                    }
                }

                do {
                    $contractNumber = 'CNT-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                    $exists = CcbrtContract::where('contract_number', $contractNumber)->exists();
                    if ($exists) {
                        $sequence++;
                    }
                } while ($exists);
            }
        }

        // Get line manager if contract_manager_id is not provided
        $contractManagerId = $request->contract_manager_id;
        if (!$contractManagerId && $request->department_id) {
            $lineManager = User::whereHas('roles', function ($query) {
                $query->where('name', 'line-manager');
            })
                ->where('deptId', $request->department_id)
                ->where('status', 'active')
                ->first();

            if ($lineManager) {
                $contractManagerId = $lineManager->id;
            }
        }

        // Determine status and workflow based on contract source
        $isNewContract = ($request->contract_source === 'new');

        if ($isNewContract) {
            // NEW contracts: Start as "in_progress" and go through approval workflow
            $initialStatus = 'in_progress';
            $approvalStage = 'line_manager';
            $lifecycleStage = 'drafting';
        } else {
            // EXISTING contracts: Use the status provided, no workflow
            $initialStatus = $request->status; // Use the status from form (active, expired, etc.)
            $approvalStage = null;
            $lifecycleStage = $request->status === 'active' ? 'execution' : ($request->status === 'expired' ? 'close' : 'drafting');
        }

        // Normalize dates
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;
        $creationDate = $request->creation_date
            ? Carbon::parse($request->creation_date)->format('Y-m-d')
            : ($startDate ?? now()->format('Y-m-d'));

        $contract = CcbrtContract::create([
            'contract_number' => $contractNumber,
            'title' => $request->title,
            'contract_type' => $request->contract_type,
            'vendor_id' => $request->vendor_id,
            'division_id' => $request->division_id,
            'department_id' => $request->department_id,
            'cost' => $request->cost,
            'currency' => $request->currency ?? 'TZS',
            'duration_months' => $request->duration_months,
            'start_date' => $startDate,
            'creation_date' => $creationDate,
            'end_date' => $endDate,
            'status' => $initialStatus,
            'lifecycle_stage' => $lifecycleStage,
            'contract_manager_id' => $contractManagerId,
            'impact_if_not_requested' => $request->impact_if_not_requested,
            'likelihood_rating' => $request->likelihood_rating,
            // Auto-enable monitoring for all contracts
            'alert_30_days' => true,
            'alert_60_days' => true,
            'alert_90_days' => true,
            'approval_stage' => $approvalStage,
            'created_by' => Auth::id(),
        ]);

        // Handle file uploads
        $fileFields = [
            'file_path' => 'file_path',
            'signed_contract_path' => 'signed_contract_path',
            'terms_conditions_path' => 'terms_conditions_path',
            'sla_document_path' => 'sla_document_path',
        ];

        foreach ($fileFields as $requestField => $dbField) {
            if ($request->hasFile($requestField)) {
                $file = $request->file($requestField);
                $filename = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('contracts', $filename, 'public');
                $contract->$dbField = '/storage/' . $filePath;
            }
        }
        $contract->save();

        // Only start workflow for NEW contracts
        if ($isNewContract) {
            // Start approval workflow: Line Manager -> HEC -> Active
            $workflow = Workflow::create([
                'user_id' => $user->id,
                'work_flow_status' => 'pending',
                'work_flow_completed' => 0,
                'ccbrt_contract_id' => $contract->id,
            ]);

            // Get Line Manager for the department
            $department = Departments::find($request->department_id);
            $lineManager = null;
            if ($department) {
                $lineManager = User::role('line-manager')->where('deptId', $department->id)->first();
            }

            if ($lineManager) {
                $contract->current_approver_id = $lineManager->id;
                $contract->save();

                // Create workflow history for Line Manager
                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'remark' => 'New contract submitted by Procurement Officer ' . $user->name . ' - Awaiting Line Manager review',
                    'forwarded_by' => $user->id,
                    'attended_by' => $lineManager->id,
                    'status' => 0, // pending
                    'step_name' => 'Line Manager',
                    'created_at' => Carbon::now(),
                ]);

                // Send notification to Line Manager (queued)
                try {
                    Mail::to($lineManager->email)->queue(new ContractAddedMail($contract, $lineManager, 'A new contract has been submitted and requires your review and approval.'));
                } catch (\Exception $e) {
                    Log::error('Failed to queue contract notification to line manager: ' . $e->getMessage());
                }
            }

            return redirect()->route('procurements.contracts.index')
                ->with('success', 'New contract created successfully! Status set to "In Progress". Line Manager has been notified for review.');
        } else {
            // EXISTING contract: Just save with provided status, no workflow
            return redirect()->route('procurements.contracts.index')
                ->with('success', 'Existing contract added successfully! Status: ' . ucfirst($initialStatus) . '. Monitoring is enabled.');
        }
    }

    /**
     * Display the specified contract
     */
    public function show($id)
    {
        // Build relationships array defensively (check if methods exist to avoid errors on older model versions)
        $relationships = ['division', 'department', 'vendor', 'creator'];

        // Only add relationships if they exist in the model
        if (method_exists(CcbrtContract::class, 'workflows')) {
            $relationships[] = 'workflows.histories';
        }
        if (method_exists(CcbrtContract::class, 'workflow')) {
            $relationships[] = 'workflow.histories';
        }
        if (method_exists(CcbrtContract::class, 'contractManager')) {
            $relationships[] = 'contractManager';
        }
        if (method_exists(CcbrtContract::class, 'currentApprover')) {
            $relationships[] = 'currentApprover';
        }
        if (method_exists(CcbrtContract::class, 'parentContract')) {
            $relationships[] = 'parentContract';
        }
        if (method_exists(CcbrtContract::class, 'renewals')) {
            $relationships[] = 'renewals';
        }

        $contract = CcbrtContract::with($relationships)->findOrFail($id);

        // Get the first contract (original) in the chain (only if parentContract relationship exists)
        $firstContract = $contract;
        if (method_exists(CcbrtContract::class, 'parentContract') && $contract->relationLoaded('parentContract')) {
            while ($firstContract->parentContract) {
                $firstContract = $firstContract->parentContract;
            }
        }

        // Get all contracts in the chain (first + all renewals) ordered by term number
        $contractChain = collect([$firstContract]);
        if (method_exists(CcbrtContract::class, 'renewals') && $firstContract->relationLoaded('renewals') && $firstContract->renewals) {
            $contractChain = $contractChain->merge($firstContract->renewals);
        }
        $contractChain = $contractChain->sortBy('renewal_term_number')->values();

        // Load data for Procurement approval form (if needed)
        $vendors = CcbrtVendor::all();
        $divisions = Division::all();
        $departments = Departments::all();

        return view('procurements.contracts.show', compact('contract', 'vendors', 'divisions', 'departments', 'firstContract', 'contractChain'));
    }

    /**
     * Get contract details for modal (AJAX)
     */
    public function getDetails($id)
    {
        $contract = CcbrtContract::with(['division', 'department', 'vendor', 'creator'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'contract' => $contract
        ]);
    }

    /**
     * Download or view contract document
     */
    public function document($id)
    {
        $contract = CcbrtContract::findOrFail($id);
        $type = request()->query('type', 'file_path');

        $filePath = null;
        if ($type === 'file_path' && $contract->file_path) {
            $filePath = $contract->file_path;
        } elseif ($type === 'uploaded_contract_path' && $contract->uploaded_contract_path) {
            $filePath = $contract->uploaded_contract_path;
        } elseif ($type === 'signed_contract_path' && $contract->signed_contract_path) {
            $filePath = $contract->signed_contract_path;
        } elseif ($type === 'terms_conditions_path' && $contract->terms_conditions_path) {
            $filePath = $contract->terms_conditions_path;
        } elseif ($type === 'sla_document_path' && $contract->sla_document_path) {
            $filePath = $contract->sla_document_path;
        } elseif ($type === 'terms_of_reference_path' && $contract->terms_of_reference_path) {
            $filePath = $contract->terms_of_reference_path;
        }

        if (!$filePath) {
            abort(404, 'Document not found');
        }

        // Remove leading slash if present
        $filePath = ltrim($filePath, '/');

        // Handle different path formats
        // If path starts with 'storage/', remove it
        if (strpos($filePath, 'storage/') === 0) {
            $filePath = str_replace('storage/', '', $filePath);
        }

        // Determine content type based on file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $contentType = 'application/pdf';
        if ($extension === 'doc') {
            $contentType = 'application/msword';
        } elseif ($extension === 'docx') {
            $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }

        // Check if file exists in public storage
        if (Storage::disk('public')->exists($filePath)) {
            $fullPath = Storage::disk('public')->path($filePath);
            if (file_exists($fullPath)) {
                return response()->file($fullPath, [
                    'Content-Type' => $contentType,
                ]);
            }
        }

        // Try alternative path formats
        $alternatives = [
            $filePath,
            'contracts/' . basename($filePath),
            'contracts/tor/' . basename($filePath),
            str_replace('contracts/', '', $filePath),
        ];

        foreach ($alternatives as $altPath) {
            if (Storage::disk('public')->exists($altPath)) {
                $fullPath = Storage::disk('public')->path($altPath);
                if (file_exists($fullPath)) {
                    return response()->file($fullPath, [
                        'Content-Type' => $contentType,
                    ]);
                }
            }
        }

        // Final fallback: try direct file path
        $fullPath = storage_path('app/public/' . $filePath);
        if (file_exists($fullPath)) {
            return response()->file($fullPath, [
                'Content-Type' => $contentType,
            ]);
        }

        abort(404, 'Document file not found. Please check if the file exists in storage.');
    }

    /**
     * Show the form for editing the specified contract
     * Only Procurement Officers, HR, and Super Admins can edit contracts
     */
    public function edit($id)
    {
        $user = Auth::user();

        // Only Procurement Officers, HR, and Super Admins can edit contracts
        // Line Managers and HEC members are NOT allowed to edit
        if (!$user->hasAnyRole(['procurement-officer', 'hr', 'super-admin'])) {
            return redirect()->route('procurements.contracts.index')
                ->withErrors('You do not have permission to edit contracts. Only Procurement Officers, HR, and Super Admins can edit contracts.');
        }

        $contract = CcbrtContract::findOrFail($id);
        $vendors = CcbrtVendor::all();
        $divisions = Division::all();
        $departments = Departments::all();
        return view('procurements.contracts.edit', compact('contract', 'vendors', 'divisions', 'departments'));
    }

    /**
     * Update the specified contract
     * Only Procurement Officers, HR, and Super Admins can update contracts
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Only Procurement Officers, HR, and Super Admins can update contracts
        // Line Managers and HEC members are NOT allowed to update
        if (!$user->hasAnyRole(['procurement-officer', 'hr', 'super-admin'])) {
            return redirect()->route('procurements.contracts.index')
                ->withErrors('You do not have permission to update contracts. Only Procurement Officers, HR, and Super Admins can update contracts.');
        }

        $contract = CcbrtContract::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string|max:255',
            'vendor_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'division_id' => 'nullable|integer',
            'cost' => 'required|numeric',
            'duration_months' => 'required|integer',
            'status' => 'required|string|max:255',
            'currency' => 'nullable|string|max:10',
            'renewal_status' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'contract_manager_id' => 'nullable|integer|exists:users,id',
            'line_manager_id' => 'nullable|integer|exists:users,id',
            'file_path' => 'nullable|mimes:pdf|max:10240',
        ]);

        // Get line manager if contract_manager_id is not provided
        $contractManagerId = $request->contract_manager_id;
        if (!$contractManagerId && $request->department_id) {
            $lineManager = User::whereHas('roles', function ($query) {
                $query->where('name', 'line-manager');
            })
                ->where('deptId', $request->department_id)
                ->where('status', 'active')
                ->first();

            if ($lineManager) {
                $contractManagerId = $lineManager->id;
            }
        }

        // Use filled() to properly check for non-empty values
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->format('Y-m-d') : $contract->start_date;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->format('Y-m-d') : $contract->end_date;

        // Build update data array
        $updateData = [
            'title' => $request->title,
            'contract_type' => $request->contract_type,
            'vendor_id' => $request->vendor_id,
            'division_id' => $request->division_id,
            'department_id' => $request->department_id,
            'cost' => $request->cost,
            'duration_months' => $request->duration_months,
            'currency' => $request->currency ?? 'TZS',
            'renewal_status' => $request->renewal_status,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $request->status,
            'contract_manager_id' => $contractManagerId,
        ];

        // Also set creation_date if not already set
        if (!$contract->creation_date && $startDate) {
            $updateData['creation_date'] = $startDate;
        }

        $contract->update($updateData);

        // Handle file upload
        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('contracts', $filename, 'public');
            $contract->file_path = '/storage/' . $filePath;
            $contract->save();
        }

        return redirect()->route('procurements.contracts.index')
            ->with('success', 'Contract updated successfully!');
    }

    /**
     * Remove the specified contract
     */
    public function destroy($id)
    {
        $contract = CcbrtContract::findOrFail($id);
        $contract->delete();

        return redirect()->route('procurements.contracts.index')
            ->with('success', 'Contract deleted successfully!');
    }

    /**
     * Show notification management page
     */
    public function notificationManagement()
    {
        $user = Auth::user();

        // Super Admin and Procurement Officer can see all contracts; others only see contracts they are responsible for
        $contractsQuery = CcbrtContract::with(['vendor', 'department', 'division'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc');

        if (!$user->hasAnyRole(['super-admin', 'procurement-officer'])) {
            $contractsQuery->where(function ($q) use ($user) {
                $q->where('contract_manager_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        $contracts = $contractsQuery->get();

        // Get queue status
        $queueConnection = config('queue.default');
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        // Get mail configuration
        $mailConfig = [
            'driver' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'encryption' => config('mail.mailers.smtp.encryption'),
        ];

        return view('procurements.contracts.notification-management', compact('contracts', 'queueConnection', 'pendingJobs', 'failedJobs', 'mailConfig'));
    }

    /**
     * Test sending a reminder email (Admin area)
     */
    public function testReminderEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
            'contract_id' => 'nullable|exists:ccbrt_contracts,id'
        ]);

        try {
            $contract = null;
            $emailBody = "This is a test contract reminder email from your eDoc app.\n\n";

            if ($request->contract_id) {
                $contract = CcbrtContract::with(['department', 'vendor'])->find($request->contract_id);
                if ($contract) {
                    $emailBody .= "Contract Details:\n";
                    $emailBody .= "Title: {$contract->title}\n";
                    if ($contract->vendor) {
                        $emailBody .= "Vendor: {$contract->vendor->name}\n";
                    }
                    if ($contract->department) {
                        $emailBody .= "Department: {$contract->department->dept_name}\n";
                    }
                    if ($contract->end_date) {
                        $emailBody .= "End Date: " . Carbon::parse($contract->end_date)->format('Y-m-d') . "\n";
                    }
                }
            }

            $emailBody .= "\nThis is a test email to verify that contract reminder emails are working correctly.";

            // Use EXACT same Mail::raw() pattern as SettingsController::sendTestEmail()
            Mail::raw($emailBody, function ($message) use ($request, $contract) {
                $message->to($request->test_email)
                    ->subject('Test Contract Reminder Email' . ($contract ? ': ' . $contract->title : ''));
            });

            return back()->with('success', 'Test reminder email sent to ' . $request->test_email);
        } catch (\Exception $e) {
            Log::error('Failed to send test reminder email', [
                'test_email' => $request->test_email,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    /**
     * Update contract notification settings
     */
    public function updateNotificationSettings(Request $request, $id)
    {
        $contract = CcbrtContract::findOrFail($id);

        $request->validate([
            'alert_30_days' => 'boolean',
            'alert_60_days' => 'boolean',
            'alert_90_days' => 'boolean',
        ]);

        $contract->update([
            'alert_30_days' => $request->has('alert_30_days'),
            'alert_60_days' => $request->has('alert_60_days'),
            'alert_90_days' => $request->has('alert_90_days'),
        ]);

        return redirect()->route('procurements.contracts.notification-management')
            ->with('success', 'Notification settings updated successfully!');
    }

    /**
     * Show the upload contract form
     */
    public function uploadContract()
    {
        $divisions = Division::all();
        $departments = Departments::all();
        $vendors = CcbrtVendor::all();
        return view('procurements.contracts.uploadContract', compact('divisions', 'departments', 'vendors'));
    }

    /**
     * Handle contract upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string|max:255',
            'vendor_id' => 'required|exists:ccbrt_vendors,id',
            'division_id' => 'required|exists:divisions,id',
            'currency' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|string|in:draft,active,expired,terminated,in_progress,renewed',
            'cost' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'notice_period_months' => 'nullable|integer',
            'creation_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:creation_date',
            'impact_if_not_requested' => 'required|string|in:Low,Medium,High',
            'likelihood_rating' => 'required|string|in:Low,Medium,High',
            'renewal_status' => 'required|string|in:renewed,not_renewed,pending',
            'file_path' => 'required|file|max:10240',
        ]);

        try {
            // Handle file upload
            $filePath = null;
            if ($request->hasFile('file_path')) {
                $file = $request->file('file_path');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('contracts', $fileName, 'public');
            }

            // Create contract with auto-enabled monitoring
            $contract = CcbrtContract::create([
                'title' => $request->title,
                'contract_type' => $request->contract_type,
                'vendor_id' => $request->vendor_id,
                'division_id' => $request->division_id,
                'currency' => $request->currency,
                'department_id' => $request->department_id,
                'status' => 'in_progress', // Always start as in_progress for approval workflow
                'cost' => $request->cost,
                'duration_months' => $request->duration_months,
                'notice_period_months' => $request->notice_period_months,
                'creation_date' => $request->creation_date,
                'end_date' => $request->end_date,
                'impact_if_not_requested' => $request->impact_if_not_requested,
                'likelihood_rating' => $request->likelihood_rating,
                'renewal_status' => $request->renewal_status,
                'file_path' => $filePath,
                // Auto-enable monitoring for all uploaded contracts
                'alert_30_days' => true,
                'alert_60_days' => true,
                'alert_90_days' => true,
                'approval_stage' => 'line_manager',
                'created_by' => auth()->id(),
            ]);

            // Eager load relationships: vendor, department, creator
            $contract->load(['vendor', 'department', 'creator']);

            // Start approval workflow
            $workflow = Workflow::create([
                'user_id' => auth()->id(),
                'work_flow_status' => 'pending',
                'work_flow_completed' => 0,
                'ccbrt_contract_id' => $contract->id,
            ]);

            // Get Line Manager for notification
            $lineManagers = User::role('line-manager')->where('deptId', $contract->department_id)->get();
            $lineManager = $lineManagers->first();

            if ($lineManager) {
                $contract->current_approver_id = $lineManager->id;
                $contract->save();

                // Create workflow history for Line Manager
                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'remark' => 'Contract (Uploaded) submitted by ' . auth()->user()->name . ' - Awaiting Line Manager approval',
                    'forwarded_by' => auth()->id(),
                    'attended_by' => $lineManager->id,
                    'status' => 0, // pending
                    'step_name' => 'Line Manager',
                    'created_at' => Carbon::now(),
                ]);

                // Send notification to Line Manager (queued)
                try {
                    Mail::to($lineManager->email)->queue(new ContractAddedMail($contract, $lineManager, 'A contract has been uploaded and requires your review and approval.'));
                    Log::info('Contract notification queued for line manager: ' . $lineManager->email);
                } catch (\Exception $e) {
                    Log::error('Failed to queue contract notification to line manager: ' . $e->getMessage());
                }
            }

            return redirect()->route('procurements.contracts.index')
                ->with('success', 'Contract uploaded successfully! Monitoring is enabled and Line Manager has been notified for approval.');
        } catch (\Exception $e) {
            Log::error('Contract upload failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to upload contract. Check logs for details.');
        }
    }

    /**
     * Show the contract renewal page
     */
    public function makeContract()
    {
        $vendors = CcbrtVendor::all();
        $divisions = Division::all();
        $departments = Departments::all();
        $contracts = CcbrtContract::all();
        return view('procurements.contracts.makeContract', compact('vendors', 'departments', 'divisions', 'contracts'));
    }

    /**
     * Initiate contract renewal
     */
    public function initiateContractRenewal(Request $request)
    {
        try {
            $validated = $request->validate([
                'contract_id' => 'required|exists:ccbrt_contracts,id',
                'vendor_id' => 'nullable|exists:ccbrt_vendors,id',
                'vendor_option' => 'required|in:existing,new',
                'department_id' => 'nullable|exists:departments,id',
                'division_id' => 'required|exists:divisions,id',
                'vendor_review' => 'nullable|integer|min:1|max:10',
                'contract_type' => 'required|string',
                'category' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'duration_months' => 'required|integer|min:1',
                'status' => 'required|string|in:New,Renewal,Extension',
                'likelihood_rating' => 'required|string',
                'impact_if_not_requested' => 'required|string',
                'overall_risk' => 'required|string',
                'contract_file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
                'service_requirements_text' => 'nullable|string',
            ]);

            Log::info('Validation passed for Contract Renewal', $validated);

            $serviceRequirements = $request->input('service_requirements_text', null);

            $filePath = null;
            if ($request->hasFile('contract_file')) {
                $filePath = $request->file('contract_file')->store('contract_renewals', 'public');
                Log::info('Contract file uploaded successfully to: ' . $filePath);
            }

            // Create Contract Renewal record
            $contractRenewal = ContractRenewal::create([
                'contract_id' => $validated['contract_id'],
                'vendor_id' => $validated['vendor_id'] ?? null,
                'vendor_option' => $validated['vendor_option'],
                'department_id' => $validated['department_id'] ?? null,
                'division_id' => $validated['division_id'],
                'vendor_review' => $validated['vendor_review'] ?? null,
                'service_requirements_text' => $serviceRequirements,
                'contract_type' => $validated['contract_type'],
                'category' => $validated['category'],
                'cost' => $validated['cost'],
                'duration_months' => $validated['duration_months'],
                'status' => $validated['status'],
                'likelihood_rating' => $validated['likelihood_rating'],
                'impact_if_not_requested' => $validated['impact_if_not_requested'],
                'overall_risk' => $validated['overall_risk'],
                'created_by' => Auth::id(),
                'service_requirements_file' => $filePath,
            ]);

            Log::info('Contract Renewal created with ID: ' . $contractRenewal->id);

            Workflow::create([
                'user_id' => Auth::id(),
                'work_flow_status' => 'sent to approval',
                'work_flow_completed' => 0,
                'contract_renewal_id' => $contractRenewal->id,
            ]);

            Log::info('Workflow created for Contract Renewal ID: ' . $contractRenewal->id);

            return redirect()->back()->with('success', 'Contract Renewal Request submitted successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating Contract Renewal: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->back()->with('error', 'Error submitting request: ' . $e->getMessage());
        }
    }

    /**
     * Show contract approval index
     */
    public function contractApprovalIndex()
    {
        $user = Auth::user();

        // Get allowed department IDs based on user role
        $allowedDepartmentIds = $this->allowedDepartmentIds($user);

        // Build query with department filtering
        $relationships = [];
        if (method_exists(CcbrtContract::class, 'workflows')) {
            $relationships[] = 'workflows.histories';
            $relationships[] = 'workflows.user';
        }
        $contractsQuery = CcbrtContract::with($relationships);

        // Apply department filter if user is Line Manager or HEC member
        if ($user->hasRole('line-manager') || $user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo'])) {
            if (!empty($allowedDepartmentIds)) {
                $contractsQuery->whereIn('department_id', $allowedDepartmentIds);
            } else {
                // If no allowed departments, return empty collection
                $contractsQuery->whereRaw('1 = 0');
            }
        }

        $contracts = $contractsQuery->get();

        foreach ($contracts as $contract) {
            $currentApproval = null;
            $nextApproval = null;

            if (method_exists(CcbrtContract::class, 'workflows') && $contract->relationLoaded('workflows')) {
                $currentApproval = $contract->workflows
                    ->where('work_flow_status', 'pending')
                    ->first();

                $nextApproval = $contract->workflows
                    ->where('work_flow_status', 'queued')
                    ->first();
            }

            $currentApprover = $currentApproval?->user;
            $nextApprover = $nextApproval?->user;

            $contract->current_approver = $currentApprover;
            $contract->next_approver = $nextApprover;
        }

        return view('procurements.contracts.approveContract', compact('contracts'));
    }

    /**
     * Approve a contract and forward to next stage
     * Workflow: Line Manager (rates & chooses action) → HEC (reviews & rates) → Procurement (processes) → Active
     */
    public function approveContract(Request $request, $id)
    {
        // Build relationships array defensively
        $relationships = ['department'];
        if (method_exists(CcbrtContract::class, 'workflow')) {
            $relationships[] = 'workflow';
        }
        if (method_exists(CcbrtContract::class, 'workflows')) {
            $relationships[] = 'workflows.histories';
        }

        $contract = CcbrtContract::with($relationships)->findOrFail($id);
        $user = Auth::user();

        // Security check: Ensure user can only approve contracts from their allowed departments
        $allowedDepartmentIds = $this->allowedDepartmentIds($user);

        // For Line Managers and HEC members, check if contract department is in their allowed list
        if (($user->hasRole('line-manager') || $user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo'])) && $contract->department_id) {
            if (!in_array($contract->department_id, $allowedDepartmentIds)) {
                return redirect()->route('procurements.contracts.index')
                    ->withErrors('You do not have permission to approve contracts from this department.');
            }
        }

        // Get workflow - try singular relationship first, then plural, or get the latest
        $workflow = null;
        if (method_exists(CcbrtContract::class, 'workflow') && $contract->relationLoaded('workflow')) {
            $workflow = $contract->workflow;
        }
        if (!$workflow && method_exists(CcbrtContract::class, 'workflows')) {
            // Try to get the latest workflow from the workflows collection
            $workflow = $contract->workflows()->latest()->first();
        }

        // If still no workflow, create one (for renewals or contracts that don't have one)
        if (!$workflow) {
            try {
                $workflow = Workflow::create([
                    'user_id' => $user->id,
                    'work_flow_status' => 'pending',
                    'work_flow_completed' => 0,
                    'ccbrt_contract_id' => $contract->id,
                ]);

                if ($workflow && method_exists(CcbrtContract::class, 'workflow')) {
                    // Reload the relationship
                    $contract->load('workflow');
                    if ($contract->relationLoaded('workflow')) {
                        $workflow = $contract->workflow;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to create workflow for contract: ' . $e->getMessage(), [
                    'contract_id' => $contract->id,
                    'user_id' => $user->id,
                    'error' => $e->getTraceAsString()
                ]);
            }
        }

        if (!$workflow) {
            return redirect()->route('procurements.contracts.index')
                ->withErrors('No workflow found for this contract and could not create one. Please contact the administrator.');
        }

        $currentHistory = $workflow->histories()
            ->where('attended_by', Auth::id())
            ->where('status', 0) // pending
            ->latest()
            ->first();

        // If no history found, create one for the current approver (for renewals initiated by Procurement)
        if (!$currentHistory) {
            // Check if this is a renewal pending Line Manager review
            if ($contract->approval_stage === 'line_manager' && $contract->current_approver_id == $user->id) {
                $currentHistory = WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'remark' => 'Contract renewal pending Line Manager review',
                    'forwarded_by' => $contract->created_by ?? $user->id,
                    'attended_by' => $user->id,
                    'status' => 0, // pending
                    'step_name' => 'Line Manager',
                    'created_at' => Carbon::now(),
                ]);
            } else {
                return redirect()->route('procurements.contracts.index')
                    ->withErrors('No pending approval found for you.');
            }
        }

        $currentStep = $currentHistory->step_name ?? $contract->approval_stage;
        $remark = $request->remark ?? 'Approved by ' . Auth::user()->name;
        $nextStep = null;
        $nextApprover = null;
        $nextStatus = null;

        // Handle Line Manager approval - they rate and choose action (renew/terminate/hold)
        if ($currentStep === 'Line Manager') {
            // Validate required fields for Line Manager
            $request->validate([
                'contract_rating' => 'required|numeric|min:1|max:5',
                'contract_action' => 'required|in:renew,terminate,hold',
            ]);

            // Store Line Manager rating
            $contract->line_manager_rating = $request->contract_rating;
            $contract->contract_action = $request->contract_action;
            $contract->evaluation_score = $request->contract_rating; // Initial evaluation score

            // Reset notification tracking since Line Manager is taking action
            $contract->last_notification_sent_at = null;
            $contract->notification_escalation_level = 'none';

            $remark = 'Contract rated ' . $request->contract_rating . '/5 by Line Manager. Action: ' . ucfirst($request->contract_action);
            if ($request->remark) {
                $remark .= '. Comments: ' . $request->remark;
            }

            // If action is terminate, mark as terminated and end workflow
            if ($request->contract_action === 'terminate') {
                $contract->status = 'terminated';
                $contract->lifecycle_stage = 'termination';
                $contract->approval_stage = 'rejected';
                $contract->current_approver_id = null;
                $contract->renewal_status = 'not_renewed';

                $currentHistory->status = 1; // approved
                $currentHistory->remark = $remark;
                $currentHistory->save();

                $workflow->work_flow_status = 'terminated';
                $workflow->work_flow_completed = 1;
                $workflow->save();
                $contract->save();

                return redirect()->route('procurements.contracts.index')
                    ->with('success', 'Contract terminated by Line Manager.');
            }

            // If action is hold, mark as hold and pause workflow
            if ($request->contract_action === 'hold') {
                $contract->status = 'in_progress';
                $contract->lifecycle_stage = 'suspension';
                $contract->approval_stage = 'line_manager';
                $contract->renewal_status = 'pending';

                $currentHistory->status = 1; // approved
                $currentHistory->remark = $remark . ' - Contract on hold';
                $currentHistory->save();
                $contract->save();

                return redirect()->route('procurements.contracts.index')
                    ->with('success', 'Contract put on hold by Line Manager.');
            }

            // If action is renew, forward to HEC
            if ($request->contract_action === 'renew') {
                $contract->renewal_status = 'pending';
                $contract->status = 'in_progress';
                $contract->lifecycle_stage = 'renewal';

                // Forward to HEC member of the department
                $department = $contract->department;
                if ($department && $department->hec_id) {
                    $hec = \App\Models\Hec::find($department->hec_id);
                    if ($hec) {
                        $hecLevelName = strtoupper(trim($hec->hec_level_name));
                        $roleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CRHDO' => 'crhdo'];
                        $roleSlug = $roleMap[$hecLevelName] ?? 'cms';

                        $hecMember = User::role($roleSlug)->first();
                        if ($hecMember) {
                            $nextStep = 'HEC Member';
                            $nextApprover = $hecMember;
                            $nextStatus = 'Pending at HEC Member';
                            $contract->approval_stage = 'hec';
                            $contract->current_approver_id = $hecMember->id;
                        }
                    }
                }
            }
        }

        // Handle HEC Member approval - they review and rate (required)
        if ($currentStep === 'HEC Member') {
            // HEC must provide a rating (1-4 scale, same as Line Manager)
            $request->validate([
                'hec_rating' => 'required|integer|min:1|max:4',
                'hec_comments' => 'nullable|string|max:2000',
            ]);

            $contract->hec_rating = $request->hec_rating;
            // Average the ratings
            $ratings = array_filter([$contract->line_manager_rating, $contract->hec_rating]);
            $contract->evaluation_score = count($ratings) > 0 ? array_sum($ratings) / count($ratings) : $contract->line_manager_rating;

            // Check if this is a renewal
            $isRenewal = $contract->renewal_status === 'pending' || $contract->lifecycle_stage === 'renewal';

            $remark = ($isRenewal ? 'Contract renewal' : 'Contract') . ' reviewed and rated (' . $request->hec_rating . '/4) by HEC Member ' . Auth::user()->name;
            if ($request->hec_comments) {
                $remark .= '. Comments: ' . $request->hec_comments;
            }

            // Forward to Procurement Officer for processing
            $procurementOfficer = User::role('procurement-officer')->first();
            if ($procurementOfficer) {
                $nextStep = 'Procurement';
                $nextApprover = $procurementOfficer;
                $nextStatus = 'Pending at Procurement for Processing';
                $contract->approval_stage = 'procurement';
                $contract->current_approver_id = $procurementOfficer->id;
                $contract->status = 'in_progress'; // Status remains in_progress until Procurement completes
                // Preserve renewal_status if it's a renewal
                if ($isRenewal && $contract->renewal_status === 'pending') {
                    $contract->renewal_status = 'pending'; // Keep as pending until Procurement finalizes
                }
            } else {
                return redirect()->route('procurements.contracts.index')
                    ->withErrors('No Procurement Officer found. Cannot proceed.');
            }
        }

        // Handle Procurement Officer approval - they process and mark as active
        if ($currentStep === 'Procurement') {
            // Validate and update contract details if provided
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'duration_months' => 'nullable|integer|min:1',
                'cost' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:10',
                'vendor_id' => 'nullable|exists:ccbrt_vendors,id',
                'contract_number' => 'nullable|string|max:255',
                'contract_type' => 'nullable|string|max:255',
                'remark' => 'nullable|string|max:2000',
                'terms_of_reference' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            ]);

            // Check if this is a renewal
            $isRenewal = $contract->renewal_status === 'pending' || $contract->lifecycle_stage === 'renewal';

            if ($isRenewal) {
                // CREATE A NEW CONTRACT for the renewal
                // Find the original contract (if this contract has a parent, use it; otherwise this is the original)
                $originalContract = (method_exists(CcbrtContract::class, 'parentContract') && $contract->relationLoaded('parentContract') && $contract->parentContract) ? $contract->parentContract : $contract;

                // Calculate renewal term number
                $maxTerm = CcbrtContract::where('parent_contract_id', $originalContract->id)
                    ->max('renewal_term_number') ?? 0;
                $renewalTermNumber = $maxTerm + 1;

                // Create new contract with data from the renewal request
                $newContract = new CcbrtContract();
                $newContract->parent_contract_id = $originalContract->id;
                $newContract->renewal_term_number = $renewalTermNumber;
                $newContract->title = $contract->title . ' - Renewal Term ' . $renewalTermNumber;
                $newContract->description = $contract->description;
                $newContract->contract_type = $request->contract_type ?? $contract->contract_type;
                $newContract->division_id = $contract->division_id;
                $newContract->department_id = $contract->department_id;
                $newContract->vendor_id = $request->vendor_id ?? $contract->vendor_id;
                $newContract->contract_manager_id = $contract->contract_manager_id;
                $newContract->start_date = $request->start_date ?? $contract->start_date;
                $newContract->end_date = $request->end_date ?? $contract->end_date;
                $newContract->duration_months = $request->duration_months ?? $contract->duration_months;
                $newContract->cost = $request->cost ?? $contract->cost;
                $newContract->currency = $request->currency ?? $contract->currency ?? 'TZS';

                // Generate unique contract number for renewal
                if ($request->contract_number && !CcbrtContract::where('contract_number', $request->contract_number)->exists()) {
                    // Use provided contract number if it's unique
                    $newContract->contract_number = $request->contract_number;
                } else {
                    // Generate a unique contract number
                    $year = date('Y');
                    $lastContract = CcbrtContract::whereYear('created_at', $year)->latest()->first();
                    $sequence = 1;
                    if ($lastContract && $lastContract->contract_number) {
                        // Extract sequence from last contract number (format: CNT-YYYY-####)
                        $parts = explode('-', $lastContract->contract_number);
                        if (count($parts) >= 3 && is_numeric($parts[2])) {
                            $sequence = (int) $parts[2] + 1;
                        }
                    }

                    // Keep trying until we find a unique number
                    do {
                        $contractNumber = 'CNT-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                        $exists = CcbrtContract::where('contract_number', $contractNumber)->exists();
                        if ($exists) {
                            $sequence++;
                        }
                    } while ($exists);

                    $newContract->contract_number = $contractNumber;
                }

                $newContract->status = 'active';
                $newContract->lifecycle_stage = 'execution';
                $newContract->renewal_status = 'renewed';
                $newContract->approval_stage = 'approved';
                $newContract->current_approver_id = null;
                $newContract->created_by = Auth::id();
                $newContract->creation_date = Carbon::now();

                // Copy ratings and evaluation
                $newContract->line_manager_rating = $contract->line_manager_rating;
                $newContract->hec_rating = $contract->hec_rating;
                $newContract->evaluation_score = $contract->evaluation_score;

                // Copy risk assessment
                $newContract->likelihood_rating = $contract->likelihood_rating;
                $newContract->impact_if_not_requested = $contract->impact_if_not_requested;
                $newContract->overall_risk = $contract->overall_risk;

                // Handle Terms of Reference file upload for renewal
                if ($request->hasFile('terms_of_reference')) {
                    $file = $request->file('terms_of_reference');
                    $filename = 'tor_' . time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('contracts/tor', $filename, 'public');
                    $newContract->terms_of_reference_path = '/storage/' . $filePath;
                } else {
                    // Copy TOR from original contract if no new one is uploaded
                    $newContract->terms_of_reference_path = $contract->terms_of_reference_path;
                }

                // Copy documents if they exist
                $newContract->file_path = $contract->file_path;
                $newContract->signed_contract_path = $contract->signed_contract_path;
                $newContract->terms_conditions_path = $contract->terms_conditions_path;
                $newContract->sla_document_path = $contract->sla_document_path;

                $newContract->save();

                // Create workflow for the new contract
                $newWorkflow = Workflow::create([
                    'user_id' => Auth::id(),
                    'ccbrt_contract_id' => $newContract->id,
                    'work_flow_status' => 'approved',
                    'work_flow_completed' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // Create workflow history for the new contract
                WorkFlowHistory::create([
                    'work_flow_id' => $newWorkflow->id,
                    'forwarded_by' => Auth::id(),
                    'attended_by' => Auth::id(),
                    'step_name' => 'Procurement',
                    'status' => 1, // approved
                    'remark' => $request->remark ?? 'Contract renewal finalized and activated as new contract term ' . $renewalTermNumber,
                    'created_at' => Carbon::now(),
                ]);

                // Mark original contract's renewal status
                $contract->renewal_status = 'renewed';
                $contract->status = 'expired'; // Original contract is now expired
                $contract->save();

                // Mark original workflow as completed
                $workflow->work_flow_status = 'approved';
                $workflow->work_flow_completed = 1;
                $workflow->save();

                $remark = 'Contract renewal finalized. New contract created as Term ' . $renewalTermNumber . '. Original contract marked as expired.';
                if ($request->remark) {
                    $remark .= ' Notes: ' . $request->remark;
                }

                // Update current history for the original contract
                $currentHistory->status = 1;
                $currentHistory->remark = $remark;
                $currentHistory->save();

                $contract->save();

                // Return early - don't continue with the rest of the function
                return redirect()->route('procurements.contracts.index')
                    ->with('success', 'Contract renewal finalized! New contract (Term ' . $renewalTermNumber . ') has been created and activated.');
            } else {
                // Regular contract (not a renewal) - update existing contract
                // Update contract details if provided
                if ($request->has('start_date') && $request->start_date) {
                    $contract->start_date = $request->start_date;
                }
                if ($request->has('end_date') && $request->end_date) {
                    $contract->end_date = $request->end_date;
                }
                if ($request->has('duration_months') && $request->duration_months) {
                    $contract->duration_months = $request->duration_months;
                }
                if ($request->has('cost') && $request->cost !== null) {
                    $contract->cost = $request->cost;
                }
                if ($request->has('currency') && $request->currency) {
                    $contract->currency = $request->currency;
                }
                if ($request->has('vendor_id') && $request->vendor_id) {
                    $contract->vendor_id = $request->vendor_id;
                }
                if ($request->has('contract_number') && $request->contract_number) {
                    $contract->contract_number = $request->contract_number;
                }
                if ($request->has('contract_type') && $request->contract_type) {
                    $contract->contract_type = $request->contract_type;
                }

                // Handle Terms of Reference file upload
                if ($request->hasFile('terms_of_reference')) {
                    $file = $request->file('terms_of_reference');
                    $filename = 'tor_' . time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('contracts/tor', $filename, 'public');
                    $contract->terms_of_reference_path = '/storage/' . $filePath;
                }

                // Procurement completes the process - contract becomes active
                $contract->status = 'active';
                $contract->lifecycle_stage = 'execution';
                $contract->approval_stage = 'approved';
                $contract->current_approver_id = null;

                // Mark workflow as completed
                $workflow->work_flow_status = 'approved';
                $workflow->work_flow_completed = 1;
                $workflow->save();
            }
        }

        // Mark current step as approved
        $currentHistory->status = 1; // approved
        $currentHistory->remark = $remark;
        $currentHistory->save();

        // Create next workflow history if there's a next step
        if ($nextStep && $nextApprover) {
            WorkFlowHistory::create([
                'work_flow_id' => (method_exists(CcbrtContract::class, 'workflow') && $contract->relationLoaded('workflow') && $contract->workflow) ? $contract->workflow->id : $workflow->id,
                'forwarded_by' => Auth::id(),
                'attended_by' => $nextApprover->id,
                'step_name' => $nextStep,
                'status' => 0, // pending
                'remark' => "Forwarded to {$nextStep}",
                'created_at' => Carbon::now(),
            ]);

            $workflow->work_flow_status = strtolower(str_replace(' ', '_', $nextStatus));
            $workflow->save();

            // Send notification to next approver (queued)
            try {
                Mail::to($nextApprover->email)->queue(new ContractAddedMail($contract, $nextApprover, "Contract requires your {$nextStep} review."));
            } catch (\Exception $e) {
                Log::error('Failed to queue contract notification: ' . $e->getMessage());
            }
        }

        $contract->save();

        $message = $nextStep
            ? "Contract approved and forwarded to {$nextStep}!"
            : "Contract fully approved! Status: " . ucfirst($contract->status);

        return redirect()->route('procurements.contracts.index')
            ->with('success', $message);
    }

    /**
     * Reject a contract
     */
    public function rejectContract(Request $request, $id)
    {
        // Build relationships array defensively
        $relationships = [];
        if (method_exists(CcbrtContract::class, 'workflow')) {
            $relationships[] = 'workflow';
        }

        $contract = CcbrtContract::with($relationships)->findOrFail($id);

        $workflow = null;
        if (method_exists(CcbrtContract::class, 'workflow') && $contract->relationLoaded('workflow')) {
            $workflow = $contract->workflow;
        }

        if (!$workflow) {
            // Try to get workflow directly from database
            $workflow = Workflow::where('ccbrt_contract_id', $contract->id)->latest()->first();
        }

        if (!$workflow) {
            return redirect()->route('procurements.contracts.index')
                ->withErrors('No workflow found for this contract.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $workflowHistory = null;
        if (method_exists(CcbrtContract::class, 'workflow') && $contract->relationLoaded('workflow') && $contract->workflow) {
            $workflowHistory = $contract->workflow->histories()
                ->where('attended_by', Auth::id())
                ->where('status', 0) // pending
                ->latest()
                ->first();
        }

        if (!$workflowHistory && $workflow) {
            $workflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', Auth::id())
                ->where('status', 0)
                ->latest()
                ->first();
        }

        if ($workflowHistory) {
            $workflowHistory->status = 2; // rejected
            $workflowHistory->remark = 'Rejected by ' . Auth::user()->name . ': ' . $request->rejection_reason;
            $workflowHistory->rejection_reason = $request->rejection_reason;
            $workflowHistory->save();

            // Check if this is a renewal request
            $isRenewal = $contract->renewal_status === 'pending' || $contract->lifecycle_stage === 'renewal';

            // Update contract and workflow status
            $contract->approval_stage = 'rejected';
            $contract->status = 'terminated';
            $contract->lifecycle_stage = 'termination';

            // If it's a renewal, mark renewal status as terminated
            if ($isRenewal) {
                $contract->renewal_status = 'terminated';
            }

            $contract->current_approver_id = null;
            $contract->save();

            if (!$workflow) {
                $workflow = (method_exists(CcbrtContract::class, 'workflow') && $contract->relationLoaded('workflow') && $contract->workflow)
                    ? $contract->workflow
                    : Workflow::where('ccbrt_contract_id', $contract->id)->latest()->first();
            }

            if ($workflow) {
                $workflow->work_flow_status = 'rejected';
                $workflow->work_flow_completed = 1;
                $workflow->save();
            }

            $message = $isRenewal
                ? 'Contract renewal rejected and terminated successfully!'
                : 'Contract rejected successfully!';

            return redirect()->route('procurements.contracts.index')
                ->with('success', $message);
        }

        return redirect()->route('procurements.contracts.index')
            ->withErrors('Error rejecting contract. Please try again.');
    }

    /**
     * Initiate contract renewal process (when contract expires)
     * Can be initiated by: Line Manager of the department OR Procurement Officer
     */
    public function initiateRenewal(Request $request, $id)
    {
        $contract = CcbrtContract::with(['workflow', 'department'])->findOrFail($id);
        $user = Auth::user();

        $isLineManager = $user->hasRole('line-manager');
        $isProcurementOfficer = $user->hasRole('procurement-officer');

        // Check permissions
        if (!$isLineManager && !$isProcurementOfficer) {
            return redirect()->route('procurements.contracts.index')
                ->withErrors('Only Line Managers or Procurement Officers can initiate contract renewals.');
        }

        // Line Managers can only renew contracts from their department
        if ($isLineManager && $contract->department && $user->deptId != $contract->department_id) {
            return redirect()->route('procurements.contracts.index')
                ->withErrors('You can only renew contracts from your own department.');
        }

        // Check if contract is expired or expiring soon (within 30 days)
        $isExpired = $contract->status == 'expired';
        $isExpiringSoon = false;
        if ($contract->end_date) {
            $daysUntilExpiry = Carbon::parse($contract->end_date)->diffInDays(Carbon::now(), false);
            $isExpiringSoon = $daysUntilExpiry <= 30 && $daysUntilExpiry >= 0;
        }

        if (!$isExpired && !$isExpiringSoon) {
            return redirect()->route('procurements.contracts.index')
                ->withErrors('Only expired contracts or contracts expiring within 30 days can be renewed.');
        }

        // Validation - rating only required if Line Manager initiates
        $validationRules = [
            'renewal_comments' => 'nullable|string|max:2000',
        ];

        if ($isLineManager) {
            $validationRules['contract_rating'] = 'required|integer|min:1|max:4';
        }

        $request->validate($validationRules);

        // Create new workflow for renewal
        $workflow = Workflow::create([
            'user_id' => $user->id,
            'work_flow_status' => 'pending',
            'work_flow_completed' => 0,
            'ccbrt_contract_id' => $contract->id,
        ]);

        // Get Line Manager for the department
        $department = $contract->department;
        $lineManager = null;
        if ($department) {
            $lineManager = User::role('line-manager')->where('deptId', $department->id)->first();
        }

        if ($isProcurementOfficer) {
            // If Procurement Officer initiates, forward to Line Manager for rating
            $contract->renewal_status = 'pending';
            $contract->status = 'in_progress';
            $contract->lifecycle_stage = 'renewal';
            $contract->approval_stage = 'line_manager';

            if ($lineManager) {
                $contract->current_approver_id = $lineManager->id;
                $contract->save();

                $remark = 'Contract renewal initiated by Procurement Officer ' . $user->name;
                if ($request->renewal_comments) {
                    $remark .= '. Comments: ' . $request->renewal_comments;
                }
                $remark .= ' - Forwarded to Line Manager for review and rating';

                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'remark' => $remark,
                    'forwarded_by' => $user->id,
                    'attended_by' => $lineManager->id,
                    'status' => 0, // pending
                    'step_name' => 'Line Manager',
                    'created_at' => Carbon::now(),
                ]);

                // Send notification to Line Manager (queued)
                try {
                    Mail::to($lineManager->email)->queue(new ContractAddedMail($contract, $lineManager, 'A contract renewal has been initiated by Procurement Officer. Please review and rate the contract.'));
                } catch (\Exception $e) {
                    Log::error('Failed to queue renewal notification to line manager: ' . $e->getMessage());
                }

                return redirect()->route('procurements.contracts.index')
                    ->with('success', 'Contract renewal initiated! Forwarded to Line Manager for review and rating.');
            } else {
                return redirect()->route('procurements.contracts.index')
                    ->withErrors('No Line Manager found for this department. Cannot proceed with renewal.');
            }
        } else {
            // If Line Manager initiates, they rate it and forward to HEC
            $contract->line_manager_rating = $request->contract_rating;
            $contract->evaluation_score = $request->contract_rating;
            $contract->contract_action = 'renew';
            $contract->renewal_status = 'pending';
            $contract->status = 'in_progress';
            $contract->lifecycle_stage = 'renewal';
            $contract->approval_stage = 'hec';
            // Reset notification tracking since action is being taken
            $contract->last_notification_sent_at = null;
            $contract->notification_escalation_level = 'none';
            $contract->save();

            $remark = 'Contract renewal reviewed and rated (' . $request->contract_rating . '/5) by Line Manager ' . $user->name;
            if ($request->renewal_comments) {
                $remark .= '. Comments: ' . $request->renewal_comments;
            }

            WorkFlowHistory::create([
                'work_flow_id' => $workflow->id,
                'remark' => $remark,
                'forwarded_by' => $user->id,
                'attended_by' => $user->id,
                'status' => 1, // approved by line manager
                'step_name' => 'Line Manager',
                'created_at' => Carbon::now(),
            ]);

            // Forward to HEC (they will also rate)
            $hecMember = null;
            if ($department && $department->hec_id) {
                $hec = Hec::find($department->hec_id);
                if ($hec) {
                    $hecLevelName = strtoupper(trim($hec->hec_level_name));
                    $roleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CRHDO' => 'crhdo'];
                    $roleSlug = $roleMap[$hecLevelName] ?? 'cms';

                    $hecMember = User::role($roleSlug)->first();
                    if ($hecMember) {
                        $contract->current_approver_id = $hecMember->id;
                        $contract->approval_stage = 'hec';
                        $contract->save();

                        // Create workflow history for HEC (they will also rate)
                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'remark' => 'Forwarded to HEC for review and rating',
                            'forwarded_by' => $user->id,
                            'attended_by' => $hecMember->id,
                            'status' => 0, // pending
                            'step_name' => 'HEC Member',
                            'created_at' => Carbon::now(),
                        ]);

                        // Send notification to HEC (queued)
                        $emailSent = false;
                        $emailError = null;
                        try {
                            Mail::to($hecMember->email)->queue(new ContractAddedMail($contract, $hecMember, 'A contract renewal has been reviewed by Line Manager. Please review and rate the contract.'));
                            $emailSent = true;
                            Log::info('Contract renewal notification queued for HEC Member', [
                                'contract_id' => $contract->id,
                                'hec_member_email' => $hecMember->email,
                                'hec_member_id' => $hecMember->id,
                                'hec_member_name' => ($hecMember->fname ?? '') . ' ' . ($hecMember->lname ?? '')
                            ]);
                        } catch (\Exception $e) {
                            $emailError = $e->getMessage();
                            Log::error('Failed to queue renewal notification to HEC', [
                                'contract_id' => $contract->id,
                                'hec_member_email' => $hecMember->email,
                                'error' => $emailError
                            ]);
                        }

                        $successMessage = 'Contract renewal request submitted successfully! Status set to "In Progress". ';
                        if ($emailSent) {
                            $successMessage .= 'Successfully sent notification to HEC Member: ' . trim(($hecMember->fname ?? '') . ' ' . ($hecMember->lname ?? '')) . ' (' . $hecMember->email . '). Awaiting HEC review and rating.';
                        } else {
                            $successMessage .= 'Warning: Failed to send notification email to HEC Member. The renewal request has been submitted, but please notify the HEC member manually.';
                            if ($emailError) {
                                Log::warning('HEC notification email failed but renewal was submitted', ['error' => $emailError]);
                            }
                        }

                        return redirect()->route('procurements.contracts.index')
                            ->with('success', $successMessage);
                    } else {
                        return redirect()->route('procurements.contracts.index')
                            ->withErrors('Failed: No HEC member found for this department. Cannot proceed with renewal.');
                    }
                } else {
                    return redirect()->route('procurements.contracts.index')
                        ->withErrors('Failed: No department found for this contract. Cannot proceed with renewal.');
                }
            } else {
                return redirect()->route('procurements.contracts.index')
                    ->withErrors('Failed: No HEC configuration found for this department. Cannot proceed with renewal.');
            }
        }
    }

    /**
     * Mark contract as vendor found (changes status to active)
     */
    public function markVendorFound(Request $request, $id)
    {
        $contract = CcbrtContract::findOrFail($id);

        $request->validate([
            'vendor_id' => 'required|exists:ccbrt_vendors,id',
        ]);

        $contract->vendor_id = $request->vendor_id;
        $contract->status = 'active';
        $contract->lifecycle_stage = 'execution';
        $contract->save();

        return redirect()->route('procurements.contracts.show', $contract->id)
            ->with('success', 'Vendor assigned and contract status changed to Active!');
    }

    /**
     * Send renewal reminder to Line Manager and optionally HEC for expiring contract
     * Only accessible by Procurement Officers
     */
    public function sendReminder(Request $request, $id)
    {
        // Log that the method was called
        Log::info('sendReminder method called', [
            'contract_id' => $id,
            'user_id' => Auth::id(),
            'user_email' => Auth::user()->email ?? 'N/A',
            'request_data' => $request->all()
        ]);

        $user = Auth::user();

        // Check if user is Procurement Officer
        if (!$user->hasRole('procurement-officer')) {
            Log::warning('sendReminder called by non-Procurement Officer', [
                'user_id' => $user->id,
                'user_roles' => $user->getRoleNames()->toArray()
            ]);
            $errorMessage = 'Only Procurement Officers can send reminders.';

            // Return JSON for AJAX requests, otherwise redirect
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 403);
            }

            return redirect()->route('procurements.contracts.index')
                ->withErrors($errorMessage);
        }

        $contract = CcbrtContract::with(['department', 'vendor'])->findOrFail($id);

        // Ensure contract is fresh and relationships are loaded
        $contract->load(['department', 'vendor']);

        Log::info('Contract found for reminder', [
            'contract_id' => $contract->id,
            'contract_title' => $contract->title,
            'contract_department_id' => $contract->department_id,
            'contract_end_date' => $contract->end_date,
            'has_department' => $contract->relationLoaded('department'),
            'has_vendor' => $contract->relationLoaded('vendor')
        ]);

        // Check if contract is expiring soon (within 30 days)
        if (!$contract->end_date) {
            $errorMessage = 'Contract does not have an end date.';

            // Return JSON for AJAX requests, otherwise redirect
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            return redirect()->route('procurements.contracts.index')
                ->withErrors($errorMessage);
        }

        $today = Carbon::now();
        $endDate = Carbon::parse($contract->end_date);
        // Calculate days until expiry (positive if future, negative if past)
        $daysUntilExpiry = $today->diffInDays($endDate, false);

        // Allow contracts expiring within 30 days (0 to 30 days from now)
        // Also allow contracts that expired recently (within last 7 days) for reminders
        if ($daysUntilExpiry > 30 || $daysUntilExpiry < -7) {
            Log::warning('Contract reminder attempted for contract outside valid range', [
                'contract_id' => $contract->id,
                'days_until_expiry' => $daysUntilExpiry,
                'end_date' => $endDate->format('Y-m-d'),
                'today' => $today->format('Y-m-d')
            ]);
            return redirect()->route('procurements.contracts.index')
                ->withErrors('This contract is not expiring within 30 days or has expired more than 7 days ago. Days until expiry: ' . $daysUntilExpiry);
        }

        // Get Line Manager for the contract's department
        if (!$contract->department) {
            $errorMessage = 'Contract does not have an associated department.';

            // Return JSON for AJAX requests, otherwise redirect
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            return redirect()->route('procurements.contracts.index')
                ->withErrors($errorMessage);
        }

        $sendToHEC = $request->has('send_to_hec') && $request->send_to_hec == '1';
        $remindersSent = 0;
        $recipients = [];

        // Send to Line Manager (always sent)
        $lineManagers = User::role('line-manager')
            ->where('deptId', $contract->department_id)
            ->where('status', 'active')
            ->get();

        if ($lineManagers->isEmpty()) {
            Log::error('No Line Manager found for contract reminder', [
                'contract_id' => $contract->id,
                'contract_department_id' => $contract->department_id,
                'contract_department_name' => $contract->department->dept_name ?? 'N/A'
            ]);
            $errorMessage = 'No Line Manager found for this contract\'s department: ' . ($contract->department->dept_name ?? 'Unknown');

            // Return JSON for AJAX requests, otherwise redirect
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            return redirect()->route('procurements.contracts.index')
                ->withErrors($errorMessage);
        }

        foreach ($lineManagers as $lineManager) {
            try {
                // Validate email address
                if (empty($lineManager->email) || !filter_var($lineManager->email, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('Invalid or missing email for Line Manager', [
                        'contract_id' => $contract->id,
                        'line_manager_id' => $lineManager->id,
                        'line_manager_email' => $lineManager->email ?? 'NULL'
                    ]);
                    continue; // Skip this Line Manager
                }

                // Format message based on whether contract is expiring or expired
                if ($daysUntilExpiry >= 0) {
                    $message = "URGENT REMINDER: Contract '{$contract->title}' is expiring in {$daysUntilExpiry} days (Expiry Date: {$endDate->format('Y-m-d')}). Please review and take appropriate action (Renew, Terminate, or Hold).";
                } else {
                    $message = "URGENT REMINDER: Contract '{$contract->title}' expired " . abs($daysUntilExpiry) . " days ago (Expiry Date: {$endDate->format('Y-m-d')}). Please review and take appropriate action (Renew, Terminate, or Hold).";
                }

                // Send email immediately for reminders (time-sensitive)
                // Use EXACT same method as settings test email for reliability
                $emailBody = $message . "\n\n";
                $emailBody .= "Contract Details:\n";
                $emailBody .= "Title: {$contract->title}\n";
                if ($contract->vendor) {
                    $emailBody .= "Vendor: {$contract->vendor->name}\n";
                }
                if ($contract->department) {
                    $emailBody .= "Department: {$contract->department->dept_name}\n";
                }
                if ($contract->start_date) {
                    $emailBody .= "Start Date: " . Carbon::parse($contract->start_date)->format('Y-m-d') . "\n";
                }
                $emailBody .= "End Date: {$endDate->format('Y-m-d')}\n";
                if ($contract->value) {
                    $emailBody .= "Value: " . number_format($contract->value, 2) . "\n";
                }
                $emailBody .= "\nPlease review this contract and take appropriate action (Renew, Terminate, or Hold).\n\n";
                $emailBody .= "View Contract: " . route('procurements.contracts.index') . "\n\n";
                $emailBody .= "Thanks,\n" . config('app.name');

                // Use EXACT same Mail::raw() pattern as SettingsController::sendTestEmail()
                Mail::raw($emailBody, function ($message) use ($lineManager, $contract) {
                    $message->to($lineManager->email)
                        ->subject('Contract Renewal Reminder: ' . $contract->title);
                });

                $remindersSent++;
                $recipients[] = 'Line Manager: ' . ($lineManager->fname ?? '') . ' ' . ($lineManager->lname ?? '') . ' (' . $lineManager->email . ')';

                Log::info('Contract renewal reminder sent to Line Manager', [
                    'contract_id' => $contract->id,
                    'contract_title' => $contract->title,
                    'line_manager_email' => $lineManager->email,
                    'line_manager_id' => $lineManager->id,
                    'line_manager_name' => ($lineManager->fname ?? '') . ' ' . ($lineManager->lname ?? ''),
                    'days_until_expiry' => $daysUntilExpiry,
                    'contract_department_id' => $contract->department_id,
                    'contract_department_name' => $contract->department->dept_name ?? 'N/A',
                    'mail_config' => [
                        'driver' => config('mail.default'),
                        'host' => config('mail.mailers.smtp.host'),
                        'port' => config('mail.mailers.smtp.port')
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send contract reminder to Line Manager', [
                    'contract_id' => $contract->id,
                    'line_manager_email' => $lineManager->email ?? 'NULL',
                    'line_manager_id' => $lineManager->id,
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't increment remindersSent if email failed
            }
        }

        // Send to HEC if option is selected
        if ($sendToHEC && $contract->department->hec_id) {
            $hec = Hec::find($contract->department->hec_id);
            if ($hec) {
                $hecLevelName = strtoupper(trim($hec->hec_level_name));
                $roleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CRHDO' => 'crhdo'];
                $roleSlug = $roleMap[$hecLevelName] ?? 'cms';

                $hecMembers = User::role($roleSlug)->where('status', 'active')->get();

                foreach ($hecMembers as $hecMember) {
                    try {
                        $message = "URGENT REMINDER: Contract '{$contract->title}' from {$contract->department->dept_name} is expiring in {$daysUntilExpiry} days (Expiry Date: {$endDate->format('Y-m-d')}). Line Manager has been notified. Please monitor and ensure appropriate action is taken (Renew, Terminate, or Hold).";

                        // Send email immediately for reminders (time-sensitive)
                        // Use EXACT same method as settings test email for reliability
                        $emailBody = $message . "\n\n";
                        $emailBody .= "Contract Details:\n";
                        $emailBody .= "Title: {$contract->title}\n";
                        if ($contract->vendor) {
                            $emailBody .= "Vendor: {$contract->vendor->name}\n";
                        }
                        if ($contract->department) {
                            $emailBody .= "Department: {$contract->department->dept_name}\n";
                        }
                        if ($contract->start_date) {
                            $emailBody .= "Start Date: " . Carbon::parse($contract->start_date)->format('Y-m-d') . "\n";
                        }
                        $emailBody .= "End Date: {$endDate->format('Y-m-d')}\n";
                        if ($contract->value) {
                            $emailBody .= "Value: " . number_format($contract->value, 2) . "\n";
                        }
                        $emailBody .= "\nPlease review this contract and take appropriate action (Renew, Terminate, or Hold).\n\n";
                        $emailBody .= "View Contract: " . route('procurements.contracts.index') . "\n\n";
                        $emailBody .= "Thanks,\n" . config('app.name');

                        // Use EXACT same Mail::raw() pattern as SettingsController::sendTestEmail()
                        Mail::raw($emailBody, function ($message) use ($hecMember, $contract) {
                            $message->to($hecMember->email)
                                ->subject('Contract Renewal Reminder: ' . $contract->title);
                        });

                        $remindersSent++;
                        $recipients[] = 'HEC Member: ' . $hecMember->email;

                        Log::info('Contract renewal reminder sent to HEC Member', [
                            'contract_id' => $contract->id,
                            'hec_member_email' => $hecMember->email,
                            'days_until_expiry' => $daysUntilExpiry
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send contract reminder to HEC Member', [
                            'contract_id' => $contract->id,
                            'hec_member_email' => $hecMember->email,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Update contract notification tracking only if reminders were queued successfully
        if ($remindersSent > 0) {
            $contract->last_notification_sent_at = $today;
            $contract->notification_escalation_level = $sendToHEC ? 'hec' : 'line_manager';
            $contract->save();

            $recipientList = implode(', ', $recipients);
            $successMessage = "Successfully sent email to {$remindersSent} recipient(s). Recipients: {$recipientList}";

            // Return JSON for AJAX requests, otherwise redirect
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'recipients' => $recipientList
                ]);
            }

            return redirect()->route('procurements.contracts.index')
                ->with('success', $successMessage);
        } else {
            $errorMessage = 'Failed to send reminder. No Line Manager found or email sending failed. Please check logs.';

            Log::error('No reminders were sent - check Line Manager assignment', [
                'contract_id' => $contract->id,
                'contract_department_id' => $contract->department_id,
                'line_managers_found' => $lineManagers->count(),
                'send_to_hec' => $sendToHEC
            ]);

            // Return JSON for AJAX requests, otherwise redirect
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            return redirect()->route('procurements.contracts.index')
                ->withErrors($errorMessage);
        }
    }

    /**
     * Send renewal reminders to Line Managers for ALL contracts expiring within 30 days
     * Only accessible by Procurement Officers
     */
    public function sendBulkReminders()
    {
        $user = Auth::user();

        // Check if user is Procurement Officer
        if (!$user->hasRole('procurement-officer')) {
            return redirect()->route('procurements.contracts.index')
                ->withErrors('Only Procurement Officers can send bulk reminders.');
        }

        $today = Carbon::now();
        $expiryThreshold = $today->copy()->addDays(30);

        // Get all contracts expiring within 30 days
        $expiringContracts = CcbrtContract::with(['department', 'vendor'])
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, $expiryThreshold])
            ->get();

        if ($expiringContracts->isEmpty()) {
            return redirect()->route('procurements.contracts.index')
                ->with('info', 'No contracts expiring within 30 days found.');
        }

        $remindersSent = 0;
        $remindersFailed = 0;
        $contractsByDepartment = $expiringContracts->groupBy('department_id');

        foreach ($contractsByDepartment as $departmentId => $contracts) {
            $department = Departments::find($departmentId);

            if (!$department) {
                $remindersFailed += $contracts->count();
                continue;
            }

            // Get Line Managers for this department
            $lineManagers = User::role('line-manager')
                ->where('deptId', $departmentId)
                ->where('status', 'active')
                ->get();

            if ($lineManagers->isEmpty()) {
                $remindersFailed += $contracts->count();
                continue;
            }

            // Send reminder email to each Line Manager for all contracts in their department
            foreach ($lineManagers as $lineManager) {
                try {
                    $contractsList = $contracts->map(function ($contract) use ($today) {
                        $endDate = Carbon::parse($contract->end_date);
                        $daysUntilExpiry = $today->diffInDays($endDate, false);
                        return [
                            'contract' => $contract,
                            'days_until_expiry' => $daysUntilExpiry,
                            'expiry_date' => $endDate->format('Y-m-d')
                        ];
                    });

                    $message = "URGENT REMINDER: You have {$contracts->count()} contract(s) expiring within 30 days. Please review and take appropriate action (Renew, Terminate, or Hold) for each contract.";

                    Mail::to($lineManager->email)->queue(new ContractsReport(
                        $contracts,
                        $message
                    ));

                    // Update notification tracking for all contracts
                    foreach ($contracts as $contract) {
                        $contract->last_notification_sent_at = $today;
                        $contract->notification_escalation_level = 'line_manager';
                        $contract->save();
                    }

                    $remindersSent += $contracts->count();
                    Log::info('Bulk contract renewal reminders sent to Line Manager', [
                        'line_manager_email' => $lineManager->email,
                        'department_id' => $departmentId,
                        'contracts_count' => $contracts->count()
                    ]);
                } catch (\Exception $e) {
                    $remindersFailed += $contracts->count();
                    Log::error('Failed to send bulk contract reminders to Line Manager', [
                        'line_manager_email' => $lineManager->email,
                        'department_id' => $departmentId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        if ($remindersSent > 0) {
            $message = "Successfully sent renewal reminders for {$remindersSent} contract(s).";
            if ($remindersFailed > 0) {
                $message .= " Failed to send {$remindersFailed} reminder(s).";
            }
            return redirect()->route('procurements.contracts.index')
                ->with('success', $message);
        } else {
            return redirect()->route('procurements.contracts.index')
                ->withErrors('Failed to send reminders. Please check if Line Managers exist for the departments.');
        }
    }

    /**
     * Export contracts to CSV with detailed information
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $viewType = $request->query('view', 'active');

        // Get allowed department IDs based on user role
        $allowedDepartmentIds = $this->allowedDepartmentIds($user);

        // Build query with department filtering
        // Eager load relationships (check if parentContract and renewals exist to avoid errors on older model versions)
        $relationships = ['division', 'department', 'vendor', 'creator', 'contractManager'];

        // Only add parentContract and renewals if they exist in the model
        if (method_exists(CcbrtContract::class, 'parentContract')) {
            $relationships[] = 'parentContract';
        }
        if (method_exists(CcbrtContract::class, 'renewals')) {
            $relationships[] = 'renewals';
        }

        $contractsQuery = CcbrtContract::with($relationships);

        // Apply department filter if user is Line Manager or HEC member
        if ($user->hasRole('line-manager') || $user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo'])) {
            if (!empty($allowedDepartmentIds)) {
                $contractsQuery->whereIn('department_id', $allowedDepartmentIds);
            } else {
                $contractsQuery->whereRaw('1 = 0');
            }
        }

        $contracts = $contractsQuery->get();

        $today = Carbon::now();
        $soonToExpire = $today->copy()->addDays(30);

        // Filter contracts based on view type
        $expiredContractIds = $contracts->filter(function ($contract) use ($today) {
            if (strtolower($contract->status ?? '') === 'expired') {
                return true;
            }
            if ($contract->end_date) {
                return Carbon::parse($contract->end_date)->lt($today);
            }
            return false;
        })->pluck('id')->toArray();

        $expiredContracts = $contracts->filter(function ($contract) use ($expiredContractIds) {
            return in_array($contract->id, $expiredContractIds);
        });

        $soonToExpireContracts = $contracts->filter(function ($contract) use ($today, $soonToExpire, $expiredContractIds) {
            if (in_array($contract->id, $expiredContractIds)) {
                return false;
            }
            if (!$contract->end_date) return false;
            $endDate = Carbon::parse($contract->end_date);
            return $endDate->gte($today) && $endDate->lte($soonToExpire);
        });

        $soonToExpireContractIds = $soonToExpireContracts->pluck('id')->toArray();

        $activeContracts = $contracts->filter(function ($contract) use ($today, $expiredContractIds, $soonToExpireContractIds) {
            $contractStatus = strtolower($contract->status ?? '');
            if ($contractStatus === 'active') {
                return true;
            }
            if ($contractStatus === 'expired') {
                return false;
            }
            if (in_array($contract->id, $expiredContractIds)) {
                return false;
            }
            if (in_array($contract->id, $soonToExpireContractIds)) {
                return false;
            }
            if (
                in_array($contract->approval_stage ?? '', ['line_manager', 'hec', 'procurement'])
                && in_array($contract->status ?? '', ['in_progress', 'pending'])
            ) {
                return false;
            }
            if (!$contract->end_date) return true;
            return Carbon::parse($contract->end_date)->gt($today);
        });

        // Filter pending contracts
        $pendingHecReviewContracts = collect();
        if ($user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo'])) {
            $pendingHecReviewContracts = $contracts->filter(function ($contract) use ($user) {
                return $contract->approval_stage === 'hec'
                    && $contract->current_approver_id == $user->id
                    && in_array($contract->status, ['in_progress', 'pending']);
            });
        }

        $pendingLineManagerReviewContracts = collect();
        if ($user->hasRole('line-manager')) {
            $pendingLineManagerReviewContracts = $contracts->filter(function ($contract) use ($user) {
                return $contract->approval_stage === 'line_manager'
                    && $contract->current_approver_id == $user->id
                    && in_array($contract->status, ['in_progress', 'pending', 'draft']);
            });
        }

        $pendingProcurementReviewContracts = collect();
        if ($user->hasRole('procurement-officer')) {
            $pendingProcurementReviewContracts = $contracts->filter(function ($contract) use ($user) {
                return $contract->approval_stage === 'procurement'
                    && $contract->current_approver_id == $user->id
                    && in_array($contract->status, ['in_progress', 'pending']);
            });
        }

        // Select contracts based on view type
        $contractsToExport = collect();
        switch ($viewType) {
            case 'active':
                $contractsToExport = $activeContracts;
                break;
            case 'expired':
                $contractsToExport = $expiredContracts;
                break;
            case 'expiring':
                $contractsToExport = $soonToExpireContracts;
                break;
            case 'pending-hec':
                $contractsToExport = $pendingHecReviewContracts;
                break;
            case 'pending-line-manager':
                $contractsToExport = $pendingLineManagerReviewContracts;
                break;
            case 'pending-procurement':
                $contractsToExport = $pendingProcurementReviewContracts;
                break;
            default:
                $contractsToExport = $activeContracts;
        }

        // Generate CSV
        $filename = 'contracts_export_' . $viewType . '_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($contractsToExport) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($file, [
                'Contract Number',
                'Contract Title',
                'Contract Type',
                'Renewal Status',
                'Term Number',
                'Status',
                'Start Date',
                'End Date',
                'Duration (Months)',
                'Contract Value',
                'Currency',
                'Vendor Name',
                'Vendor Contact Person',
                'Vendor Email',
                'Vendor Phone',
                'Vendor Address',
                'Entity',
                'Department',
                'Contract Owner',
                'Created By',
                'Created At',
                'Evaluation Score',
                'Line Manager Rating',
                'HEC Rating'
            ]);

            // Data rows
            foreach ($contractsToExport as $contract) {
                $renewalStatus = 'First Contract';
                $termNumber = '1';
                if ($contract->parent_contract_id) {
                    $renewalStatus = 'Renewal';
                    $termNumber = (string)($contract->renewal_term_number ?? 2);
                } elseif (method_exists(CcbrtContract::class, 'renewals') && $contract->relationLoaded('renewals') && $contract->renewals && $contract->renewals->count() > 0) {
                    $renewalStatus = 'First Contract (Has Renewals)';
                }

                fputcsv($file, [
                    $contract->contract_number ?? 'N/A',
                    $contract->title ?? 'N/A',
                    $contract->contract_type ?? 'N/A',
                    $renewalStatus,
                    $termNumber,
                    ucfirst($contract->status ?? 'N/A'),
                    $contract->start_date ? Carbon::parse($contract->start_date)->format('Y-m-d') : 'N/A',
                    $contract->end_date ? Carbon::parse($contract->end_date)->format('Y-m-d') : 'N/A',
                    $contract->duration_months ?? 'N/A',
                    number_format($contract->cost ?? 0, 2),
                    $contract->currency ?? 'TZS',
                    $contract->vendor->name ?? 'N/A',
                    $contract->vendor->contact_person ?? 'N/A',
                    $contract->vendor->contact_email ?? 'N/A',
                    $contract->vendor->contact_phone ?? 'N/A',
                    $contract->vendor->address ?? 'N/A',
                    $contract->division->name ?? 'N/A',
                    $contract->department->dept_name ?? 'N/A',
                    (method_exists(CcbrtContract::class, 'contractManager') && $contract->relationLoaded('contractManager') && $contract->contractManager) ? trim(($contract->contractManager->fname ?? '') . ' ' . ($contract->contractManager->mname ?? '') . ' ' . ($contract->contractManager->lname ?? '')) : 'N/A',
                    $contract->creator ? trim(($contract->creator->fname ?? '') . ' ' . ($contract->creator->mname ?? '') . ' ' . ($contract->creator->lname ?? '')) : 'N/A',
                    $contract->created_at ? Carbon::parse($contract->created_at)->format('Y-m-d H:i:s') : 'N/A',
                    $contract->evaluation_score ? number_format($contract->evaluation_score, 2) : 'N/A',
                    $contract->line_manager_rating ? number_format($contract->line_manager_rating, 1) : 'N/A',
                    $contract->hec_rating ? number_format($contract->hec_rating, 1) : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
