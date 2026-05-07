<?php

namespace App\Http\Controllers;

use App\Mail\RequisitionSubmittedMail;
use App\Models\JobTitle;
use App\Models\Departments;
use App\Models\Requisition;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Request as FacadesRequest;
use App\Mail\RequisitionCompletedMail;
use Barryvdh\DomPDF\Facade\Pdf;



class RequisitionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // This page is "My Recruitment Requisitions" – only show requisitions
        // initiated by the current user (or where they are the owner), so users
        // can track progress of their own forms only.

        // Basic access check: user must at least be able to access the form or be a line manager/HEC
        $canView = $user->can('access requisitions form') ||
            $user->hasRole('line-manager') ||
            $user->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant']);

        if (!$canView) {
            abort(403, 'Access Denied');
        }

        $requisitions = Requisition::with([
            'user.department',
            'user.roles',
            'jobTitle',
            'workflow.histories' => function ($q) {
                $q->with(['attendedBy.roles', 'forwardedBy.roles'])
                    ->orderBy('created_at', 'asc');
            },
        ])
            ->where(function ($q) use ($user) {
                $q->where('initiator_id', $user->id)
                    ->orWhere('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $isHecMember = $user->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant']);

        return view('requisitions.index', compact('requisitions', 'isHecMember'));
    }

    /**
     * View pending requisitions for approvers (similar to locum requests view)
     */
    public function view(Request $request)
    {
        $user = Auth::user();

        // Get requisitions related to the current user for approval
        // For HR: show both pending and completed items where HR is/was an approver (for reference)
        // For others: show only pending approvals
        if ($user->hasRole('hr')) {
            $requisitions = Requisition::with([
                'user.department',
                'user.roles',
                'jobTitle',
                'workflow.histories' => function ($q) {
                    $q->with(['attendedBy', 'forwardedBy'])
                        ->whereNotNull('requisition_status')
                        ->orderBy('id', 'desc');
                },
            ])
                ->whereHas('workflow.histories', function ($h) use ($user) {
                    $h->where('attended_by', $user->id)
                        ->whereIn('requisition_status', [1, 3, -1]); // pending HR, completed HR
                })
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $requisitions = Requisition::with([
                'user.department',
                'user.roles',
                'jobTitle',
                'workflow.histories' => function ($q) {
                    $q->with(['attendedBy', 'forwardedBy'])
                        ->whereNotNull('requisition_status')
                        ->orderBy('id', 'desc');
                },
            ])
                ->whereHas('workflow', function ($q) use ($user) {
                    $q->where('work_flow_completed', 0)
                        ->whereHas('histories', function ($h) use ($user) {
                            $h->where('attended_by', $user->id)
                                ->whereIn('requisition_status', [0, 1])
                                ->whereNull('decision_date'); // Only show pending items (not yet decided)
                        });
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('requisitions.view', compact('requisitions'));
    }

    public function create()
    {
        $user = auth()->user();

        // Check if user has permission to create requisitions
        // Allow: users with "create new requisition" permission, line managers, or HEC members
        $canCreate = $user->can('create new requisition') ||
            $user->hasRole('line-manager') ||
            $user->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant']);

        if (!$canCreate) {
            abort(403, 'Access Denied');
        }

        $isHecMember = $user->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant']);
        $isLineManager = $user->hasRole('line-manager');

        if ($isHecMember) {
            // For HEC members: get departments mapped to them
            // First, try direct mapping via hec_member_id
            $departmentsByMember = Departments::with('hec')
                ->where('hec_member_id', $user->id)
                ->orderBy('dept_name', 'asc')
                ->get();

            // Also get departments by HEC level/role (fallback)
            // Get user's role and find matching HEC level
            $userRoles = $user->getRoleNames();
            $hecLevels = [];

            // Map roles to HEC levels (including all HEC roles)
            $roleToHecLevel = [
                'coo' => 'COO',
                'cfo' => 'CFO',
                'cms' => 'CMS',
                'crhdo' => 'CRHDO',
                'chief_accountant' => 'CFO', // Chief Accountant maps to CFO level
            ];

            foreach ($userRoles as $role) {
                if (isset($roleToHecLevel[strtolower($role)])) {
                    $hecLevels[] = $roleToHecLevel[strtolower($role)];
                }
            }

            $departmentsByLevel = collect();
            if (!empty($hecLevels)) {
                $departmentsByLevel = Departments::with('hec')
                    ->whereHas('hec', function ($query) use ($hecLevels) {
                        $query->whereIn('hec_level_name', $hecLevels);
                    })
                    ->orderBy('dept_name', 'asc')
                    ->get();
            }

            // Merge both collections and remove duplicates
            $departments = $departmentsByMember->merge($departmentsByLevel)->unique('id')->values();

            // Debug: Log if no departments found
            if ($departments->isEmpty()) {
                \Log::warning("No departments mapped to HEC member", [
                    'user_id' => $user->id,
                    'user_name' => $user->fname . ' ' . $user->lname,
                    'roles' => $userRoles,
                    'departments_by_member' => $departmentsByMember->count(),
                    'departments_by_level' => $departmentsByLevel->count()
                ]);
            }

            $jobTitles = collect();
            $users = collect();
            $lineManagers = collect();
        } else {
            // For regular users and line managers: use their department
            $userDepartment = $user->department;

            // Check if user has a department assigned
            if (!$userDepartment) {
                return redirect()->route('requisitions.index')
                    ->with('error', 'You must have a department assigned to create a requisition. Please contact administrator.');
            }

            // Wrap in collection for consistency with view expectations
            $departments = collect([$userDepartment]);
            $jobTitles = JobTitle::where('deptId', $userDepartment->id)->get();

            // Load users with their contract end dates from users table
            $users = User::where('deptId', $userDepartment->id)
                ->where('status', 'active')
                ->select('id', 'fname', 'mname', 'lname', 'username', 'ending_date')
                ->get()
                ->map(function ($user) {
                    // Get contract end date from users table ending_date field
                    $contractEndDate = null;
                    if ($user->ending_date) {
                        $contractEndDate = is_string($user->ending_date)
                            ? date('Y-m-d', strtotime($user->ending_date))
                            : $user->ending_date->format('Y-m-d');
                    }

                    // Add contract_end_date as an attribute
                    $user->contract_end_date = $contractEndDate;
                    return $user;
                });

            $lineManagers = collect();
        }

        return view('requisitions.create', compact('jobTitles', 'departments', 'users', 'isHecMember', 'isLineManager', 'lineManagers'));
    }

    /**
     * Get users by job title (AJAX endpoint)
     */
    public function getUsersByJobTitle(Request $request)
    {
        $jobTitleId = $request->input('job_title_id');
        $departmentId = auth()->user()->department->id ?? null;

        if (!$jobTitleId) {
            return response()->json(['users' => []]);
        }

        $query = User::where('job_title', $jobTitleId)
            ->where('status', 'active');

        if ($departmentId) {
            $query->where('deptId', $departmentId);
        }

        $users = $query->select('id', 'fname', 'mname', 'lname', 'username', 'ending_date')
            ->orderBy('fname')
            ->get()
            ->map(function ($user) {
                // Get contract end date from users table ending_date field
                $contractEndDate = null;
                if ($user->ending_date) {
                    $contractEndDate = is_string($user->ending_date)
                        ? date('Y-m-d', strtotime($user->ending_date))
                        : $user->ending_date->format('Y-m-d');
                }

                return [
                    'id' => $user->id,
                    'name' => trim($user->fname . ' ' . ($user->mname ?? '') . ' ' . $user->lname),
                    'username' => $user->username,
                    'contract_end_date' => $contractEndDate,
                ];
            });

        return response()->json(['users' => $users]);
    }

    /**
     * Get all staff in department (AJAX endpoint for line managers)
     */
    public function getAllStaffInDepartment(Request $request)
    {
        $user = Auth::user();
        $departmentId = $user->department->id ?? null;

        if (!$departmentId) {
            return response()->json(['users' => []]);
        }

        $users = User::where('deptId', $departmentId)
            ->where('status', 'active')
            ->with('jobTitle:id,job_title')
            ->select('id', 'fname', 'mname', 'lname', 'username', 'job_title', 'ending_date')
            ->orderBy('fname')
            ->get()
            ->map(function ($user) {
                // Get contract end date from users table ending_date field
                $contractEndDate = null;
                if ($user->ending_date) {
                    $contractEndDate = is_string($user->ending_date)
                        ? date('Y-m-d', strtotime($user->ending_date))
                        : $user->ending_date->format('Y-m-d');
                }

                return [
                    'id' => $user->id,
                    'name' => trim($user->fname . ' ' . ($user->mname ?? '') . ' ' . $user->lname),
                    'username' => $user->username,
                    'job_title' => $user->jobTitle->job_title ?? 'N/A',
                    'contract_end_date' => $contractEndDate,
                ];
            });

        return response()->json(['users' => $users]);
    }

    /**
     * Get line managers by department (AJAX endpoint for HEC members)
     * Uses Spatie permissions role() method for better compatibility
     */
    public function getLineManagersByDepartment(Request $request)
    {
        try {
            $departmentId = $request->input('department_id');

            if (!$departmentId) {
                return response()->json(['lineManagers' => []]);
            }

            // Use Spatie's role() method which handles model_type automatically
            // Match the pattern used in other controllers (e.g., ChangeRequestController, ContractsController)
            // Filter to only show line managers whose contracts have expired (for contract renewal)
            $today = now()->format('Y-m-d');

            // Get all line managers in the department
            $allLineManagers = User::role('line-manager')
                ->where('deptId', $departmentId)
                ->with(['contracts' => function ($query) {
                    $query->orderBy('end_date', 'desc');
                }])
                ->get();

            // Filter to only those with expired contracts (no active contracts)
            $lineManagers = $allLineManagers->filter(function ($user) use ($today) {
                // Check if user has any active contracts (end_date >= today)
                $hasActiveContract = $user->contracts()
                    ->where('end_date', '>=', $today)
                    ->where('status', '!=', 'terminated')
                    ->exists();

                // Include if no active contract (meaning contract expired or no contract)
                return !$hasActiveContract;
            })
                ->map(function ($user) use ($today) {
                    $fullName = trim(($user->fname ?? '') . ' ' . ($user->mname ?? '') . ' ' . ($user->lname ?? ''));
                    // Get the most recent expired contract
                    $expiredContract = $user->contracts()
                        ->where('end_date', '<', $today)
                        ->where('status', '!=', 'terminated')
                        ->orderBy('end_date', 'desc')
                        ->first();

                    return [
                        'id' => $user->id,
                        'name' => $fullName,
                        'username' => $user->username ?? '',
                        'employee_id' => $user->employee_id ?? null,
                        'job_title_id' => $user->job_title ?? null, // Include job title ID
                        'contract_end_date' => $expiredContract ? $expiredContract->end_date : null,
                    ];
                })
                ->values(); // Reset keys after filtering

            return response()->json(['lineManagers' => $lineManagers]);
        } catch (\Exception $e) {
            \Log::error('Error fetching line managers by department', [
                'department_id' => $request->input('department_id'),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback: Try using raw query with proper model_type
            try {
                $userModelClass = User::class; // Get the full class name
                $lineManagers = DB::table('users')
                    ->join('model_has_roles', function ($join) use ($userModelClass) {
                        $join->on('users.id', '=', 'model_has_roles.model_id')
                            ->where('model_has_roles.model_type', '=', $userModelClass);
                    })
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('roles.name', 'line-manager')
                    ->where('users.deptId', $departmentId)
                    ->select('users.id', 'users.fname', 'users.mname', 'users.lname', 'users.username', 'users.employee_id')
                    ->orderBy('users.fname')
                    ->orderBy('users.lname')
                    ->get()
                    ->map(function ($user) {
                        $fullName = trim(($user->fname ?? '') . ' ' . ($user->mname ?? '') . ' ' . ($user->lname ?? ''));
                        return [
                            'id' => $user->id,
                            'name' => $fullName,
                            'username' => $user->username ?? '',
                            'employee_id' => $user->employee_id ?? null,
                        ];
                    });

                return response()->json(['lineManagers' => $lineManagers]);
            } catch (\Exception $fallbackError) {
                \Log::error('Fallback query also failed', [
                    'department_id' => $request->input('department_id'),
                    'error' => $fallbackError->getMessage()
                ]);
            }

            return response()->json([
                'lineManagers' => [],
                'error' => 'An error occurred while fetching line managers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job titles by department (AJAX endpoint for HEC members)
     */
    public function getJobTitlesByDepartment(Request $request)
    {
        $departmentId = $request->input('department_id');

        if (!$departmentId) {
            return response()->json(['jobTitles' => []]);
        }

        $jobTitles = JobTitle::where('deptId', $departmentId)
            ->select('id', 'job_title')
            ->orderBy('job_title')
            ->get();

        return response()->json(['jobTitles' => $jobTitles]);
    }

    public function store(Request $request)
    {
        // Check if user has permission to create requisitions
        $user = Auth::user();

        // Allow: users with "create new requisition" permission, line managers, or HEC members
        $canCreate = $user->can('create new requisition') ||
            $user->hasRole('line-manager') ||
            $user->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant']);

        if (!$canCreate) {
            return redirect()->route('requisitions.index')
                ->with('error', 'You do not have permission to perform this action.');
        }

        $isHecMember = $user->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant']);
        $isLineManager = $user->hasRole('line-manager');

        // Validate based on background type
        $rules = [
            'job_description_file' => 'required|file|mimes:pdf|max:2048', // 2MB
            'background' => 'required|in:new_position,replacement,contract_renewal',
            'department' => 'required|exists:departments,id',
            'responsibility_centre' => 'required|string|max:100',
            'reporting_line' => 'required|string|max:100',
            'contract_type' => 'required|in:minimal_1_year,termed_less_1_year,health_volunteer,work_exposure',
            'conditions' => 'required|array|min:1',
            'conditions.*' => 'in:medical_operational,safety_reputational,legal,financial_loss,increase_income',
            'reasoning' => 'required|string|max:1000',
            'required_start_date' => 'required|date',
            'max_monthly_budget' => 'nullable|numeric|min:0|max:9999999999', // Maximum 10 digits
        ];

        // Job title validation based on background and user type
        $background = $request->input('background');

        if (!$isHecMember && !$isLineManager) {
            // Regular users: job_title or new_job_title required
            $rules['job_title'] = 'required_without:new_job_title|exists:job_titles,id';
            $rules['new_job_title'] = 'required_without:job_title|string|max:255';
        } else {
            // For HEC members and Line Managers
            if ($background === 'new_position') {
                // For new position, new_job_title is required
                $rules['new_job_title'] = 'required|string|max:255';
                $rules['job_title'] = 'nullable|exists:job_titles,id';
            } else {
                // For replacement/contract_renewal, job_title can be null (will be determined from employee/line manager)
                $rules['job_title'] = 'nullable|exists:job_titles,id';
                $rules['new_job_title'] = 'nullable|string|max:255';
            }
        }

        // Add employee validation for contract renewal and replacement
        // For HEC members and Line Managers, validation differs
        if ($request->input('background') == 'contract_renewal') {
            if ($isHecMember) {
                $rules['contract_employee_id'] = 'nullable|exists:users,id';
                $rules['contract_end_date'] = 'nullable|date';
            } elseif ($isLineManager) {
                // Line managers must select employee and provide end date
                $rules['contract_employee_id'] = 'required|exists:users,id';
                $rules['contract_end_date'] = 'required|date';
            } else {
                $rules['contract_employee_id'] = 'required|exists:users,id';
                $rules['contract_end_date'] = 'required|date';
            }
        } elseif ($request->input('background') == 'replacement') {
            if ($isHecMember) {
                $rules['replacement_employee_id'] = 'nullable|exists:users,id';
            } elseif ($isLineManager) {
                // Line managers don't need replacement employee (they only do contract renewal)
                $rules['replacement_employee_id'] = 'nullable|exists:users,id';
            } else {
                $rules['replacement_employee_id'] = 'required|exists:users,id';
            }
        }

        $request->validate($rules);

        // For HEC members, validate line manager selection
        $requisitionUserId = $user->id; // Default to current user
        $jobTitleId = $request->input('job_title');

        if ($isHecMember) {
            $background = $request->input('background');

            // For new position, line_manager is not required
            if ($background !== 'new_position') {
                $request->validate([
                    'line_manager' => 'required|exists:users,id',
                ]);
                // Use the selected line manager as the requisition user
                $requisitionUserId = $request->input('line_manager');

                // Get job_title_id from line manager if not provided in form
                if (!$jobTitleId) {
                    $lineManager = User::find($requisitionUserId);
                    if ($lineManager && $lineManager->job_title) {
                        $jobTitleId = $lineManager->job_title;
                    }
                }

                // Validate that we have a job_title_id (either from form or line manager)
                if (!$jobTitleId) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['job_title' => 'Job title is required. The selected line manager must have a job title assigned.']);
                }
            } else {
                // For new position, use new_job_title and set requisitionUserId to current user
                $requisitionUserId = $user->id;
                $newJobTitle = $request->input('new_job_title');
                if (!$newJobTitle) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['new_job_title' => 'Proposed new position name is required for new positions.']);
                }
            }
        }

        $filePath = null;
        if ($request->hasFile('job_description_file')) {
            $filePath = $request->file('job_description_file')->store('requisitions', 'public');
        }

        // Determine employee_id based on background type
        $employeeId = null;
        if ($request->input('background') == 'contract_renewal') {
            $employeeId = $request->input('contract_employee_id');
        } elseif ($request->input('background') == 'replacement') {
            $employeeId = $request->input('replacement_employee_id');
        }

        // For line managers, if employee is selected, get their job title
        if ($isLineManager && $employeeId) {
            $selectedEmployee = User::find($employeeId);
            if ($selectedEmployee && $selectedEmployee->job_title && !$jobTitleId) {
                $jobTitleId = $selectedEmployee->job_title;
            }
        }

        // Validate that all required approvers exist before creating the requisition
        $errors = [];

        // 1. Check for Payroll Accountant (always required)
        $payroll_accountant = User::whereHas('roles', function ($query) {
            $query->where('name', 'payroll_accountant');
        })->first();

        if (!$payroll_accountant) {
            $errors[] = 'No payroll accountant found. Please contact administrator.';
        }

        // 2. Get department to check HEC mapping
        $department = Departments::with('hec')->find($request->input('department'));
        if (!$department) {
            $errors[] = 'Selected department not found.';
        } else {
            // 3. Check HEC mapping for non-finance departments
            $financeDepartments = ['Finance Back Office', 'Finance Planning Analysis'];
            $departmentName = $department->dept_name ?? $department->name ?? '';

            if (in_array($departmentName, $financeDepartments)) {
                // For finance departments, check for Chief Accountant
                $chiefAccountant = User::role('chief_accountant')->first();
                if (!$chiefAccountant) {
                    $errors[] = 'No Chief Accountant found. Please contact administrator.';
                }
            } else {
                // For other departments, check HEC mapping
                if (!$department->hec_id) {
                    $errors[] = 'Department does not have an HEC mapping configured. Please contact administrator.';
                } else {
                    $hec = $department->hec;
                    if (!$hec || !$hec->hec_level_name) {
                        $errors[] = 'Cannot determine HEC level for this department. Please contact administrator.';
                    } else {
                        // Map HEC level to role
                        $roleMapping = [
                            'COO' => 'coo',
                            'CFO' => 'cfo',
                            'CMS' => 'cms',
                            'CRHDO' => 'crhdo',
                        ];
                        $requiredRole = $roleMapping[strtoupper(trim($hec->hec_level_name))] ?? null;

                        if ($requiredRole) {
                            $hecMember = User::role($requiredRole)->first();
                            if (!$hecMember) {
                                $errors[] = "No {$requiredRole} member found for HEC approval. Please contact administrator.";
                            }
                        } else {
                            $errors[] = "Invalid HEC level '{$hec->hec_level_name}' for this department. Please contact administrator.";
                        }
                    }
                }
            }
        }

        // 4. Check for HR (will be needed later in workflow)
        $hrUser = User::role('hr')->first();
        if (!$hrUser) {
            $errors[] = 'No HR user found. Please contact administrator.';
        }

        // 5. For new positions, check for CFO and CEO (will be needed later)
        if ($request->input('background') == 'new_position') {
            $cfo = User::role('cfo')->first();
            if (!$cfo) {
                $errors[] = 'No CFO found. New positions require CFO approval. Please contact administrator.';
            }

            $ceo = User::role('ceo')->first();
            if (!$ceo) {
                $errors[] = 'No CEO found. New positions require CEO approval. Please contact administrator.';
            }
        }

        // If any errors found, return with errors and input
        if (!empty($errors)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['approver_error' => implode(' ', $errors)])
                ->with('error', implode(' ', $errors));
        }

        // Determine initiator info (role & name) at the time of creation
        // Store the actual role key used in the system (e.g. 'coo', 'cfo', 'cms', 'crhdo', 'chief_accountant', 'line-manager')
        $initiatorRole = 'regular';
        if ($isHecMember) {
            // Pick the first matching HEC role from the user's roles
            $hecRoles = ['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant'];
            $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            $matched = array_values(array_intersect($hecRoles, $userRoles));
            $initiatorRole = $matched[0] ?? 'hec_member';
        } elseif ($isLineManager) {
            // Store the actual line manager role key
            $initiatorRole = 'line-manager';
        }

        $initiatorName = trim(($user->fname ?? '') . ' ' . ($user->lname ?? ''));

        $requisition = Requisition::create([
            'user_id' => $requisitionUserId,
            'initiator_id' => $user->id,
            'initiator_role' => $initiatorRole,
            'initiator_name' => $initiatorName,
            'employee_id' => $employeeId,
            'job_title_id' => $jobTitleId, // Use the determined job_title_id
            'deptId' => $request->input('department'),
            'background' => $request->input('background'),
            'contract_end_date' => $request->input('contract_end_date'),
            'new_job_title' => $request->input('new_job_title'), // For new positions
            'responsibility_centre' => $request->input('responsibility_centre'),
            'reporting_line' => $request->input('reporting_line'),
            'contract_type' => $request->input('contract_type'),
            'budget_approved' => $request->boolean('budget_approved', false),
            // On some databases this column is NOT NULL, so default to 0 when nothing was entered
            'max_monthly_budget' => $request->filled('max_monthly_budget') ? $request->input('max_monthly_budget') : 0,
            'funding_available' => $request->boolean('funding_available', false),
            'donor_code' => $request->input('donor_code'),
            'activity_code' => $request->input('activity_code'),
            'required_start_date' => $request->input('required_start_date'),
            'job_description_file' => $filePath,
            'conditions' => $request->input('conditions', []),
            'reasoning' => $request->input('reasoning'),
            'replacement_user' => $request->input('replacement_employee_id'), // For replacement background
        ]);

        $workflow = Workflow::create([
            'user_id' => $requisitionUserId,
            'work_flow_status' => 0,
            'work_flow_completed' => 0,
            'requisition_id' => $requisition->id
        ]);

        WorkFlowHistory::create([
            'work_flow_id' => $workflow->id,
            'forwarded_by' => Auth::id(),
            'attended_by' => $payroll_accountant->id,
            'remark' => 'Initiate Job description',
            'requisition_status' => 0,
        ]);

        Mail::to($payroll_accountant->email)->queue(new RequisitionSubmittedMail($payroll_accountant, $requisition));

        // Determine where the requisition was sent for the success message
        $sentTo = 'Payroll Accountant';
        $sentToName = $payroll_accountant->fname . ' ' . $payroll_accountant->lname;
        $successMessage = 'Requisition submitted successfully and sent to Payroll Accountant for initial review.';

        // If HEC member initiated, customize the message
        if ($isHecMember) {
            $lineManager = User::find($requisitionUserId);
            if ($lineManager) {
                $sentTo = 'Payroll Accountant';
                $successMessage = 'Requisition submitted successfully for Line Manager (' . $lineManager->fname . ' ' . $lineManager->lname . '). The requisition has been sent to Payroll Accountant for initial review.';
            }
        }

        return redirect()->route('requisitions.index')
            ->with('success', $successMessage)
            ->with('sent_to', $sentTo)
            ->with('sent_to_name', $sentToName);
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();

        // First, get the requisition with basic relationships
        $requisition = Requisition::with([
            'user.department.hec',
            'user.department.hecMember',
            'department.hec',
            'department.hecMember',
            'jobTitle',
            'workflow.histories.forwardedBy',
            'workflow.histories.attendedBy',
            'employee.jobTitle',
            'employee.department',
            'employee.employmentType'
        ])->find($id);

        // Load department if deptId exists
        if ($requisition && $requisition->deptId) {
            $requisition->load('department');
        }

        if (!$requisition) {
            abort(404, 'Requisition not found.');
        }

        // Check access: creator can always view, or user must be an approver in the workflow
        // Also allow Line Managers to view completed forms they created or are in their department
        // Also allow HEC members to view requisitions they initiated or are assigned to review
        $isCreator = $requisition->user_id == $user->id;
        $isApprover = false;
        $isLineManager = $user->hasRole('line-manager');
        $isHecMember = $user->hasAnyRole(['coo', 'cms', 'chief_accountant']);
        $canViewAsLineManager = false;
        $canViewAsHecMember = false;

        if ($requisition->workflow) {
            $isApprover = $requisition->workflow->histories()
                ->where('attended_by', $user->id)
                ->exists();

            // Line Manager can view if they created it, or if it's from their department and completed
            if ($isLineManager) {
                $canViewAsLineManager = $isCreator ||
                    ($requisition->workflow->work_flow_completed == 1 &&
                        $requisition->user->deptId == $user->deptId);
            }

            // HEC Member can view if:
            // 1. They initiated the requisition (forwarded_by in first history)
            // 2. They are assigned to review it (attended_by in workflow history)
            // 3. The requisition is from a department they manage
            if ($isHecMember) {
                // Check if HEC member initiated the requisition
                $hecInitiated = false;
                if ($requisition->workflow->histories->count() > 0) {
                    $firstHistory = $requisition->workflow->histories->sortBy('created_at')->first();
                    if ($firstHistory && $firstHistory->forwarded_by == $user->id) {
                        $hecInitiated = true;
                    }
                }

                // Check if HEC member is assigned to review
                $hecAssigned = $requisition->workflow->histories()
                    ->where('attended_by', $user->id)
                    ->exists();

                // Check if requisition is from a department the HEC member manages
                $hecManagesDepartment = false;
                if ($requisition->user && $requisition->user->department) {
                    $department = $requisition->user->department;
                    // Check if department has HEC mapping that matches user's role
                    if ($department->hec_id) {
                        $hec = $department->hec;
                        if ($hec && $hec->hec_member_id == $user->id) {
                            $hecManagesDepartment = true;
                        }
                    }
                    // Also check by HEC level mapping
                    if ($department->hec) {
                        $hecLevel = strtoupper(trim($department->hec->hec_level_name ?? ''));
                        $roleMapping = [
                            'COO' => 'coo',
                            'CFO' => 'cfo',
                            'CMS' => 'cms',
                            'CRHDO' => 'crhdo',
                        ];
                        $requiredRole = $roleMapping[$hecLevel] ?? null;
                        if ($requiredRole && $user->hasRole($requiredRole)) {
                            $hecManagesDepartment = true;
                        }
                    }
                }

                $canViewAsHecMember = $hecInitiated || $hecAssigned || $hecManagesDepartment || $isApprover;
            }
        } else {
            // If no workflow yet, HEC members can view if they manage the department
            if ($isHecMember && $requisition->user && $requisition->user->department) {
                $department = $requisition->user->department;
                if ($department->hec_id) {
                    $hec = $department->hec;
                    if ($hec && $hec->hec_member_id == $user->id) {
                        $canViewAsHecMember = true;
                    }
                }
            }
        }

        if (!$isCreator && !$isApprover && !$canViewAsLineManager && !$canViewAsHecMember) {
            abort(403, 'You do not have access to view this requisition.');
        }

        // Get additional data for the view using joins (for backward compatibility with view)
        $requisitionData = Requisition::join('users', 'users.id', '=', 'requisitions.user_id')
            ->leftJoin('workflows', 'workflows.requisition_id', '=', 'requisitions.id')
            ->leftJoin('work_flow_histories', function ($join) use ($user) {
                $join->on('work_flow_histories.work_flow_id', '=', 'workflows.id')
                    ->where('work_flow_histories.attended_by', $user->id);
            })
            ->leftJoin('departments', 'departments.id', '=', 'users.deptId')
            ->leftJoin('job_titles', 'job_titles.id', '=', 'requisitions.job_title_id')
            ->leftJoin('hecs', 'hecs.id', '=', 'departments.hec_id')
            ->where('requisitions.id', $id)
            ->first([
                'requisitions.*',
                'users.*',
                'workflows.*',
                'work_flow_histories.*',
                'job_titles.*',
                'requisitions.id as access_id',
                'requisitions.required_start_date',
                'departments.dept_name',
                'hecs.hec_level_name',
                'work_flow_histories.forwarded_by'
            ]);

        // Use the joined data if available, otherwise use the eager-loaded requisition
        $requisition = $requisitionData ?: $requisition;

        // Get workflow ID for approver queries (before reloading)
        $workflowId = $requisition->workflow ? $requisition->workflow->id : ($requisition->work_flow_id ?? null);

        // Reload workflow with histories if not already loaded (in case join query replaced the object)
        if ($requisition && $workflowId) {
            // Always reload workflow to ensure we have the latest data with relationships
            $requisition->load(['workflow.histories' => function ($q) {
                $q->with(['forwardedBy.roles', 'attendedBy.roles'])->orderBy('created_at', 'asc');
            }]);

            // If workflow still not loaded, load it directly
            if (!$requisition->workflow) {
                $requisition->setRelation('workflow', \App\Models\Workflow::with(['histories' => function ($q) {
                    $q->with(['forwardedBy.roles', 'attendedBy.roles'])->orderBy('created_at', 'asc');
                }])->find($workflowId));
            }
        }
        $deptName = $requisition->dept_name ?? ($requisition->user->department->dept_name ?? null);
        $hecLevelName = $requisition->hec_level_name ?? ($requisition->user->department->hec->hec_level_name ?? null);

        $financeDepartments = ['Finance Back Office', 'Finance Planning Analysis'];
        $approver = null;

        if ($workflowId) {
            if (in_array($deptName, $financeDepartments)) {
                // For finance departments: use chief_accountant
                $approver = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'chief_accountant');
                    })
                    ->where('work_flow_histories.work_flow_id', $workflowId)
                    ->where(function ($query) {
                        $query->where('work_flow_histories.requisition_status', 1)
                            ->orWhere('work_flow_histories.requisition_status', -1);
                    })
                    ->orderBy('work_flow_histories.updated_at', 'desc')
                    ->select('users.*', 'work_flow_histories.updated_at')
                    ->first();
            } else {
                // For other departments: use HEC mapping (same as ICT access form)
                $hecLevelName = strtoupper(trim($requisition->hec_level_name ?? ''));
                $roleMapping = [
                    'COO' => 'coo',
                    'CFO' => 'cfo',
                    'CMS' => 'cms',
                    'CRHDO' => 'crhdo',
                ];

                $requiredRole = $roleMapping[$hecLevelName] ?? null;

                if ($requiredRole) {
                    // First try to find from workflow history
                    $approver = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
                        ->whereHas('roles', function ($query) use ($requiredRole) {
                            $query->where('name', $requiredRole);
                        })
                        ->where('work_flow_histories.work_flow_id', $workflowId)
                        ->where(function ($query) {
                            $query->where('work_flow_histories.requisition_status', 1)
                                ->orWhere('work_flow_histories.requisition_status', -1);
                        })
                        ->orderBy('work_flow_histories.updated_at', 'desc')
                        ->select('users.*', 'work_flow_histories.updated_at')
                        ->first();

                    // If not found in history, try to find a user with the role (for viewing purposes)
                    if (!$approver) {
                        $approver = User::whereHas('roles', function ($query) use ($requiredRole) {
                            $query->where('name', $requiredRole);
                        })->first();
                    }
                }
            }
        } else {
            // If no workflow yet, try to find approver based on department HEC mapping (for viewing)
            if ($hecLevelName) {
                $roleMapping = [
                    'COO' => 'coo',
                    'CFO' => 'cfo',
                    'CMS' => 'cms',
                    'CRHDO' => 'crhdo',
                ];
                $requiredRole = $roleMapping[$hecLevelName] ?? null;

                if ($requiredRole) {
                    $approver = User::whereHas('roles', function ($query) use ($requiredRole) {
                        $query->where('name', $requiredRole);
                    })->first();
                }
            }
        }

        $users = User::role(['coo', 'cfo', 'cms', 'chief_accountant'])->get();
        $roleMapping = [
            'COO' => 'coo',
            // 'CFO' => 'cfo',
            'CMS' => 'cms',
        ];
        //    dd($users);

        // Get other approvers for display (only if workflow exists)
        $payroll_accountant = null;
        $cfoToApprove = null;
        $ceoToApprove = null;
        $hrToApprove = null;

        $payrollHistory = null;
        if ($workflowId) {
            // Get payroll accountant workflow history to access decision_date and signature
            // First, get all payroll accountant users
            $payrollAccountantIds = User::whereHas('roles', function ($query) {
                $query->where('name', 'payroll_accountant');
            })->pluck('id')->toArray();

            if (!empty($payrollAccountantIds)) {
                // Look for any payroll accountant history (pending, processed, or approved)
                // Include status 2 (rejected) as well in case we need to show who reviewed it
                // Prioritize records with decision_date (actual approvals) and order by decision_date desc to get most recent
                $payrollHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflowId)
                    ->whereIn('attended_by', $payrollAccountantIds)
                    ->whereIn('requisition_status', [0, -1, 1, 2])
                    ->with(['attendedBy' => function ($query) {
                        $query->select('id', 'fname', 'lname', 'mname', 'signature', 'email');
                    }])
                    ->orderByRaw('CASE WHEN decision_date IS NOT NULL THEN 0 ELSE 1 END')
                    ->orderBy('decision_date', 'desc')
                    ->orderBy('updated_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($payrollHistory && $payrollHistory->attendedBy) {
                    $payroll_accountant = $payrollHistory->attendedBy;
                    // Ensure we have the full user object with signature
                    if (!$payroll_accountant->signature) {
                        $fullUser = User::select('id', 'fname', 'lname', 'mname', 'signature', 'email', 'updated_at')
                            ->find($payroll_accountant->id);
                        if ($fullUser) {
                            $payroll_accountant = $fullUser;
                        }
                    }
                } else {
                    // Fallback: Get the payroll accountant user directly from workflow history
                    // Include status 2 (rejected) as well
                    $payroll_accountant = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
                        ->whereIn('users.id', $payrollAccountantIds)
                        ->where('work_flow_histories.work_flow_id', $workflowId)
                        ->whereIn('work_flow_histories.requisition_status', [0, -1, 1, 2])
                        ->select(
                            'users.id',
                            'users.fname',
                            'users.lname',
                            'users.mname',
                            'users.signature',
                            'users.email',
                            'users.updated_at',
                            'work_flow_histories.updated_at as history_updated_at',
                            'work_flow_histories.requisition_status',
                            'work_flow_histories.decision_date'
                        )
                        ->orderBy('work_flow_histories.updated_at', 'desc')
                        ->orderBy('work_flow_histories.id', 'desc')
                        ->first();

                    // If found, create a payrollHistory object for consistency
                    // Include status 2 (rejected) as well
                    if ($payroll_accountant) {
                        $payrollHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflowId)
                            ->where('attended_by', $payroll_accountant->id)
                            ->whereIn('requisition_status', [0, -1, 1, 2])
                            ->orderBy('updated_at', 'desc')
                            ->orderBy('id', 'desc')
                            ->first();
                    }
                }
            }

            $cfoToApprove = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'cfo');
                })
                ->where('work_flow_histories.work_flow_id', $workflowId)
                ->where(function ($query) {
                    $query->where('work_flow_histories.requisition_status', 1)
                        ->orWhere('work_flow_histories.requisition_status', -1);
                })
                ->orderBy('work_flow_histories.updated_at', 'asc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();

            $ceoToApprove = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'ceo');
                })
                ->where('work_flow_histories.work_flow_id', $workflowId)
                ->where(function ($query) {
                    $query->where('work_flow_histories.requisition_status', 1)
                        ->orWhere('work_flow_histories.requisition_status', -1);
                })
                ->orderBy('work_flow_histories.updated_at', 'asc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();

            $hrToApprove = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'hr');
                })
                ->where('work_flow_histories.work_flow_id', $workflowId)
                // Remove the status restriction to find any HR involvement
                ->orderBy('work_flow_histories.updated_at', 'asc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();
        }

        // Alternative: If no HR found in workflow history, get any HR user for display purposes
        if (!$hrToApprove) {
            $hrToApprove = User::whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })->first(); // Get first HR user as fallback
        }

        $jobTitles = JobTitle::all();
        $departments = Departments::all();

        // Check if requisition was initiated by an HEC member
        $initiatedByHEC = false;
        $hecInitiator = null;
        $isLineManagerRequester = false;

        // Check workflow for HEC initiation (use workflowId to ensure we get the right workflow)
        $workflow = $requisition->workflow ?? null;

        // If workflow not loaded, try to load it using workflowId
        if (!$workflow && $workflowId) {
            $workflow = \App\Models\Workflow::with(['histories' => function ($q) {
                $q->with(['forwardedBy.roles', 'attendedBy.roles'])->orderBy('created_at', 'asc');
            }])->find($workflowId);
        }

        // Also try to get first history directly from database if workflow still not available
        if (!$workflow && $workflowId) {
            $firstHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflowId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->with(['forwardedBy.roles'])
                ->first();

            if ($firstHistory && $firstHistory->forwarded_by) {
                $initiator = $firstHistory->forwardedBy;
                if (!$initiator) {
                    $initiator = User::with('roles')->find($firstHistory->forwarded_by);
                }

                if ($initiator && $initiator->hasAnyRole(['coo', 'cms'])) {
                    $initiatedByHEC = true;
                    $hecInitiator = $initiator;
                }
            }
        } elseif ($workflow && $workflow->histories && $workflow->histories->count() > 0) {
            // Get the first workflow history (initial submission)
            $firstHistory = $workflow->histories->sortBy('created_at')->first();
            if ($firstHistory) {
                // Try to get initiator from forwardedBy relationship first
                $initiator = $firstHistory->forwardedBy;

                // Fallback to finding by forwarded_by if relationship not loaded
                if (!$initiator && $firstHistory->forwarded_by) {
                    $initiator = User::with('roles')->find($firstHistory->forwarded_by);
                }

                if ($initiator && $initiator->hasAnyRole(['coo', 'cms'])) {
                    $initiatedByHEC = true;
                    $hecInitiator = $initiator;
                }
            }
        }

        // Check if the requisition user (owner) is a line manager
        $requisitionOwner = $requisition->user;
        if ($requisitionOwner) {
            $isLineManagerRequester = $requisitionOwner->hasRole('line-manager');
        }

        return view('requisitions.show', compact('requisition', 'payroll_accountant', 'payrollHistory', 'departments', 'user', 'approver', 'cfoToApprove', 'ceoToApprove', 'hrToApprove', 'jobTitles', 'initiatedByHEC', 'hecInitiator', 'isLineManagerRequester'));
    }

    /**
     * Show a read-only overview of the requisition approval flows for System Settings.
     * This mirrors the style of the locum approval settings page, but is informational only.
     */
    public function flowSettings()
    {
        return view('settings.requisition-flow');
    }

    public function update(Request $request, $id)
    {
        $requisition = Requisition::findOrFail($id);
        $workflow = $requisition->workflow;
        $user = Auth::user();

        // Get role name more reliably
        $role = $user->getRoleNames()->first();
        if (!$role) {
            // Fallback: try to get role from roles relationship
            $userRole = $user->roles->first();
            $role = $userRole ? $userRole->name : null;
        }

        $action = $request->input('action'); // Get the action (approve/reject/return)

        // Find the current user's history that is pending or approved
        $historyToUpdate = $workflow->histories->where('attended_by', $user->id)->where('requisition_status', '>=', 0)->first();

        // Handle rejection for all roles
        if ($action === 'reject') {
            $rejectionReason = $request->input('rejection_reason', 'No reason provided');

            // Update the existing history record instead of creating a new one
            if ($historyToUpdate) {
                $historyToUpdate->update([
                    'requisition_status' => 2, // Rejected status
                    'rejection_reason' => $rejectionReason,
                    'remark' => 'Rejected by ' . ($role ?? 'User'),
                    'decision_date' => now(),
                ]);
            }

            // Update workflow status to rejected
            $workflow->update([
                'work_flow_status' => 2, // Rejected status
                'work_flow_completed' => 1
            ]);

            return redirect()->route('requisitions.view')->with('error', 'Requisition rejected.');
        }

        // Handle return to Line Manager action (for HEC members)
        if ($action === 'return') {
            $returnComment = $request->input('return_comment', 'Returned for review');

            // Get the line manager (requisition owner)
            $lineManager = User::find($requisition->user_id);

            if (!$lineManager) {
                return redirect()->back()->with('error', 'Cannot find line manager for this requisition.');
            }

            // Update current approver's history
            if ($historyToUpdate) {
                $historyToUpdate->update([
                    'requisition_status' => -1, // Processed
                    'remark' => 'Returned to Line Manager: ' . $returnComment,
                    'decision_date' => now(),
                ]);
            }

            // Create workflow history for line manager
            WorkFlowHistory::create([
                'work_flow_id' => $workflow->id,
                'forwarded_by' => $user->id,
                'attended_by' => $lineManager->id,
                'step_name' => 'Returned to Line Manager',
                'remark' => $returnComment,
                'requisition_status' => 0, // Pending review
                'status' => 0,
                'decision_date' => null,
            ]);

            // Send email notification
            Mail::to($lineManager->email)->queue(new RequisitionSubmittedMail($lineManager, $requisition));

            return redirect()->route('requisitions.view')->with('success', 'Requisition returned to Line Manager for review.');
        }

        // Mark current user's history as processed
        if ($historyToUpdate) {
            // For payroll accountant, also set decision_date when they approve
            if ($user->hasRole('payroll_accountant') && $requisition->budget_approved !== null) {
                $historyToUpdate->update([
                    'requisition_status' => -1,
                    'decision_date' => now()
                ]);
            } else {
                $historyToUpdate->update(['requisition_status' => -1]);
            }
        }

        // Continue with normal approval flow for each role - use hasRole for more reliable checking
        // IMPORTANT: Check CFO and CEO BEFORE HEC roles, because CFO/CEO are also in the HEC role list
        if ($user->hasRole('cfo')) {
            $cfoDecision = $request->input('action'); // approve or reject

            if ($cfoDecision === 'reject') {
                // CFO rejected → Send back to line manager (initiator)
                $initiator = User::find($requisition->user_id);

                if (!$initiator) {
                    return redirect()->back()->with('error', 'Cannot find requisition initiator.');
                }

                // Find line manager for the initiator's department
                $lineManager = User::whereHas('roles', function ($query) {
                    $query->where('name', 'line-manager');
                })
                    ->where('deptId', $initiator->deptId)
                    ->first();

                // If no line manager found, send to initiator
                $recipient = $lineManager ?? $initiator;

                // Mark workflow as rejected
                $workflow->update([
                    'work_flow_status' => 2, // Rejected status
                    'work_flow_completed' => 1
                ]);

                // Update CFO history to show rejection
                if ($historyToUpdate) {
                    $historyToUpdate->update([
                        'requisition_status' => 2, // Rejected
                        'decision_date' => now(),
                        'remark' => 'CFO rejected - Requisition returned to line manager',
                        'rejection_reason' => $request->input('cfo_comment', 'CFO rejected the requisition'),
                    ]);
                }

                $requisition->update([
                    'cfo_comment' => $request->input('cfo_comment'),
                    'cfo_financing_code' => $request->input('cfo_financing_code'),
                    'cfo_financing_confirmation' => 'Not confirmed', // ENUM only allows 'Confirmed' or 'Not confirmed'
                ]);

                // Create workflow history for line manager/initiator
                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $recipient->id,
                    'step_name' => 'Returned to Line Manager',
                    'remark' => 'Requisition rejected by CFO - Returned to line manager for review',
                    'requisition_status' => 2, // Rejected status
                    'status' => 2,
                    'decision_date' => now(),
                ]);

                // Send mail to line manager/initiator
                Mail::to($recipient->email)->queue(new RequisitionSubmittedMail($recipient, $requisition));

                return redirect()->route('requisitions.view')->with('error', 'Requisition rejected and returned to line manager.');
            } else {
                // CFO approved → Forward to CEO
                // Mark current CFO history as processed
                if ($historyToUpdate) {
                    $historyToUpdate->update([
                        'requisition_status' => -1,
                        'decision_date' => now(),
                        'remark' => 'CFO approved and forwarded to CEO',
                    ]);
                }

                // Map the input to valid ENUM values ('Confirmed' or 'Not confirmed')
                $financingConfirmation = $request->input('cfo_financing_confirmation');
                // ENUM only allows 'Confirmed' or 'Not confirmed'
                if ($financingConfirmation && in_array($financingConfirmation, ['Confirmed', 'Not confirmed'])) {
                    $financingConfirmationValue = $financingConfirmation;
                } else {
                    // Default to 'Confirmed' if approved
                    $financingConfirmationValue = 'Confirmed';
                }

                $requisition->update([
                    'cfo_comment' => $request->input('cfo_comment'),
                    'cfo_financing_code' => $request->input('cfo_financing_code'),
                    'cfo_financing_confirmation' => $financingConfirmationValue,
                ]);

                // Forward to CEO (after CFO approval)
                $ceo = User::whereHas('roles', function ($query) {
                    $query->where('name', 'ceo');
                })->first();

                if (!$ceo) {
                    return redirect()->back()->with('error', 'No CEO found.');
                }

                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $ceo->id,
                    'step_name' => 'CEO Review',
                    'remark' => 'CFO approved - Waiting for CEO approval',
                    'requisition_status' => 1,
                    'status' => 0,
                    'decision_date' => null,
                ]);

                // Send mail to CEO
                Mail::to($ceo->email)->queue(new RequisitionSubmittedMail($ceo, $requisition));

                return redirect()->route('requisitions.view')->with('success', 'Requisition approved by CFO and forwarded to CEO.');
            }
        } elseif ($user->hasRole('ceo')) {
            $ceoDecision = $request->input('action'); // approve or reject
            $ceoComment = $request->input('ceo_comment');

            if ($ceoDecision === 'reject') {
                // CEO rejected → Send back to line manager (initiator)
                $initiator = User::find($requisition->user_id);

                if (!$initiator) {
                    return redirect()->back()->with('error', 'Cannot find requisition initiator.');
                }

                // Find line manager for the initiator's department
                $lineManager = User::whereHas('roles', function ($query) {
                    $query->where('name', 'line-manager');
                })
                    ->where('deptId', $initiator->deptId)
                    ->first();

                // If no line manager found, send to initiator
                $recipient = $lineManager ?? $initiator;

                // Mark workflow as rejected
                $workflow->update([
                    'work_flow_status' => 2, // Rejected status
                    'work_flow_completed' => 1
                ]);

                // Update CEO history to show rejection
                if ($historyToUpdate) {
                    $historyToUpdate->update([
                        'requisition_status' => 2, // Rejected
                        'decision_date' => now(),
                        'remark' => 'CEO rejected - Requisition returned to line manager',
                        'rejection_reason' => $ceoComment ?? 'CEO rejected the requisition',
                    ]);
                }

                $requisition->update([
                    'ceo_decision' => 'Declined', // Keep for backward compatibility
                    'ceo_comment' => $ceoComment,
                ]);

                // Create workflow history for line manager/initiator
                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $recipient->id,
                    'step_name' => 'Returned to Line Manager',
                    'remark' => 'Requisition rejected by CEO - Returned to line manager for review',
                    'requisition_status' => 2, // Rejected status
                    'status' => 2,
                    'decision_date' => now(),
                ]);

                // Send mail to line manager/initiator
                Mail::to($recipient->email)->queue(new RequisitionSubmittedMail($recipient, $requisition));

                return redirect()->route('requisitions.view')->with('error', 'Requisition rejected by CEO and returned to line manager.');
            } else {
                // CEO approved → Forward to HR for final review
                // Mark current CEO history as processed
                if ($historyToUpdate) {
                    $historyToUpdate->update([
                        'requisition_status' => -1,
                        'decision_date' => now(),
                        'remark' => 'CEO approved and forwarded to HR',
                    ]);
                }

                $requisition->update([
                    'ceo_decision' => 'Approved', // Keep for backward compatibility
                    'ceo_comment' => $ceoComment,
                ]);

                // After CEO approval, forward to HR for final review
                $hrUsers = User::whereHas('roles', function ($query) {
                    $query->where('name', 'hr');
                })->get();

                if ($hrUsers->isEmpty()) {
                    return redirect()->back()->with('error', 'No HR users found.');
                }

                // Don't mark workflow as completed yet - HR needs to approve it
                // Create workflow history for EACH HR user and send mail
                foreach ($hrUsers as $hrUser) {
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $hrUser->id,
                        'step_name' => 'HR Review',
                        'remark' => 'CEO approved - Sent to HR for final review and processing',
                        'requisition_status' => 1, // Pending HR review
                        'status' => 0,
                        'decision_date' => null,
                    ]);

                    // Send mail to each HR user
                    Mail::to($hrUser->email)->queue(new RequisitionSubmittedMail($hrUser, $requisition));
                }

                return redirect()->route('requisitions.view')->with('success', 'Requisition approved by CEO and forwarded to HR.');
            }
        } elseif ($user->hasRole('payroll_accountant')) {
            // Validate input data
            try {
                $validated = $request->validate([
                    'budget_approved' => 'nullable|in:0,1,true,false',
                    'max_monthly_budget' => 'nullable|numeric|min:0|max:999999999999999.99',
                    'funding_available' => 'nullable|in:0,1,true,false',
                    'donor_code' => 'nullable|string|max:50',
                    'activity_code' => 'nullable|string|max:50',
                ], [
                    'max_monthly_budget.numeric' => 'The maximum monthly budget must be a valid number.',
                    'max_monthly_budget.min' => 'The maximum monthly budget cannot be negative.',
                    'max_monthly_budget.max' => 'The maximum monthly budget value is too large. Maximum allowed is 999,999,999,999,999.99',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return redirect()->back()
                    ->withErrors($e->errors())
                    ->withInput()
                    ->with('error', 'Please correct the errors in the form.');
            }

            // Ensure budget allocation data is saved properly
            // Handle boolean values from select fields (they come as strings "1" or "0")
            $budgetApproved = $request->input('budget_approved');
            $fundingAvailable = $request->input('funding_available');

            // Prepare update data with proper type casting
            $maxMonthlyBudget = null;
            if ($request->filled('max_monthly_budget')) {
                $maxMonthlyBudget = $request->input('max_monthly_budget');
                // Ensure it's a valid numeric value
                if (!is_numeric($maxMonthlyBudget)) {
                    return redirect()->back()
                        ->withErrors(['max_monthly_budget' => 'The maximum monthly budget must be a valid number.'])
                        ->withInput()
                        ->with('error', 'Invalid budget value provided.');
                }
                // Convert to float to ensure proper decimal handling
                $maxMonthlyBudget = (float) $maxMonthlyBudget;
            }

            $updateData = [
                'budget_approved' => ($budgetApproved === '1' || $budgetApproved === 1 || $budgetApproved === true),
                'max_monthly_budget' => $maxMonthlyBudget,
                'funding_available' => ($fundingAvailable === '1' || $fundingAvailable === 1 || $fundingAvailable === true),
                'donor_code' => $request->filled('donor_code') ? $request->input('donor_code') : null,
                'activity_code' => $request->filled('activity_code') ? $request->input('activity_code') : null,
            ];

            // Update the requisition with error handling
            try {
                DB::beginTransaction();

                $updated = $requisition->update($updateData);

                if (!$updated) {
                    throw new \Exception('Failed to update requisition budget information.');
                }

                DB::commit();
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();

                // Handle specific database errors
                $errorCode = $e->getCode();
                $errorMessage = $e->getMessage();

                if (strpos($errorMessage, 'Out of range value') !== false || strpos($errorMessage, 'Numeric value out of range') !== false) {
                    return redirect()->back()
                        ->withErrors(['max_monthly_budget' => 'The budget value is too large. Please enter a smaller amount.'])
                        ->withInput()
                        ->with('error', 'The budget value exceeds the maximum allowed limit.');
                } elseif (strpos($errorMessage, 'Data too long') !== false) {
                    return redirect()->back()
                        ->withErrors(['max_monthly_budget' => 'The budget value format is invalid.'])
                        ->withInput()
                        ->with('error', 'Invalid budget value format.');
                } else {
                    \Log::error('Database error updating requisition budget', [
                        'requisition_id' => $requisition->id,
                        'error' => $errorMessage,
                        'update_data' => $updateData
                    ]);

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'An error occurred while saving the budget information. Please try again or contact support if the problem persists.');
                }
            } catch (\Exception $e) {
                DB::rollBack();

                \Log::error('Error updating requisition budget', [
                    'requisition_id' => $requisition->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'An unexpected error occurred. Please try again or contact support if the problem persists.');
            }

            // Refresh the model to ensure we have the latest data
            $requisition->refresh();

            // Update payroll accountant workflow history with decision_date
            if ($historyToUpdate && $user->hasRole('payroll_accountant')) {
                $historyToUpdate->update([
                    'requisition_status' => -1,
                    'decision_date' => now(),
                    'remark' => 'Budget allocation reviewed and approved'
                ]);
            }

            // Get background type
            $isNewPosition = isset($requisition->background) && $requisition->background === 'new_position';
            $isRenewalOrReplacement = in_array($requisition->background, ['contract_renewal', 'replacement']);
            $budgetApprovedValue = $requisition->budget_approved;

            // SIMPLIFIED ROUTING LOGIC:
            // If budget approved → Always go to HEC Member (for all: new position, replacement, renewal)
            // If budget NOT approved → Go to HEC Member (no budget path)

            if ($budgetApprovedValue) {
                // Budget approved: Payroll Accountant → HEC Member → HR
                // Get the HEC member based on department
                $initiator = User::with(['department.hec'])->find($requisition->user_id);

                if (!$initiator || !$initiator->department) {
                    return redirect()->back()->with('error', 'Cannot determine department for the requisition initiator.');
                }

                $department = $initiator->department;

                // Handle special departments (Finance departments go to Chief Accountant)
                $financeDepartments = ['Finance Back Office', 'Finance Planning Analysis'];

                if (in_array($department->name, $financeDepartments)) {
                    $hec_member = User::role('chief_accountant')->first();
                    if (!$hec_member) {
                        return redirect()->back()->with('error', 'No Chief Accountant found.');
                    }
                } else {
                    if (!$department->hec_id) {
                        return redirect()->back()->with('error', 'Department does not have an HEC mapping configured.');
                    }

                    if (!$department->relationLoaded('hec')) {
                        $department->load('hec');
                    }

                    $hec = $department->hec;
                    if (!$hec || !$hec->hec_level_name) {
                        return redirect()->back()->with('error', 'Cannot determine HEC level for this department.');
                    }

                    $approverRoleName = null;
                    switch (strtoupper(trim($hec->hec_level_name))) {
                        case 'COO':
                            $approverRoleName = 'coo';
                            break;
                        case 'CFO':
                            $approverRoleName = 'cfo';
                            break;
                        case 'CMS':
                            $approverRoleName = 'cms';
                            break;
                        case 'CRHDO':
                            $approverRoleName = 'crhdo';
                            break;
                        default:
                            return redirect()->back()->with('error', "No valid HEC level found for this department: {$hec->hec_level_name}");
                    }

                    $hec_member = User::role($approverRoleName)->first();
                    if (!$hec_member) {
                        return redirect()->back()->with('error', "No {$approverRoleName} member found.");
                    }
                }

                // Create workflow history for HEC member
                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $hec_member->id,
                    'step_name' => 'HEC Approval',
                    'remark' => 'Payroll approved - Budget available - Waiting for HEC member approval',
                    'requisition_status' => 1,
                    'status' => 0,
                    'decision_date' => null,
                ]);

                Mail::to($hec_member->email)->queue(new RequisitionSubmittedMail($hec_member, $requisition));

                return redirect()->route('requisitions.view')->with('success', 'Requisition approved and sent to HEC member for review.');
            } else {
                // No budget: Payroll Accountant → HEC Member (no budget path)
                // Get the HEC member based on department
                $initiator = User::with(['department.hec'])->find($requisition->user_id);

                if (!$initiator || !$initiator->department) {
                    return redirect()->back()->with('error', 'Cannot determine department for the requisition initiator.');
                }

                $department = $initiator->department;

                // Handle special departments (Finance departments go to Chief Accountant)
                $financeDepartments = ['Finance Back Office', 'Finance Planning Analysis'];

                if (in_array($department->name, $financeDepartments)) {
                    $hec_member = User::role('chief_accountant')->first();
                    if (!$hec_member) {
                        return redirect()->back()->with('error', 'No Chief Accountant found.');
                    }
                } else {
                    if (!$department->hec_id) {
                        return redirect()->back()->with('error', 'Department does not have an HEC mapping configured.');
                    }

                    if (!$department->relationLoaded('hec')) {
                        $department->load('hec');
                    }

                    $hec = $department->hec;
                    if (!$hec || !$hec->hec_level_name) {
                        return redirect()->back()->with('error', 'Cannot determine HEC level for this department.');
                    }

                    $approverRoleName = null;
                    switch (strtoupper(trim($hec->hec_level_name))) {
                        case 'COO':
                            $approverRoleName = 'coo';
                            break;
                        case 'CFO':
                            $approverRoleName = 'cfo';
                            break;
                        case 'CMS':
                            $approverRoleName = 'cms';
                            break;
                        case 'CRHDO':
                            $approverRoleName = 'crhdo';
                            break;
                        default:
                            return redirect()->back()->with('error', "No valid HEC level found for this department: {$hec->hec_level_name}");
                    }

                    $hec_member = User::role($approverRoleName)->first();
                    if (!$hec_member) {
                        return redirect()->back()->with('error', "No {$approverRoleName} member found.");
                    }
                }

                // Create workflow history for HEC member
                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $hec_member->id,
                    'step_name' => 'HEC Approval',
                    'remark' => 'Payroll approved - No budget available - Waiting for HEC member approval',
                    'requisition_status' => 1,
                    'status' => 0,
                    'decision_date' => null,
                ]);

                Mail::to($hec_member->email)->queue(new RequisitionSubmittedMail($hec_member, $requisition));

                return redirect()->route('requisitions.view')->with('success', 'Requisition approved and sent to HEC member for review (No budget).');
            }

            // OLD CODE BELOW - TO BE REMOVED
            if (false && $budgetApprovedValue && $isRenewalOrReplacement) {
                // Case 1: Budget approved + Renewal/Replacement → Auto-set HEC review to "No objection" → Direct to HR (skip HEC, CFO, CEO)
                // Check if requisition was initiated by HEC member
                $initiatedByHEC = false;
                $hecInitiator = null;
                if ($requisition->workflow && $requisition->workflow->histories) {
                    $firstHistory = $requisition->workflow->histories()->orderBy('id', 'asc')->first();
                    if ($firstHistory && $firstHistory->forwarded_by) {
                        $initiator = User::find($firstHistory->forwarded_by);
                        if ($initiator && $initiator->hasAnyRole(['coo', 'cms', 'chief_accountant'])) {
                            $initiatedByHEC = true;
                            $hecInitiator = $initiator;
                        }
                    }
                }

                // Auto-set HEC review to "No objection" if initiated by HEC
                if ($initiatedByHEC) {
                    try {
                        $requisition->update([
                            'hec_objection' => 'in_budget_no_objection',
                            'hec_member_comment' => 'Automatically approved - Budget confirmed by Payroll Accountant',
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error updating HEC review status', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with workflow but log the error
                    }
                }

                $hrUsers = User::whereHas('roles', function ($query) {
                    $query->where('name', 'hr');
                })->get();

                if ($hrUsers->isEmpty()) {
                    return redirect()->back()->with('error', 'No HR users found.');
                }

                try {
                    foreach ($hrUsers as $hrUser) {
                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $hrUser->id,
                            'step_name' => 'HR Review',
                            'remark' => 'Payroll approved - Budget available for Renewal/Replacement - HEC review auto-approved (No objection) - Sent directly to HR',
                            'requisition_status' => 1, // Pending HR review
                            'status' => 0,
                            'decision_date' => null,
                        ]);

                        Mail::to($hrUser->email)->queue(new RequisitionSubmittedMail($hrUser, $requisition));
                    }
                } catch (\Exception $e) {
                    \Log::error('Error creating workflow history for HR', [
                        'requisition_id' => $requisition->id,
                        'error' => $e->getMessage()
                    ]);

                    return redirect()->back()
                        ->with('error', 'An error occurred while forwarding to HR. Please try again.');
                }

                return redirect()->route('requisitions.view')->with('success', 'Requisition approved and sent directly to HR (Budget available for Renewal/Replacement). HEC review automatically set to "No objection".');
            } elseif ($budgetApprovedValue && $isNewPosition) {
                // Case 2: Budget approved + New Position → Auto-set HEC review to "No objection" → Skip HEC, go to CFO → CEO → HR
                // Check if requisition was initiated by HEC member
                $initiatedByHEC = false;
                $hecInitiator = null;
                if ($requisition->workflow && $requisition->workflow->histories) {
                    $firstHistory = $requisition->workflow->histories()->orderBy('id', 'asc')->first();
                    if ($firstHistory && $firstHistory->forwarded_by) {
                        $initiator = User::find($firstHistory->forwarded_by);
                        if ($initiator && $initiator->hasAnyRole(['coo', 'cms', 'chief_accountant'])) {
                            $initiatedByHEC = true;
                            $hecInitiator = $initiator;
                        }
                    }
                }

                // Auto-set HEC review to "No objection" if initiated by HEC
                if ($initiatedByHEC) {
                    $requisition->update([
                        'hec_objection' => 'in_budget_no_objection',
                        'hec_member_comment' => 'Automatically approved - Budget confirmed by Payroll Accountant',
                    ]);
                }

                $cfo = User::whereHas('roles', function ($query) {
                    $query->where('name', 'cfo');
                })->first();

                if (!$cfo) {
                    return redirect()->back()->with('error', 'No CFO found.');
                }

                try {
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $cfo->id,
                        'step_name' => 'CFO Review',
                        'remark' => 'Payroll approved - Budget available for New Position - HEC review auto-approved (No objection) - Sent to CFO for review (New positions always require CFO/CEO approval)',
                        'requisition_status' => 1,
                        'status' => 0,
                        'decision_date' => null,
                    ]);

                    Mail::to($cfo->email)->queue(new RequisitionSubmittedMail($cfo, $requisition));
                } catch (\Exception $e) {
                    \Log::error('Error creating workflow history for CFO', [
                        'requisition_id' => $requisition->id,
                        'error' => $e->getMessage()
                    ]);

                    return redirect()->back()
                        ->with('error', 'An error occurred while forwarding to CFO. Please try again.');
                }

                return redirect()->route('requisitions.view')->with('success', 'Requisition approved and sent to CFO (New position requires CFO/CEO approval). HEC review automatically set to "No objection".');
            } else {
                // Case 3: Budget NOT approved → Route based on initiator type
                // Check if requisition was initiated by HEC member (from workflow history)
                $initiatedByHEC = false;
                $hecInitiator = null;
                if ($requisition->workflow && $requisition->workflow->histories) {
                    $firstHistory = $requisition->workflow->histories()->orderBy('id', 'asc')->first();
                    if ($firstHistory && $firstHistory->forwarded_by) {
                        $workflowInitiator = User::find($firstHistory->forwarded_by);
                        if ($workflowInitiator && $workflowInitiator->hasAnyRole(['coo', 'cms', 'chief_accountant'])) {
                            $initiatedByHEC = true;
                            $hecInitiator = $workflowInitiator;
                        }
                    }
                }

                // If HEC initiator + no budget → Skip HEC, go to CFO → CEO → HR
                if ($initiatedByHEC) {
                    // Route to CFO first
                    $cfo = User::whereHas('roles', function ($query) {
                        $query->where('name', 'cfo');
                    })->first();

                    if (!$cfo) {
                        return redirect()->back()->with('error', 'No CFO found.');
                    }

                    try {
                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $cfo->id,
                            'step_name' => 'CFO Review',
                            'remark' => 'Payroll approved - No budget - HEC initiator - Sent to CFO for financial review',
                            'requisition_status' => 1,
                            'status' => 0,
                            'decision_date' => null,
                        ]);

                        Mail::to($cfo->email)->queue(new RequisitionSubmittedMail($cfo, $requisition));
                    } catch (\Exception $e) {
                        \Log::error('Error creating workflow history for CFO', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);

                        return redirect()->back()
                            ->with('error', 'An error occurred while forwarding to CFO. Please try again.');
                    }

                    return redirect()->route('requisitions.view')->with('success', 'Requisition approved and sent to CFO for financial review (No budget - HEC initiator).');
                }

                // If Line Manager initiator + no budget → Go to HEC (current logic)
                // Get the user who initiated the requisition with department and HEC info
                $initiator = User::with(['department.hec'])->find($requisition->user_id);

                if (!$initiator || !$initiator->department) {
                    return redirect()->back()->with('error', 'Cannot determine department for the requisition initiator.');
                }

                $department = $initiator->department;

                // Handle special departments (Finance departments go to Chief Accountant)
                $financeDepartments = ['Finance Back Office', 'Finance Planning Analysis'];

                if (in_array($department->name, $financeDepartments)) {
                    // Send to Chief Accountant
                    $hec_member = User::role('chief_accountant')->first();

                    if (!$hec_member) {
                        return redirect()->back()->with('error', 'No Chief Accountant found.');
                    }
                } else {
                    // Get HEC mapping from department (same as ICT access form)
                    if (!$department->hec_id) {
                        return redirect()->back()->with('error', 'Department does not have an HEC mapping configured.');
                    }

                    // Load HEC relationship if not already loaded
                    if (!$department->relationLoaded('hec')) {
                        $department->load('hec');
                    }

                    $hec = $department->hec;

                    if (!$hec || !$hec->hec_level_name) {
                        return redirect()->back()->with('error', 'Cannot determine HEC level for this department.');
                    }

                    // Map HEC level to role (same mapping as ICT access form)
                    $approverRoleName = null;
                    switch (strtoupper(trim($hec->hec_level_name))) {
                        case 'COO':
                            $approverRoleName = 'coo';
                            break;
                        case 'CFO':
                            $approverRoleName = 'cfo';
                            break;
                        case 'CMS':
                            $approverRoleName = 'cms';
                            break;
                        case 'CRHDO':
                            $approverRoleName = 'crhdo';
                            break;
                        default:
                            return redirect()->back()->with('error', "No valid HEC level found for this department: {$hec->hec_level_name}");
                    }

                    // Find the HEC member with the specific role (using role() method like ICT access form)
                    $hec_member = User::role($approverRoleName)->first();

                    if (!$hec_member) {
                        return redirect()->back()->with('error', "No {$approverRoleName} member found.");
                    }
                }

                // Create workflow history for HEC/Chief Accountant
                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $hec_member->id,
                    'step_name' => 'HEC Approval',
                    'remark' => 'Payroll approved - No budget available - Waiting for HEC member approval',
                    'requisition_status' => 1,
                    'status' => 0, // Pending status
                    'decision_date' => null, // Explicitly set to null for pending items
                ]);

                // Send email notification to next approver
                Mail::to($hec_member->email)->queue(new RequisitionSubmittedMail($hec_member, $requisition));
            }

            return redirect()->route('requisitions.view')->with('success', 'Requisition updated successfully.');
        } elseif ($user->hasAnyRole(['coo', 'cms', 'crhdo', 'chief_accountant'])) {
            // HEC Member Approval Logic
            $hecObjection = $request->input('hec_objection');
            $hecJustificationType = $request->input('hec_justification_type'); // 'no_financial_implications' or 'other_alternative_for_money'

            // Handle rejection
            if ($hecObjection === 'reject' || $hecObjection === 'Objection to start' || $hecObjection === 'in_budget_with_objection') {
                $initiator = User::find($requisition->user_id);
                if (!$initiator) {
                    return redirect()->back()->with('error', 'Cannot find requisition initiator.');
                }

                $lineManager = User::whereHas('roles', function ($query) {
                    $query->where('name', 'line-manager');
                })->where('deptId', $initiator->deptId)->first();

                $recipient = $lineManager ?? $initiator;

                $workflow->update([
                    'work_flow_status' => 2,
                    'work_flow_completed' => 1
                ]);

                if ($historyToUpdate) {
                    $historyToUpdate->update([
                        'requisition_status' => 2,
                        'decision_date' => now(),
                        'remark' => 'HEC rejected - Requisition returned to line manager',
                        'rejection_reason' => $request->input('hec_member_comment', 'HEC rejected the requisition'),
                    ]);
                }

                $requisition->update([
                    'hec_objection' => $hecObjection,
                    'hec_member_comment' => $request->input('hec_member_comment'),
                    'hec_justification' => $request->input('hec_justification'),
                    'hec_justification_type' => $hecJustificationType,
                ]);

                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $recipient->id,
                    'step_name' => 'Returned to Line Manager',
                    'remark' => 'Requisition rejected by HEC - Returned to line manager for review',
                    'requisition_status' => 2,
                    'status' => 2,
                    'decision_date' => now(),
                ]);

                Mail::to($recipient->email)->queue(new RequisitionSubmittedMail($recipient, $requisition));

                return redirect()->route('requisitions.view')->with('error', 'Requisition rejected and returned to line manager.');
            }

            // HEC approved - update requisition
            // Approval values: 'in_budget_no_objection' or 'No objection to start'
            $isApproved = in_array($hecObjection, ['in_budget_no_objection', 'No objection to start']);
            
            $requisition->update([
                'hec_objection' => $hecObjection,
                'hec_member_comment' => $request->input('hec_member_comment'),
                'hec_justification' => $request->input('hec_justification'),
                'hec_justification_type' => $hecJustificationType,
                'hec_proposed_funding' => $request->input('hec_proposed_funding'),
                'hec_signature_date' => $request->input('hec_signature_date'),
            ]);

            // Mark current HEC history as processed
            if ($historyToUpdate) {
                $historyToUpdate->update([
                    'requisition_status' => -1,
                    'decision_date' => now(),
                    'remark' => 'HEC member approved and forwarded',
                ]);
            }

            // SIMPLIFIED ROUTING LOGIC:
            // With Budget: HEC → HR
            // No Budget: 
            //   - If HEC provides justification (hec_justification_type is set) → HR
            //   - If HEC approves without justification → CFO → CEO → HR

            $budgetApproved = $requisition->budget_approved;
            $hasJustification = !empty($hecJustificationType);

            if ($budgetApproved) {
                // With Budget: HEC → HR
                $hrUsers = User::whereHas('roles', function ($query) {
                    $query->where('name', 'hr');
                })->get();

                if ($hrUsers->isEmpty()) {
                    return redirect()->back()->with('error', 'No HR users found.');
                }

                foreach ($hrUsers as $hrUser) {
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $hrUser->id,
                        'step_name' => 'HR Review',
                        'remark' => 'HEC approved - Budget available - Sent to HR for final approval and processing',
                        'requisition_status' => 1,
                        'status' => 0,
                        'decision_date' => null,
                    ]);

                    Mail::to($hrUser->email)->queue(new RequisitionSubmittedMail($hrUser, $requisition));
                }

                return redirect()->route('requisitions.view')->with('success', 'Requisition approved by HEC and forwarded to HR.');
            } else {
                // No Budget: Check if justification provided
                if ($hasJustification) {
                    // HEC approved WITH justification → HR
                    $hrUsers = User::whereHas('roles', function ($query) {
                        $query->where('name', 'hr');
                    })->get();

                    if ($hrUsers->isEmpty()) {
                        return redirect()->back()->with('error', 'No HR users found.');
                    }

                    foreach ($hrUsers as $hrUser) {
                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $hrUser->id,
                            'step_name' => 'HR Review',
                            'remark' => 'HEC approved with justification - No budget - Sent to HR for final approval and processing',
                            'requisition_status' => 1,
                            'status' => 0,
                            'decision_date' => null,
                        ]);

                        Mail::to($hrUser->email)->queue(new RequisitionSubmittedMail($hrUser, $requisition));
                    }

                    return redirect()->route('requisitions.view')->with('success', 'Requisition approved by HEC with justification and forwarded to HR.');
                } else {
                    // HEC approved WITHOUT justification → CFO → CEO → HR
                    $cfo = User::whereHas('roles', function ($query) {
                        $query->where('name', 'cfo');
                    })->first();

                    if (!$cfo) {
                        return redirect()->back()->with('error', 'No CFO found.');
                    }

                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $cfo->id,
                        'step_name' => 'CFO Review',
                        'remark' => 'HEC approved without justification - No budget - Sent to CFO for financial review',
                        'requisition_status' => 1,
                        'status' => 0,
                        'decision_date' => null,
                    ]);

                    Mail::to($cfo->email)->queue(new RequisitionSubmittedMail($cfo, $requisition));

                    return redirect()->route('requisitions.view')->with('success', 'Requisition approved by HEC (without justification) and forwarded to CFO.');
                }
            }

            // OLD COMPLEX LOGIC BELOW - TO BE REMOVED
            if (false) {
            $isNewPosition = isset($requisition->background) && $requisition->background === 'new_position';
            $initiator = User::find($requisition->user_id);
            $initiatorRole = $initiator ? $initiator->getRoleNames()->first() : null;
            $initiatorIsHec = $initiatorRole && in_array($initiatorRole, ['coo', 'cms'], true);

            if ($initiatorIsHec) {
                // 3. New / Replacement / Renewal initiated directly by HEC (COO/CMS)
                if ($hecDecisionType === 'Objection to start') {
                    // Treat as rejection back to initiator (HEC) or their line manager if any
                    if (!$initiator) {
                        return redirect()->back()->with('error', 'Cannot find requisition initiator.');
                    }

                    $lineManager = User::whereHas('roles', function ($query) {
                        $query->where('name', 'line-manager');
                    })
                        ->where('deptId', $initiator->deptId)
                        ->first();

                    $recipient = $lineManager ?? $initiator;

                    // Mark workflow as rejected
                    $workflow->update([
                        'work_flow_status' => 2, // Rejected status
                        'work_flow_completed' => 1
                    ]);

                    // Update HEC history to show rejection
                    if ($historyToUpdate) {
                        $historyToUpdate->update([
                            'requisition_status' => 2, // Rejected
                            'decision_date' => now(),
                            'remark' => 'HEC objected - Requisition rejected and returned to initiator/line manager',
                            'rejection_reason' => $request->input('hec_member_comment', 'HEC objected to start recruitment/renewal'),
                        ]);
                    }

                    // Create workflow history for initiator/LM
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $recipient->id,
                        'step_name' => 'Returned to Initiator',
                        'remark' => 'Requisition rejected by HEC initiator - Returned for review',
                        'requisition_status' => 2, // Rejected status
                        'status' => 2,
                        'decision_date' => now(),
                    ]);

                    Mail::to($recipient->email)->queue(new RequisitionSubmittedMail($recipient, $requisition));
                } elseif ($hecDecisionType === 'No objection to start') {
                    // HEC-initiated: Check if replacement/renewal and budget status
                    if (!$isNewPosition) {
                        // Replacement / Renewal initiated by HEC
                        if ($requisition->budget_approved && $requisition->funding_available) {
                            // With budget: HEC → Payroll Accountant → HR
                            $hrUsers = User::whereHas('roles', function ($query) {
                                $query->where('name', 'hr');
                            })->get();

                            if ($hrUsers->isEmpty()) {
                                return redirect()->back()->with('error', 'No HR users found.');
                            }

                            foreach ($hrUsers as $hrUser) {
                                WorkFlowHistory::create([
                                    'work_flow_id' => $workflow->id,
                                    'forwarded_by' => $user->id,
                                    'attended_by' => $hrUser->id,
                                    'step_name' => 'HR Review',
                                    'remark' => 'HEC initiator - Replacement/Renewal with budget - Sent to HR for final approval and processing',
                                    'requisition_status' => 1, // Pending HR review
                                    'status' => 0,
                                    'decision_date' => null,
                                ]);

                                Mail::to($hrUser->email)->queue(new RequisitionSubmittedMail($hrUser, $requisition));
                            }
                        } else {
                            // Without budget: HEC → Payroll Accountant → CFO → CEO → HR
                            $cfo = User::whereHas('roles', function ($query) {
                                $query->where('name', 'cfo');
                            })->first();

                            if (!$cfo) {
                                return redirect()->back()->with('error', 'No CFO found.');
                            }

                            WorkFlowHistory::create([
                                'work_flow_id' => $workflow->id,
                                'forwarded_by' => $user->id,
                                'attended_by' => $cfo->id,
                                'step_name' => 'CFO Review',
                                'remark' => 'HEC initiator - Replacement/Renewal without budget - Sent to CFO for financial review',
                                'requisition_status' => 1,
                                'status' => 0,
                                'decision_date' => null,
                            ]);

                            Mail::to($cfo->email)->queue(new RequisitionSubmittedMail($cfo, $requisition));
                        }
                    } else {
                        // New Position initiated by HEC: Always go to CFO → CEO → HR
                        $cfo = User::whereHas('roles', function ($query) {
                            $query->where('name', 'cfo');
                        })->first();

                        if (!$cfo) {
                            return redirect()->back()->with('error', 'No CFO found.');
                        }

                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $cfo->id,
                            'step_name' => 'CFO Review',
                            'remark' => 'HEC initiator - New position - Sent to CFO for financial review',
                            'requisition_status' => 1,
                            'status' => 0,
                            'decision_date' => null,
                        ]);

                        Mail::to($cfo->email)->queue(new RequisitionSubmittedMail($cfo, $requisition));
                    }
                } else {
                    return redirect()->back()->with('error', 'Please select an option: Objection or No objection.');
                }
            } else {
                // 1 & 2. Initiated by Line Manager (Replacement/Renewal or New Position)

                // Case 1: Budget approved but funds NOT available → go directly to HR (skip CFO/CEO)
                if ($requisition->budget_approved && !$requisition->funding_available) {
                    $hrUsers = User::whereHas('roles', function ($query) {
                        $query->where('name', 'hr');
                    })->get();

                    if ($hrUsers->isEmpty()) {
                        return redirect()->back()->with('error', 'No HR users found.');
                    }

                    foreach ($hrUsers as $hrUser) {
                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $hrUser->id,
                            'step_name' => 'HR Review',
                            'remark' => 'HEC approved - Budget approved but funds not available - Sent to HR for final approval and processing',
                            'requisition_status' => 1, // Pending HR review
                            'status' => 0,
                            'decision_date' => null,
                        ]);

                        Mail::to($hrUser->email)->queue(new RequisitionSubmittedMail($hrUser, $requisition));
                    }
                }
                // Case 2: Budget NOT approved but funds ARE available → check HEC decision
                elseif (!$requisition->budget_approved && $requisition->funding_available) {
                    if ($hecDecisionType === 'Objection to start') {
                        // 3b: Objection → back to Line Manager (initiator) - Rejected
                        if (!$initiator) {
                            return redirect()->back()->with('error', 'Cannot find requisition initiator.');
                        }

                        $lineManager = User::whereHas('roles', function ($query) {
                            $query->where('name', 'line-manager');
                        })
                            ->where('deptId', $initiator->deptId)
                            ->first();

                        $recipient = $lineManager ?? $initiator;

                        $workflow->update([
                            'work_flow_status' => 2, // Rejected status
                            'work_flow_completed' => 1
                        ]);

                        if ($historyToUpdate) {
                            $historyToUpdate->update([
                                'requisition_status' => 2, // Rejected
                                'decision_date' => now(),
                                'remark' => 'HEC objected - Budget not approved but funds available - Requisition rejected and returned to line manager',
                                'rejection_reason' => $request->input('hec_member_comment', 'HEC objected to start recruitment/renewal'),
                            ]);
                        }

                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $recipient->id,
                            'step_name' => 'Returned to Line Manager',
                            'remark' => 'Requisition rejected by HEC - Returned to line manager for review',
                            'requisition_status' => 2, // Rejected status
                            'status' => 2,
                            'decision_date' => now(),
                        ]);

                        Mail::to($recipient->email)->queue(new RequisitionSubmittedMail($recipient, $requisition));
                    } elseif ($hecDecisionType === 'No objection to start') {
                        // 3c: No objection → send to CFO → CEO → HR
                        $cfo = User::whereHas('roles', function ($query) {
                            $query->where('name', 'cfo');
                        })->first();

                        if (!$cfo) {
                            return redirect()->back()->with('error', 'No CFO found.');
                        }

                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $cfo->id,
                            'step_name' => 'CFO Review',
                            'remark' => 'HEC no objection - Budget not approved but funds available - Sent to CFO for financial review',
                            'requisition_status' => 1,
                            'status' => 0,
                            'decision_date' => null,
                        ]);

                        Mail::to($cfo->email)->queue(new RequisitionSubmittedMail($cfo, $requisition));
                    } else {
                        return redirect()->back()->with('error', 'Please select an option: Objection or No objection.');
                    }
                }
                // Case 3: Both budget and funds are available
                elseif ($requisition->budget_approved && $requisition->funding_available) {
                    if (!$isNewPosition) {
                        // Replacement / Renewal with funds & in budget: LM → Payroll → HEC → HR
                        $hrUsers = User::whereHas('roles', function ($query) {
                            $query->where('name', 'hr');
                        })->get();

                        if ($hrUsers->isEmpty()) {
                            return redirect()->back()->with('error', 'No HR users found.');
                        }

                        foreach ($hrUsers as $hrUser) {
                            WorkFlowHistory::create([
                                'work_flow_id' => $workflow->id,
                                'forwarded_by' => $user->id,
                                'attended_by' => $hrUser->id,
                                'step_name' => 'HR Review',
                                'remark' => 'HEC approved with budget and funds - Sent to HR for final approval and processing',
                                'requisition_status' => 1, // Pending HR review
                                'status' => 0,
                                'decision_date' => null,
                            ]);

                            Mail::to($hrUser->email)->queue(new RequisitionSubmittedMail($hrUser, $requisition));
                        }
                    } else {
                        // New Position with funds & in budget: LM → Payroll → HEC → CFO → CEO → HR
                        if ($hecDecisionType === 'Objection to start') {
                            // Objection → back to Line Manager (initiator) - Rejected
                            if (!$initiator) {
                                return redirect()->back()->with('error', 'Cannot find requisition initiator.');
                            }

                            $lineManager = User::whereHas('roles', function ($query) {
                                $query->where('name', 'line-manager');
                            })
                                ->where('deptId', $initiator->deptId)
                                ->first();

                            $recipient = $lineManager ?? $initiator;

                            $workflow->update([
                                'work_flow_status' => 2, // Rejected status
                                'work_flow_completed' => 1
                            ]);

                            if ($historyToUpdate) {
                                $historyToUpdate->update([
                                    'requisition_status' => 2, // Rejected
                                    'decision_date' => now(),
                                    'remark' => 'HEC objected - New position with budget and funds - Requisition rejected and returned to line manager',
                                    'rejection_reason' => $request->input('hec_member_comment', 'HEC objected to start recruitment/renewal'),
                                ]);
                            }

                            WorkFlowHistory::create([
                                'work_flow_id' => $workflow->id,
                                'forwarded_by' => $user->id,
                                'attended_by' => $recipient->id,
                                'step_name' => 'Returned to Line Manager',
                                'remark' => 'Requisition rejected by HEC - Returned to line manager for review',
                                'requisition_status' => 2, // Rejected status
                                'status' => 2,
                                'decision_date' => now(),
                            ]);

                            Mail::to($recipient->email)->queue(new RequisitionSubmittedMail($recipient, $requisition));
                        } elseif ($hecDecisionType === 'No objection to start') {
                            // No objection → send to CFO → CEO → HR
                            $cfo = User::whereHas('roles', function ($query) {
                                $query->where('name', 'cfo');
                            })->first();

                            if (!$cfo) {
                                return redirect()->back()->with('error', 'No CFO found.');
                            }

                            WorkFlowHistory::create([
                                'work_flow_id' => $workflow->id,
                                'forwarded_by' => $user->id,
                                'attended_by' => $cfo->id,
                                'step_name' => 'CFO Review',
                                'remark' => 'HEC no objection - New position with budget and funds - Sent to CFO for financial review',
                                'requisition_status' => 1,
                                'status' => 0,
                                'decision_date' => null,
                            ]);

                            Mail::to($cfo->email)->queue(new RequisitionSubmittedMail($cfo, $requisition));
                        } else {
                            return redirect()->back()->with('error', 'Please select an option: Objection or No objection.');
                        }
                    }
                }
                // Case 4: Neither budget nor funds are available → check HEC decision
                else {
                    // No budget and no funds: use 3b / 3c rules
                    if ($hecDecisionType === 'Objection to start') {
                        // 3b: Objection → back to Line Manager (initiator)
                        if (!$initiator) {
                            return redirect()->back()->with('error', 'Cannot find requisition initiator.');
                        }

                        $lineManager = User::whereHas('roles', function ($query) {
                            $query->where('name', 'line-manager');
                        })
                            ->where('deptId', $initiator->deptId)
                            ->first();

                        $recipient = $lineManager ?? $initiator;

                        $workflow->update([
                            'work_flow_status' => 2, // Rejected status
                            'work_flow_completed' => 1
                        ]);

                        if ($historyToUpdate) {
                            $historyToUpdate->update([
                                'requisition_status' => 2, // Rejected
                                'decision_date' => now(),
                                'remark' => 'HEC objected - No budget and no funds - Requisition rejected and returned to line manager',
                                'rejection_reason' => $request->input('hec_member_comment', 'HEC objected to start recruitment/renewal'),
                            ]);
                        }

                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $recipient->id,
                            'step_name' => 'Returned to Line Manager',
                            'remark' => 'Requisition rejected by HEC - Returned to line manager for review',
                            'requisition_status' => 2, // Rejected status
                            'status' => 2,
                            'decision_date' => now(),
                        ]);

                        Mail::to($recipient->email)->queue(new RequisitionSubmittedMail($recipient, $requisition));
                    } elseif ($hecDecisionType === 'No objection to start') {
                        // 3c: No objection → send to CFO → CEO → HR
                        $cfo = User::whereHas('roles', function ($query) {
                            $query->where('name', 'cfo');
                        })->first();

                        if (!$cfo) {
                            return redirect()->back()->with('error', 'No CFO found.');
                        }

                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $cfo->id,
                            'step_name' => 'CFO Review',
                            'remark' => 'HEC no objection - No budget and no funds - Sent to CFO for financial review',
                            'requisition_status' => 1,
                            'status' => 0,
                            'decision_date' => null,
                        ]);

                        Mail::to($cfo->email)->queue(new RequisitionSubmittedMail($cfo, $requisition));
                    } else {
                        return redirect()->back()->with('error', 'Please select an option: Objection or No objection.');
                    }
                }
            }
            return redirect()->route('requisitions.view')->with('success', 'Requisition updated successfully.');
        } elseif ($user->hasRole('cfo')) {
            $cfoDecision = $request->input('action'); // approve or reject

            if ($cfoDecision === 'reject') {
                // CFO rejected → Send back to line manager (initiator)
                $initiator = User::find($requisition->user_id);

                if (!$initiator) {
                    return redirect()->back()->with('error', 'Cannot find requisition initiator.');
                }

                // Find line manager for the initiator's department
                $lineManager = User::whereHas('roles', function ($query) {
                    $query->where('name', 'line-manager');
                })
                    ->where('deptId', $initiator->deptId)
                    ->first();

                // If no line manager found, send to initiator
                $recipient = $lineManager ?? $initiator;

                // Mark workflow as rejected
                $workflow->update([
                    'work_flow_status' => 2, // Rejected status
                    'work_flow_completed' => 1
                ]);

                // Update CFO history to show rejection
                if ($historyToUpdate) {
                    $historyToUpdate->update([
                        'requisition_status' => 2, // Rejected
                        'decision_date' => now(),
                        'remark' => 'CFO rejected - Requisition returned to line manager',
                        'rejection_reason' => $request->input('cfo_comment', 'CFO rejected the requisition'),
                    ]);
                }

                $requisition->update([
                    'cfo_comment' => $request->input('cfo_comment'),
                    'cfo_financing_code' => $request->input('cfo_financing_code'),
                    'cfo_financing_confirmation' => 'Not confirmed', // ENUM only allows 'Confirmed' or 'Not confirmed'
                ]);

                // Create workflow history for line manager/initiator
                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $recipient->id,
                    'step_name' => 'Returned to Line Manager',
                    'remark' => 'Requisition rejected by CFO - Returned to line manager for review',
                    'requisition_status' => 2, // Rejected status
                    'status' => 2,
                    'decision_date' => now(),
                ]);

                // Send mail to line manager/initiator
                Mail::to($recipient->email)->queue(new RequisitionSubmittedMail($recipient, $requisition));

                return redirect()->route('requisitions.view')->with('error', 'Requisition rejected and returned to line manager.');
            } else {
                // CFO approved → Forward to CEO
                // Mark current CFO history as processed
                if ($historyToUpdate) {
                    $historyToUpdate->update([
                        'requisition_status' => -1,
                        'decision_date' => now(),
                        'remark' => 'CFO approved and forwarded to CEO',
                    ]);
                }

                // Map the input to valid ENUM values ('Confirmed' or 'Not confirmed')
                $financingConfirmation = $request->input('cfo_financing_confirmation');
                // ENUM only allows 'Confirmed' or 'Not confirmed'
                if ($financingConfirmation && in_array($financingConfirmation, ['Confirmed', 'Not confirmed'])) {
                    $financingConfirmationValue = $financingConfirmation;
                } else {
                    // Default to 'Confirmed' if approved
                    $financingConfirmationValue = 'Confirmed';
                }

                $requisition->update([
                    'cfo_comment' => $request->input('cfo_comment'),
                    'cfo_financing_code' => $request->input('cfo_financing_code'),
                    'cfo_financing_confirmation' => $financingConfirmationValue,
                ]);

                // Forward to CEO (after CFO approval)
                $ceo = User::whereHas('roles', function ($query) {
                    $query->where('name', 'ceo');
                })->first();

                if (!$ceo) {
                    return redirect()->back()->with('error', 'No CEO found.');
                }

                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $ceo->id,
                    'step_name' => 'CEO Review',
                    'remark' => 'CFO approved - Waiting for CEO approval',
                    'requisition_status' => 1,
                    'status' => 0,
                    'decision_date' => null,
                ]);

                // Send mail to CEO
                Mail::to($ceo->email)->queue(new RequisitionSubmittedMail($ceo, $requisition));

                return redirect()->route('requisitions.view')->with('success', 'Requisition approved by CFO and forwarded to CEO.');
            }
        } elseif ($user->hasRole('ceo')) {
            $ceoDecision = $request->input('action'); // approve or reject
            $ceoComment = $request->input('ceo_comment');

            if ($ceoDecision === 'reject') {
                // CEO rejected → Send back to line manager (initiator)
                $initiator = User::find($requisition->user_id);

                if (!$initiator) {
                    return redirect()->back()->with('error', 'Cannot find requisition initiator.');
                }

                // Find line manager for the initiator's department
                $lineManager = User::whereHas('roles', function ($query) {
                    $query->where('name', 'line-manager');
                })
                    ->where('deptId', $initiator->deptId)
                    ->first();

                // If no line manager found, send to initiator
                $recipient = $lineManager ?? $initiator;

                // Mark workflow as rejected
                $workflow->update([
                    'work_flow_status' => 2, // Rejected status
                    'work_flow_completed' => 1
                ]);

                // Update CEO history to show rejection
                if ($historyToUpdate) {
                    $historyToUpdate->update([
                        'requisition_status' => 2, // Rejected
                        'decision_date' => now(),
                        'remark' => 'CEO rejected - Requisition returned to line manager',
                        'rejection_reason' => $ceoComment ?? 'CEO rejected the requisition',
                    ]);
                }

                $requisition->update([
                    'ceo_decision' => 'Declined', // Keep for backward compatibility
                    'ceo_comment' => $ceoComment,
                ]);

                // Create workflow history for line manager/initiator
                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $recipient->id,
                    'step_name' => 'Returned to Line Manager',
                    'remark' => 'Requisition rejected by CEO - Returned to line manager for review',
                    'requisition_status' => 2, // Rejected status
                    'status' => 2,
                    'decision_date' => now(),
                ]);

                // Send mail to line manager/initiator
                Mail::to($recipient->email)->queue(new RequisitionSubmittedMail($recipient, $requisition));

                return redirect()->route('requisitions.view')->with('error', 'Requisition rejected by CEO and returned to line manager.');
            } else {
                // CEO approved → Forward to HR for final review
                // Mark current CEO history as processed
                if ($historyToUpdate) {
                    $historyToUpdate->update([
                        'requisition_status' => -1,
                        'decision_date' => now(),
                        'remark' => 'CEO approved and forwarded to HR',
                    ]);
                }

                $requisition->update([
                    'ceo_decision' => 'Approved', // Keep for backward compatibility
                    'ceo_comment' => $ceoComment,
                ]);

                // After CEO approval, forward to HR for final review
                $hrUsers = User::whereHas('roles', function ($query) {
                    $query->where('name', 'hr');
                })->get();

                if ($hrUsers->isEmpty()) {
                    return redirect()->back()->with('error', 'No HR users found.');
                }

                // Don't mark workflow as completed yet - HR needs to approve it
                // Create workflow history for EACH HR user and send mail
                foreach ($hrUsers as $hrUser) {
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $hrUser->id,
                        'step_name' => 'HR Review',
                        'remark' => 'CEO approved - Sent to HR for final review and processing',
                        'requisition_status' => 1, // Pending HR review
                        'status' => 0,
                        'decision_date' => null,
                    ]);

                    // Send mail to each HR user
                    Mail::to($hrUser->email)->queue(new RequisitionSubmittedMail($hrUser, $requisition));
                }

                return redirect()->route('requisitions.view')->with('success', 'Requisition approved by CEO and forwarded to HR.');
            }
            } // End of old complex logic block
        } elseif ($user->hasRole('hr')) {
            // Handle HR action (approve or reject)
            // When HR approves, save everything and mark form as completed
            if ($action === 'approve') {
                // Get HR comments from request
                $hrComments = $request->input('hr_comments', '');

                // HR finalizes/approves the requisition for processing - save all data and complete
                $requisition->update([
                    'hr_comments' => $hrComments,
                    'hr_signature_date' => $request->input('hr_signature_date'),
                    'hr_processed' => true,
                    'hr_processed_at' => now(),
                ]);

                // Ensure workflow is marked as completed
                $workflow->update([
                    'work_flow_status' => 3, // Complete status
                    'work_flow_completed' => 1
                ]);

                // Build remark with HR comments if provided
                $remark = 'HR finalized and approved requisition for processing';
                if (!empty($hrComments)) {
                    $remark .= '. Comments: ' . $hrComments;
                }

                // Update or create workflow history for HR finalization
                // Look for HR history with status 1 (pending) or 3 (completed)
                $hrHistory = $workflow->histories()
                    ->where('attended_by', $user->id)
                    ->whereIn('requisition_status', [1, 3])
                    ->latest()
                    ->first();

                if ($hrHistory) {
                    $hrHistory->update([
                        'requisition_status' => 3,
                        'remark' => $remark,
                        'decision_date' => now(),
                    ]);
                } else {
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $user->id,
                        'remark' => $remark,
                        'requisition_status' => 3,
                        'decision_date' => now(),
                    ]);
                }

                // Notify the original requester that HR has finalized
                $requester = $requisition->user;
                if ($requester) {
                    Mail::to($requester->email)->queue(new RequisitionCompletedMail($requester, $requisition));
                }

                return redirect()->route('requisitions.view')->with('success', 'Requisition finalized and approved by HR.');
            } elseif ($action === 'reject') {
                // HR can reject if needed
                $rejectionReason = $request->input('rejection_reason', 'No reason provided');

                $requisition->update([
                    'hr_comments' => $rejectionReason,
                    'hr_processed' => false,
                ]);

                // Update workflow status to rejected
                $workflow->update([
                    'work_flow_status' => 2, // Rejected status
                    'work_flow_completed' => 1
                ]);

                // Update workflow history
                $hrHistory = $workflow->histories()
                    ->where('attended_by', $user->id)
                    ->latest()
                    ->first();

                if ($hrHistory) {
                    $hrHistory->update([
                        'requisition_status' => 2,
                        'remark' => 'Rejected by HR: ' . $rejectionReason,
                        'rejection_reason' => $rejectionReason,
                        'decision_date' => now(),
                    ]);
                } else {
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $user->id,
                        'remark' => 'Rejected by HR: ' . $rejectionReason,
                        'rejection_reason' => $rejectionReason,
                        'requisition_status' => 2,
                        'decision_date' => now(),
                    ]);
                }

                return redirect()->route('requisitions.view')->with('error', 'Requisition rejected by HR.');
            } else {
                // Fallback: treat any other action as "save & complete" to avoid leaving it pending unintentionally
                // Get HR comments from request
                $hrComments = $request->input('hr_comments', '');

                $requisition->update([
                    'hr_comments' => $hrComments,
                    'hr_signature_date' => $request->input('hr_signature_date'),
                    'hr_processed' => true,
                    'hr_processed_at' => now(),
                ]);

                $workflow->update([
                    'work_flow_status' => 3,
                    'work_flow_completed' => 1,
                ]);

                // Update workflow history with HR comments if provided
                $hrHistory = $workflow->histories()
                    ->where('attended_by', $user->id)
                    ->whereIn('requisition_status', [1, 3])
                    ->latest()
                    ->first();

                if ($hrHistory) {
                    $remark = 'HR finalized and completed requisition';
                    if (!empty($hrComments)) {
                        $remark .= '. Comments: ' . $hrComments;
                    }
                    $hrHistory->update([
                        'requisition_status' => 3,
                        'remark' => $remark,
                        'decision_date' => now(),
                    ]);
                }

                return redirect()->route('requisitions.view')->with('success', 'HR action saved and requisition marked as completed.');
            }
        } else {
            // No matching role found
            return redirect()->back()->with('error', 'Unauthorized role. You do not have permission to approve this requisition.');
        }
    }
    public function edit($id)
    {
        // $requisition = Requisition::findOrFail($id);

        $jobTitles = JobTitle::all();
        $users = User::all();

        // Get the specific department for this requisition
        $requisition = Requisition::with('department')->findOrFail($id);

        return view('requisitions.edit', compact('requisition', 'jobTitles', 'users',));
    }

    public function resubmit(Request $request, $id)
    {
        $requisition = Requisition::findOrFail($id);
        $workflow = $requisition->workflow;

        // Validate that this is a rejected requisition belonging to the current user
        if ($workflow->work_flow_status != 2 || $requisition->user_id != Auth::id()) {
            return redirect()->route('requisitions.index')->with('error', 'You can only resubmit rejected requisitions that belong to you.');
        }

        // Validate the form data (same as store method)
        $validated = $request->validate([
            'job_title' => 'required_if:background,replacement,contract_renewal',
            'new_job_title' => 'required_if:background,new_position',
            'background' => 'required',
            'department' => 'required',
            'responsibility_centre' => 'required|max:100',
            'reporting_line' => 'required|max:100',
            'contract_type' => 'required',
            'conditions' => 'required|array|min:1',
            'reasoning' => 'required|max:1000',
            'job_description_file' => 'required_without:existing_job_description_file|file|mimes:pdf|max:2048', // 2MB - required if no existing file
            'max_monthly_budget' => 'nullable|numeric|min:0|max:9999999999', // Maximum 10 digits
            // Add other validation rules as needed
        ]);

        // Map job_title to job_title_id for database
        $updateData = $validated;
        if (isset($updateData['job_title'])) {
            $updateData['job_title_id'] = $updateData['job_title'];
            unset($updateData['job_title']);
        }
        if (isset($updateData['department'])) {
            $updateData['deptId'] = $updateData['department'];
            unset($updateData['department']);
        }

        // Update the requisition
        $requisition->update($updateData);

        // Reset workflow status to pending
        $payroll_accountant = User::whereHas('roles', function ($query) {
            $query->where('name', 'payroll_accountant');
        })->first();

        if (!$payroll_accountant) {
            return redirect()->back()->with('error', 'No payroll accountant found.');
        }

        $workflow = Workflow::create([
            'user_id' => Auth::id(),
            'work_flow_status' => 0,
            'work_flow_completed' => 0,
            'requisition_id' => $requisition->id
        ]);

        WorkFlowHistory::create([
            'work_flow_id' => $workflow->id,
            'forwarded_by' => Auth::id(),
            'attended_by' => $payroll_accountant->id,
            'remark' => 'Initiate Job description',
            'requisition_status' => 0,
        ]);

        Mail::to($payroll_accountant->email)->queue(new RequisitionSubmittedMail($payroll_accountant, $requisition));

        return redirect()->route('requisitions.index')->with('success', 'Requisition resubmitted successfully.');
    }

    public function downloadPDF($id)
    {
        // Get the requisition with all relationships
        $requisition = Requisition::with(['user', 'department', 'employee', 'jobTitle', 'workflow'])
            ->findOrFail($id);

        // Get workflow ID
        $workflowId = $requisition->workflow ? $requisition->workflow->id : null;

        if (!$workflowId) {
            return redirect()->back()->with('error', 'Workflow not found for this requisition.');
        }

        // Get all approvers (similar to your show method)
        $payroll_accountant = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'payroll_accountant');
            })
            ->where('work_flow_histories.work_flow_id', $workflowId)
            ->where(function ($query) {
                $query->where('work_flow_histories.requisition_status', 0)
                    ->orWhere('work_flow_histories.requisition_status', -1);
            })
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at', 'work_flow_histories.requisition_status')
            ->first();

        $cfoToApprove = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'cfo');
            })
            ->where('work_flow_histories.work_flow_id', $workflowId)
            ->where(function ($query) {
                $query->where('work_flow_histories.requisition_status', 1)
                    ->orWhere('work_flow_histories.requisition_status', -1);
            })
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        $ceoToApprove = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'ceo');
            })
            ->where('work_flow_histories.work_flow_id', $workflowId)
            ->where(function ($query) {
                $query->where('work_flow_histories.requisition_status', 1)
                    ->orWhere('work_flow_histories.requisition_status', -1);
            })
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        $hrToApprove = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })
            ->where('work_flow_histories.work_flow_id', $workflowId)
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        // Get HEC approver (similar to your show method logic)
        $financeDepartments = ['Finance Back Office', 'Finance Planning Analysis'];
        $approver = null;

        if (in_array($requisition->department->dept_name ?? '', $financeDepartments)) {
            $approver = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'chief_accountant');
                })
                ->where('work_flow_histories.work_flow_id', $workflowId)
                ->where(function ($query) {
                    $query->where('work_flow_histories.requisition_status', 1)
                        ->orWhere('work_flow_histories.requisition_status', -1);
                })
                ->orderBy('work_flow_histories.updated_at', 'desc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();
        } else {
            // Use HEC mapping (same as ICT access form)
            $hecLevelName = strtoupper(trim($requisition->hec_level_name ?? ''));
            $roleMapping = [
                'COO' => 'coo',
                'CFO' => 'cfo',
                'CMS' => 'cms',
                'CRHDO' => 'crhdo',
            ];
            $requiredRole = $roleMapping[$hecLevelName] ?? null;

            if ($requiredRole) {
                $approver = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
                    ->whereHas('roles', function ($query) use ($requiredRole) {
                        $query->where('name', $requiredRole);
                    })
                    ->where('work_flow_histories.work_flow_id', $workflowId)
                    ->where(function ($query) {
                        $query->where('work_flow_histories.requisition_status', 1)
                            ->orWhere('work_flow_histories.requisition_status', -1);
                    })
                    ->orderBy('work_flow_histories.updated_at', 'desc')
                    ->select('users.*', 'work_flow_histories.updated_at')
                    ->first();
            }
        }

        // Load the PDF view with all data
        $pdf = PDF::loadView('requisitions.pdf', compact(
            'requisition',
            'payroll_accountant',
            'approver',
            'cfoToApprove',
            'ceoToApprove',
            'hrToApprove'
        ));

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'dpi' => 96
        ]);
        // Download the PDF
        return $pdf->download('requisition-' . $requisition->access_id . '.pdf');
    }


    public function staffByDepartment(Request $request)
    {
        $departmentId = $request->get('department_id');

        $users = \App\Models\User::where('dept_id', $departmentId)
            ->where('status', 'active')
            ->with('jobTitle:id,job_title')
            ->select('id', 'fname', 'mname', 'lname', 'username', 'job_title', 'ending_date')
            ->orderBy('fname')
            ->get()
            ->map(function ($user) {
                // Get contract end date from users table ending_date field
                $contractEndDate = null;
                if ($user->ending_date) {
                    $contractEndDate = is_string($user->ending_date)
                        ? date('Y-m-d', strtotime($user->ending_date))
                        : $user->ending_date->format('Y-m-d');
                }

                return [
                    'id'        => $user->id,
                    'name'      => trim($user->fname . ' ' . ($user->mname ?? '') . ' ' . $user->lname),
                    'username'  => $user->username,
                    'job_title' => optional($user->jobTitle)->job_title,
                    'contract_end_date' => $contractEndDate,
                ];
            });

        return response()->json(['users' => $users]);
    }
}
