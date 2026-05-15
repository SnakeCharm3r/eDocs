<?php

namespace App\Http\Controllers;

use DB;
use Mpdf\Mpdf;
use App\Models\Unit;
use App\Models\User;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Policy;
use App\Models\IDCards;
use App\Models\JobTitle;
use App\Models\Platform;
use LdapRecord\Container;
use App\Models\BankDetail;
use App\Models\Departments;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\WelcomeUserMail;
use App\Models\CcbrtRelation;
use App\Models\ClearanceForm;
use App\Models\HealthDetails;
use App\Models\EmploymentTypes;
use App\Models\LoanDeclaration;
use Illuminate\Validation\Rule;
use App\Mail\AdminResetPassword;
use App\Models\NhifRegistration;
use App\Models\EmployementsTypes;
use App\Models\IctAccessResource;
use App\Models\LanguageKnowledge;
use App\Models\UserFamilyDetails;
use Adldap\Laravel\Facades\Adldap;
use App\Models\UserAdditionalInfo;
use Barryvdh\DomPDF\Facade as PDF;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\FailedLoginAttempt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\Relationship;
use Illuminate\Support\Facades\Request as FacadesRequest;
use App\Models\UserSession;
use Illuminate\Support\Facades\Session;


class AuthController extends Controller
{

    public function login()
    {
        $externalLinks = \App\Models\ExternalSystemLink::getActiveLinks();
        return view('auth.login', compact('externalLinks'));
    }
    //get All user
    public function getAllUser(Request $request)
    {
        $users = User::with('jobTitle', 'department.divisions', 'roles', 'permissions', 'assignedEntity', 'assignedEntities')->get();
        $jobTitles = JobTitle::all();
        $departments = Departments::all();
        $allRoles = \Spatie\Permission\Models\Role::where('name', 'not like', 'finance-officer-%')
            ->pluck('name')
            ->toArray();
        $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->pluck('name')->toArray();

        // Stat counts for dashboard cards
        $totalUsers = $users->count();
        $activeUsers = $users->where('status', 'active')->count();
        $inactiveUsers = $users->whereIn('status', ['inactive', 'pending', 'deactivated'])->count();
        $pendingUsers = $users->where('status', 'pending')->count();

        // Get locked users count
        $lockedUsersCount = User::where('is_locked', true)
            ->where('locked_until', '>', Carbon::now())
            ->count();

        // Get recent failed login attempts (last 24 hours)
        $recentFailedAttempts = FailedLoginAttempt::where('success', false)
            ->where('attempted_at', '>=', Carbon::now()->subDay())
            ->count();

        return view('role-permission.user.index', compact(
            'users', 'jobTitles', 'departments', 'allRoles', 'allPermissions',
            'totalUsers', 'activeUsers', 'inactiveUsers', 'pendingUsers',
            'lockedUsersCount', 'recentFailedAttempts'
        ));
    }

    public function exportUsers()
    {
        $users = User::with('jobTitle', 'department', 'roles', 'permissions', 'employmentType')
            ->orderBy('fname')
            ->get();

        $filename = 'CCBRT_User_Report_' . now()->format('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\UsersExport($users),
            $filename
        );
    }

    // Get failed login attempts log
    public function getFailedLoginLogs(Request $request)
    {
        $perPage = $request->get('per_page', 50);
        $failedAttempts = FailedLoginAttempt::where('success', false)
            ->orderBy('attempted_at', 'desc')
            ->paginate($perPage);

        return response()->json($failedAttempts);
    }

    // Unlock user account
    public function unlockUser($id)
    {
        $user = User::findOrFail($id);

        $user->is_locked = false;
        $user->locked_until = null;
        $user->failed_login_attempts = 0;
        $user->last_failed_login_at = null;
        $user->save();

        Alert::success('Success', 'User account has been unlocked successfully.');
        return redirect()->back();
    }

    // Get user details for quick view modal
    public function getUserDetails($id)
    {
        $user = User::with('jobTitle', 'department', 'roles', 'employmentType')->findOrFail($id);

        // Get failed login attempts for this user
        $failedLoginAttempts = FailedLoginAttempt::where('username', $user->username)
            ->orWhere('email', $user->email)
            ->where('success', false)
            ->orderBy('attempted_at', 'desc')
            ->limit(20)
            ->get();

        $html = view('role-permission.user.details', compact('user', 'failedLoginAttempts'))->render();
        return response($html);
    }

    // Bulk actions for users
    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
            'action' => 'required|in:activate,deactivate,delete'
        ]);

        $ids = $request->ids;
        $action = $request->action;
        $count = 0;

        try {
            switch ($action) {
                case 'activate':
                    User::whereIn('id', $ids)->update(['status' => 'active']);
                    $count = count($ids);
                    $message = "$count user(s) activated successfully.";
                    break;

                case 'deactivate':
                    // Exclude super-admin users from deactivation
                    $users = User::whereIn('id', $ids)->with('roles')->get();
                    $superAdminIds = [];
                    $deactivatableIds = [];

                    foreach ($users as $user) {
                        if ($user->hasRole('super-admin')) {
                            $superAdminIds[] = $user->id;
                        } else {
                            $deactivatableIds[] = $user->id;
                        }
                    }

                    if (!empty($deactivatableIds)) {
                        User::whereIn('id', $deactivatableIds)->update(['status' => 'inactive']);
                        $count = count($deactivatableIds);
                    }

                    if (!empty($superAdminIds)) {
                        $skippedCount = count($superAdminIds);
                        $message = $count > 0
                            ? "$count user(s) deactivated successfully. $skippedCount super-admin user(s) cannot be deactivated."
                            : "$skippedCount super-admin user(s) cannot be deactivated.";
                    } else {
                        $message = "$count user(s) deactivated successfully.";
                    }
                    break;

                case 'delete':
                    // Only delete inactive users
                    $users = User::whereIn('id', $ids)->where('status', 'inactive')->get();
                    foreach ($users as $user) {
                        try {
                            $user->delete();
                            $count++;
                        } catch (QueryException $e) {
                            // Skip users with related records
                            continue;
                        }
                    }
                    $message = "$count user(s) deleted successfully.";
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    public function employee($id)
    {
        $user = User::with('department', 'jobTitle', 'employmentType', 'onCallRates')->findOrFail($id);
        $policies = Policy::orderBy('created_at', 'desc')->get();
        $allOnCallRates = \App\Models\OnCallRate::where('is_active', true)->orderBy('education_level')->get();

        // Check profile completion status for HR to fill in details
        $hasPersonalDetails = $user->place_of_birth && $user->marital_status;
        $hasFamilyDetails = UserFamilyDetails::where('userId', $user->id)->count() >= 1; // At least 1 required
        $hasHealthDetails = HealthDetails::where('userId', $user->id)->whereNotNull('blood_group')->exists();
        $hasLanguageKnowledge = LanguageKnowledge::where('userId', $user->id)->count() > 0;
        $hasCcbrtRelation = CcbrtRelation::where('userId', $user->id)->count() > 0;
        $hasConflictInterest = $user->hr_detail_declare === 'on';

        // Check if workflow already exists
        $existingWorkflow = \App\Models\Workflow::where('hr_form', $user->id)
            ->where('work_flow_completed', 0)
            ->first();

        $profileComplete = $hasPersonalDetails && $hasFamilyDetails && $hasHealthDetails &&
            $hasLanguageKnowledge && $hasConflictInterest; // CCBRT Relation is optional

        return view('employees_details.show', compact(
            'user',
            'policies',
            'hasPersonalDetails',
            'hasFamilyDetails',
            'hasHealthDetails',
            'hasLanguageKnowledge',
            'hasCcbrtRelation',
            'hasConflictInterest',
            'profileComplete',
            'existingWorkflow',
            'allOnCallRates'
        ));
    }

    public function changedept($id)
    {
        // Fetch the user by ID

        $user = User::with('jobTitle')->findOrFail($id);
        //dd($user);
        $policies = Policy::all();
        // Pass the user to the view
        return view('employees_details.show', compact('user', 'policies'));
    }


    public function update(Request $request, $id)
    {
        // Validate the incoming data
        $request->validate([
            'department' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'ccb_code' => 'required|string|max:255',
            'professional_reg_number' => 'required|string|max:255',
        ]);

        // Find the user by ID
        $user = User::findOrFail($id);

        // Update the user details
        $user->update([
            'department' => $request->input('department'),
            'job_title' => $request->input('job_title'),
            'ccb_code' => $request->input('ccb_code'),
            'professional_reg_number' => $request->input('professional_reg_number'),
        ]);

        // Return a JSON response for AJAX
        return response()->json([
            'success' => true,
            'message' => 'User details updated successfully.',
        ]);
    }


    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Only allow deleting inactive users
        if ($user->status !== 'inactive') {
            return redirect('/employees')->with('error', 'Only inactive users can be deleted.');
        }

        try {
            $user->delete();
            return redirect('/employees')->with('success', 'User deleted successfully.');
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') { // Foreign key constraint violation
                return redirect('/employees')->with('error', "Cannot delete user '{$user->name}' because they have related records. Please remove related records first.");
            }

            return redirect('/employees')->with('error', 'An unexpected error occurred while deleting the user.');
        }
    }

    // App\Http\Controllers\AuthController.php

    public function userDetail()
    {

        // Get all users with relations
        $users = User::with(['department.divisions', 'jobTitle', 'employmentType'])->get();

        // Distinct statuses for dynamic columns (e.g., active, inactive, pending, deactivated, etc.)
        $statusColumns = User::select('status')->distinct()->pluck('status')->filter()->values();

        // Overall summary by status
        $statusCounts = User::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Per-department summary by status
        $deptStatusRaw = User::select('deptId', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('deptId', 'status')
            ->get();

        $departments = Departments::all()->keyBy('id');

        // Build per-department structure
        $deptSummaries = [];
        foreach ($deptStatusRaw as $row) {
            $deptId = (int) $row->deptId;
            if (!isset($deptSummaries[$deptId])) {
                $deptSummaries[$deptId] = [
                    'name'   => $departments[$deptId]->dept_name ?? '—',
                    'totals' => [],
                    'sum'    => 0,
                ];
            }
            $keyStatus = $row->status ?? '—';
            $deptSummaries[$deptId]['totals'][$keyStatus] = (int) $row->total;
            $deptSummaries[$deptId]['sum'] += (int) $row->total;
        }

        // Ensure all departments appear even if they currently have zero users
        foreach ($departments as $d) {
            if (!isset($deptSummaries[$d->id])) {
                $deptSummaries[$d->id] = [
                    'name'   => $d->dept_name,
                    'totals' => [],
                    'sum'    => 0,
                ];
            }
        }

        // (Optional) detect mismatches without throwing
        $mismatches = $users->filter(function ($u) {
            return $u->department && $u->jobTitle && ($u->department->id !== $u->jobTitle->deptId);
        });
        if ($mismatches->count() > 0) {
            session()->flash('error', $mismatches->count() . ' user(s) have job titles that do not match their department.');
        }

        // Get all departments for filter dropdown
        $allDepartments = Departments::all();

        // Employment contract type summary
        $contractTypeCounts = User::select('employment_types.employment_type', DB::raw('COUNT(*) as total'))
            ->join('employment_types', 'users.employment_typeId', '=', 'employment_types.id')
            ->groupBy('employment_types.employment_type')
            ->pluck('total', 'employment_type');

        // Employment type breakdown by status (for detailed table)
        $contractTypeByStatus = User::select(
                'employment_types.employment_type',
                'users.status',
                DB::raw('COUNT(*) as total')
            )
            ->join('employment_types', 'users.employment_typeId', '=', 'employment_types.id')
            ->groupBy('employment_types.employment_type', 'users.status')
            ->get();

        $contractSummaries = [];
        foreach ($contractTypeByStatus as $row) {
            $type = $row->employment_type;
            if (!isset($contractSummaries[$type])) {
                $contractSummaries[$type] = ['totals' => [], 'sum' => 0];
            }
            $contractSummaries[$type]['totals'][$row->status] = (int) $row->total;
            $contractSummaries[$type]['sum'] += (int) $row->total;
        }

        // All entities (divisions) for filter dropdown
        $allEntities = \App\Models\Division::where('delete_status', '!=', 1)
            ->orderBy('name')
            ->get();

        // ===== Staff contracts expiring within 90 days =====
        $today = \Carbon\Carbon::today();
        $ninetyDaysOut = $today->copy()->addDays(90);

        $expiringStaff = User::where('status', 'active')
            ->whereNotNull('ending_date')
            ->whereDate('ending_date', '>=', $today)
            ->whereDate('ending_date', '<=', $ninetyDaysOut)
            ->with(['department', 'employmentType'])
            ->orderBy('ending_date', 'asc')
            ->get();

        // Compute days until expiry
        foreach ($expiringStaff as $staff) {
            $staff->days_until_expiry = (int) $today->diffInDays(\Carbon\Carbon::parse($staff->ending_date), false);
        }

        // Check for linked requisitions (renewal or employee_id match)
        if ($expiringStaff->isNotEmpty()) {
            $staffIds = $expiringStaff->pluck('id')->toArray();

            $reqByEmployee = \App\Models\Requisition::whereIn('employee_id', $staffIds)
                ->whereNotIn('status', ['rejected', 'cancelled', 'draft'])
                ->get()
                ->keyBy('employee_id');

            $reqByUser = \App\Models\Requisition::where('position_type', 'renewal')
                ->whereIn('user_id', $staffIds)
                ->whereNotIn('status', ['rejected', 'cancelled', 'draft'])
                ->get()
                ->keyBy('user_id');

            // Notification milestones already sent
            $sentNotifications = \App\Models\StaffContractNotification::whereIn('user_id', $staffIds)
                ->get()
                ->groupBy('user_id');

            foreach ($expiringStaff as $staff) {
                $req = $reqByEmployee[$staff->id] ?? $reqByUser[$staff->id] ?? null;
                $staff->active_requisition_id = $req?->id;
                $staff->active_requisition_access_id = $req?->access_id;
                $staff->requisition_status = $req?->status;
                $staff->requisition_contract_type = $req?->contract_type;
                $staff->requisition_required_start = $req?->required_start_date;
                $staff->sent_milestones = isset($sentNotifications[$staff->id])
                    ? $sentNotifications[$staff->id]->pluck('milestone')->unique()->values()->toArray()
                    : [];
            }
        }

        return view('employees_details.index', [
            'users'               => $users,
            'statusCounts'        => $statusCounts,
            'deptSummaries'       => $deptSummaries,
            'statusColumns'       => $statusColumns,
            'departments'         => $allDepartments,
            'contractTypeCounts'  => $contractTypeCounts,
            'contractSummaries'   => $contractSummaries,
            'entities'            => $allEntities,
            'expiringStaff'       => $expiringStaff,
        ]);
    }

    // Bulk actions for staff
    public function staffBulkAction(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
            'action' => 'required|in:activate,deactivate'
        ]);

        $ids = $request->ids;
        $action = $request->action;
        $count = 0;

        try {
            switch ($action) {
                case 'activate':
                    User::whereIn('id', $ids)->update(['status' => 'active']);
                    $count = count($ids);
                    $message = "$count staff member(s) activated successfully.";
                    break;

                case 'deactivate':
                    User::whereIn('id', $ids)->update(['status' => 'inactive']);
                    $count = count($ids);
                    $message = "$count staff member(s) deactivated successfully.";
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    // Manage professional license for clinical staff
    public function manageLicense(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'professional_reg_number' => 'required|string|max:50',
            'license_valid_until' => 'required|date',
            'license_provider' => 'required|string|max:255',
            'professional_reg_verified' => 'nullable|boolean',
        ]);

        try {
            $user = User::findOrFail($request->user_id);

            // Check if user's department is clinical
            if (!$user->jobTitle || $user->jobTitle->clinical_or_non_clinical !== 'Clinical') {
                return response()->json([
                    'success' => false,
                    'message' => 'This user does not have a clinical job title. Professional license management is only available for clinical staff.'
                ], 400);
            }

            // Update user's professional registration number
            $user->professional_reg_number = $request->professional_reg_number;
            $user->save();

            // Get or create HR workflow for this user
            $workflow = \App\Models\Workflow::where('hr_form', $user->id)->orderBy('id', 'desc')->first();

            if (!$workflow) {
                // Create a new workflow if it doesn't exist
                $workflow = \App\Models\Workflow::create([
                    'user_id' => Auth::id(), // The current user (HR) managing the license
                    'hr_form' => $user->id, // The user whose license is being managed
                    'work_flow_status' => 'pending',
                ]);
            }

            // Get or create HR workflow history
            $hrUser = \App\Models\User::role('hr')->first();
            if (!$hrUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'No HR user found. Please ensure at least one HR user exists in the system.'
                ], 400);
            }

            $workflowHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', $hrUser->id)
                ->orderBy('id', 'desc')
                ->first();

            if (!$workflowHistory) {
                // Create new workflow history
                $workflowHistory = \App\Models\WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'attended_by' => $hrUser->id,
                    'status' => 0,
                    'step_name' => 'HR Verification',
                ]);
            }

            // Update license information
            $workflowHistory->license_provider = $request->license_provider;
            $workflowHistory->license_valid_until = $request->license_valid_until;
            $workflowHistory->professional_reg_verified = $request->has('professional_reg_verified') && $request->professional_reg_verified == '1';
            $workflowHistory->save();

            return response()->json([
                'success' => true,
                'message' => 'Professional license information saved successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error managing professional license: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export staff data to Excel with filters.
     */
    public function exportStaff(Request $request)
    {
        $query = User::with(['department.divisions', 'jobTitle', 'employmentType']);

        // Apply filters
        if ($request->filled('export_status') && $request->export_status !== 'all') {
            $query->where('status', $request->export_status);
        }

        if ($request->filled('entity')) {
            $entityName = $request->entity;
            $query->whereHas('department.divisions', function ($q) use ($entityName) {
                $q->where('name', $entityName);
            });
        }

        if ($request->filled('department')) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('dept_name', $request->department);
            });
        }

        if ($request->filled('contract_type')) {
            $query->whereHas('employmentType', function ($q) use ($request) {
                $q->where('employment_type', $request->contract_type);
            });
        }

        if ($request->filled('expiry_range')) {
            $today = \Carbon\Carbon::today();
            $query->where('status', 'active')->whereNotNull('ending_date');
            switch ($request->expiry_range) {
                case '30':
                    $query->whereDate('ending_date', '>=', $today)->whereDate('ending_date', '<=', $today->copy()->addDays(30));
                    break;
                case '60':
                    $query->whereDate('ending_date', '>', $today->copy()->addDays(30))->whereDate('ending_date', '<=', $today->copy()->addDays(60));
                    break;
                case '90':
                    $query->whereDate('ending_date', '>=', $today)->whereDate('ending_date', '<=', $today->copy()->addDays(90));
                    break;
            }
        }

        $users = $query->orderBy('fname')->get();

        // Build spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ===== Sheet 1: Summary =====
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Summary');

        $summary->setCellValue('A1', 'CCBRT eDocs — Staff Report');
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
        if ($request->filled('department')) $filterList[] = 'Department: ' . $request->department;
        if ($request->filled('contract_type')) $filterList[] = 'Contract Type: ' . $request->contract_type;
        if ($request->filled('expiry_range')) $filterList[] = 'Expiry Range: ≤ ' . $request->expiry_range . ' days';
        if (empty($filterList)) $filterList[] = 'None (All Staff)';
        foreach ($filterList as $f) {
            $summary->setCellValue("A{$row}", $f);
            $row++;
        }

        // Status breakdown
        $row += 1;
        $summary->setCellValue("A{$row}", 'Status Breakdown');
        $summary->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $row++;
        $statusCounts = $users->groupBy('status')->map->count();
        $headerRow = $row;
        $summary->setCellValue("A{$row}", 'Status');
        $summary->setCellValue("B{$row}", 'Count');
        $summary->setCellValue("C{$row}", '%');
        $summary->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '007A33']],
        ]);
        $row++;
        $total = $users->count();
        foreach ($statusCounts as $status => $count) {
            $summary->setCellValue("A{$row}", ucfirst($status));
            $summary->setCellValue("B{$row}", $count);
            $summary->setCellValue("C{$row}", $total > 0 ? round($count / $total * 100, 1) . '%' : '0%');
            $row++;
        }
        $summary->setCellValue("A{$row}", 'Total');
        $summary->setCellValue("B{$row}", $total);
        $summary->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);

        // Contract type breakdown
        $row += 2;
        $summary->setCellValue("A{$row}", 'Contract Type Breakdown');
        $summary->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $row++;
        $summary->setCellValue("A{$row}", 'Contract Type');
        $summary->setCellValue("B{$row}", 'Count');
        $summary->getStyle("A{$row}:B{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '007A33']],
        ]);
        $row++;
        $contractCounts = $users->groupBy(fn($u) => optional($u->employmentType)->employment_type ?? 'Unknown')->map->count()->sortDesc();
        foreach ($contractCounts as $type => $count) {
            $summary->setCellValue("A{$row}", $type);
            $summary->setCellValue("B{$row}", $count);
            $row++;
        }

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $summary->getColumnDimension($col)->setAutoSize(true);
        }

        // ===== Sheet 2: Detailed Records =====
        $detail = $spreadsheet->createSheet();
        $detail->setTitle('Staff Details');

        $headers = ['#', 'Code', 'Full Name', 'Department', 'Entity', 'Job Title', 'Contract Type', 'Contract End', 'Days Left', 'Starting Date', 'Status'];
        $detail->setCellValue('A1', 'CCBRT eDocs — Staff Details Report');
        $detail->setCellValue('A2', 'Generated: ' . now()->format('d M Y, H:i') . ' | Records: ' . $total);
        $detail->mergeCells('A1:K1');
        $detail->mergeCells('A2:K2');
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
        $detail->getStyle("A{$headerRow}:K{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '007A33']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 4;
        $today = \Carbon\Carbon::today();
        foreach ($users as $i => $user) {
            $endDate = $user->ending_date ? \Carbon\Carbon::parse($user->ending_date) : null;
            $daysLeft = $endDate ? (int) $today->diffInDays($endDate, false) : null;
            $entity = optional($user->department)->divisions->first()->name ?? '—';

            $daysLabel = '—';
            if ($daysLeft !== null && $user->status === 'active') {
                if ($daysLeft <= 0) {
                    $daysLabel = 'Expired';
                } else {
                    $months = intdiv($daysLeft, 30);
                    $remainDays = $daysLeft % 30;
                    if ($daysLeft < 30) {
                        $daysLabel = $daysLeft . ' days';
                    } elseif ($remainDays == 0) {
                        $daysLabel = $months . ' month' . ($months > 1 ? 's' : '');
                    } else {
                        $daysLabel = $months . ' month' . ($months > 1 ? 's' : '') . ', ' . $remainDays . ' day' . ($remainDays > 1 ? 's' : '');
                    }
                }
            }

            $detail->setCellValue("A{$row}", $i + 1);
            $detail->setCellValue("B{$row}", $user->ccbrt_code ?? '—');
            $detail->setCellValue("C{$row}", trim($user->fname . ' ' . $user->lname));
            $detail->setCellValue("D{$row}", optional($user->department)->dept_name ?? '—');
            $detail->setCellValue("E{$row}", $entity);
            $detail->setCellValue("F{$row}", optional($user->jobTitle)->job_title ?? '—');
            $detail->setCellValue("G{$row}", optional($user->employmentType)->employment_type ?? '—');
            $detail->setCellValue("H{$row}", $endDate ? $endDate->format('d M Y') : '—');
            $detail->setCellValue("I{$row}", $daysLabel);
            $detail->setCellValue("J{$row}", $user->starting_date ? \Carbon\Carbon::parse($user->starting_date)->format('d M Y') : '—');
            $detail->setCellValue("K{$row}", ucfirst($user->status ?? '—'));

            // Alternating row colors
            if ($row % 2 === 0) {
                $detail->getStyle("A{$row}:K{$row}")->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
            }
            $row++;
        }

        // Borders
        $lastRow = $row - 1;
        if ($lastRow >= $headerRow) {
            $detail->getStyle("A{$headerRow}:K{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            ]);
        }

        foreach (range('A', 'K') as $col) {
            $detail->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        $filename = 'Staff_Report_' . now()->format('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ];

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, $headers);
    }


    // public function userDetail()
    // {
    //     $users = User::with('department', 'jobTitle')->get();

    //     //dd($users);
    //     foreach ($users as $user) {
    //         if ($user->department->id !== $user->jobTitle->deptId) {
    //             throw new \Exception("User '{$user->fname} {$user->lname}' job title does not match their department.");
    //         }
    //     }

    //     return view('employees_details.index', compact('users'));
    // }

    public function getUserById($id)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect()->back()->withErrors(['user_not_found' => 'User not found']);
        }
        return view('user.show', compact('user'));
    }


    public function handleLogin(Request $request)
    {
        $wantsJson = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            if ($wantsJson) {
                return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()->toArray()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Accept either username OR email in the same input field
        $login = trim((string) $request->input('username'));
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Check if user exists and is locked
        $user = User::where($loginField, $login)->first();

        if ($user && $user->is_locked && $user->locked_until) {
            if (Carbon::now()->lt($user->locked_until)) {
                $minutesRemaining = Carbon::now()->diffInMinutes($user->locked_until);
                $hoursRemaining = floor($minutesRemaining / 60);
                $minsRemaining = $minutesRemaining % 60;

                $timeMessage = $hoursRemaining > 0
                    ? "{$hoursRemaining} hour(s) and {$minsRemaining} minute(s)"
                    : "{$minsRemaining} minute(s)";

                // Log the locked attempt
                FailedLoginAttempt::create([
                    'username' => $login,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'attempted_at' => Carbon::now(),
                    'success' => false,
                    'failure_reason' => 'Account is locked',
                ]);

                $lockMessage = "Your account has been locked due to multiple failed login attempts. Please try again after {$timeMessage}.";
                if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['message' => $lockMessage], 423);
                }
                return redirect()->back()
                    ->withErrors(['login_error' => $lockMessage])
                    ->withInput();
            } else {
                // Lock period expired, reset failed attempts
                $user->is_locked = false;
                $user->locked_until = null;
                $user->failed_login_attempts = 0;
                $user->save();
            }
        }

        // Attempt to authenticate using username OR email and password
        if (Auth::attempt([$loginField => $login, 'password' => $request->input('password')])) {
            $user = Auth::user();

            // Reset failed login attempts on successful login
            if ($user->failed_login_attempts > 0 || $user->is_locked) {
                $user->failed_login_attempts = 0;
                $user->is_locked = false;
                $user->locked_until = null;
                $user->last_failed_login_at = null;
                $user->save();
            }

            // Log successful login attempt
            FailedLoginAttempt::create([
                'username' => $login,
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'attempted_at' => Carbon::now(),
                'success' => true,
                'failure_reason' => null,
            ]);

            // Check maintenance mode - only users with manage maintenance mode permission can login during maintenance
            if (\App\Http\Controllers\SettingsController::getMaintenanceModeStatus()) {
                if (!$user->can('manage maintenance mode')) {
                    Auth::logout();
                    if ($wantsJson) {
                        return response()->json(['message' => 'The system is currently under maintenance. Please try again shortly.'], 503);
                    }
                    return redirect()->back()
                        ->with('error', 'The system is currently under maintenance. Please try again shortly.');
                }
            }

            if ($user->status === 'deactivated') {
                Auth::logout();
                if ($wantsJson) {
                    return response()->json(['message' => 'Your account has been deactivated. Please contact support for assistance.'], 403);
                }
                return redirect()->back()
                    ->with('error', 'Your account has been deactivated. Please contact support for assistance.');
            }
            if ($user->status == 'inactive') {
                // Handle session limiting for inactive users too
                $this->manageUserSessions($user, $request);

                if (!$user->place_of_birth || !$user->marital_status) {
                    return redirect()->route('profile.personalDetails');
                }


                $familyData = $user->userFamilyDetails()->count();
                if ($familyData < 1) {
                    return redirect()->route('profile.familyDetails');
                }

                $existingHealthDetail = $user->healthDetails()->first();
                if (!$existingHealthDetail || !$existingHealthDetail->blood_group) {
                    return redirect()->route('profile.healthDetails');
                }

                // Check if languageKnowledge is filled
                $languageCount = $user->languageKnowledge()->count();
                if ($languageCount < 1) {
                    return redirect()->route('profile.languageKnowledge');
                }

                // Check if primary_employer_ccbrt is filled with 'yes' or 'no'
                if ($user->hr_detail_declare !== 'on') {
                    return view('profile.interest');
                }


                // Check if signature is filled - allow HR-added users to access signature even if other details are incomplete
                if (!$user->signature) {
                    return redirect()->route('signature.index');
                }

                if ($user->status == 'inactive') {
                    return redirect()->route('profile.personalDetails');
                }
            }

            // If the user's status is 'pending', redirect to the review dashboard
            elseif ($user->status == 'pending') {
                // Handle session limiting for pending users too
                $this->manageUserSessions($user, $request);
                if ($wantsJson) {
                    return response()->json(['redirect' => route('review-dashboard')]);
                }
                return redirect()->route('review-dashboard')->with('info', 'Your account is pending approval, please wait.');
            } elseif ($user->status == 'active') {
                // Handle session limiting (max 2 concurrent sessions)
                $this->manageUserSessions($user, $request);

                // Get intended URL or default to dashboard
                $intendedUrl = session('url.intended', route('dashboard'));
                session()->forget('url.intended');

                // Redirect directly to dashboard/intended URL so login works reliably on all
                // browsers and computers (no dependency on session surviving an extra redirect).
                if ($wantsJson) {
                    return response()->json(['redirect' => $intendedUrl]);
                }
                return redirect()->to($intendedUrl);
            }
        } else {
            // Failed login attempt
            $failedAttempts = 0;
            $lockDuration = null;

            if ($user) {
                // Prevent locking super-admin users
                if (!$user->hasRole('super-admin')) {
                    // Increment failed login attempts
                    $user->failed_login_attempts = ($user->failed_login_attempts ?? 0) + 1;
                    $user->last_failed_login_at = Carbon::now();

                    // Check if we need to lock the account
                    if ($user->failed_login_attempts >= 3) {
                        // If already locked once (5 min), now lock for 30 minutes
                        if ($user->is_locked && $user->locked_until && Carbon::now()->gte($user->locked_until)) {
                            // Previous lock expired, this is a new violation - lock for 30 minutes
                            $lockDuration = 30; // 30 minutes
                        } elseif (!$user->is_locked) {
                            // First time reaching 3 attempts - lock for 5 minutes
                            $lockDuration = 5; // 5 minutes
                        } else {
                            // Still within lock period, extend it to 30 minutes
                            $lockDuration = 30; // 30 minutes
                        }

                        $user->is_locked = true;
                        $user->locked_until = Carbon::now()->addMinutes($lockDuration);
                    }
                } else {
                    // For super-admin, track attempts but don't lock
                    $user->failed_login_attempts = ($user->failed_login_attempts ?? 0) + 1;
                    $user->last_failed_login_at = Carbon::now();
                }

                $user->save();
                $failedAttempts = $user->failed_login_attempts;
            }

            // Log failed login attempt
            FailedLoginAttempt::create([
                'username' => $login,
                'email' => $user ? $user->email : null,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'attempted_at' => Carbon::now(),
                'success' => false,
                'failure_reason' => 'Invalid username or password',
            ]);

            $errorMessage = 'Invalid username or password';

            if ($user && $user->is_locked && $user->locked_until) {
                $minutesRemaining = Carbon::now()->diffInMinutes($user->locked_until);
                $hoursRemaining = floor($minutesRemaining / 60);
                $minsRemaining = $minutesRemaining % 60;

                $timeMessage = $hoursRemaining > 0
                    ? "{$hoursRemaining} hour(s) and {$minsRemaining} minute(s)"
                    : "{$minsRemaining} minute(s)";

                $errorMessage = "Your account has been locked due to multiple failed login attempts. Please try again after {$timeMessage}.";
            } elseif ($user && $failedAttempts >= 2) {
                $remainingAttempts = 3 - $failedAttempts;
                $errorMessage = "Invalid username or password. {$remainingAttempts} attempt(s) remaining before account lock.";
            } elseif ($user && $user->status === 'inactive' && empty($user->signature)) {
                // User exists but password didn't match and registration is incomplete (e.g. stopped at signature step)
                $errorMessage = 'We found your account but the password did not match. If you did not complete registration (e.g. the signature step), please use "Forgot password" below to set a new password, then log in to complete your registration.';
            }

            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['message' => $errorMessage], 401);
            }
            return redirect()->back()->withErrors(['login_error' => $errorMessage])->withInput();
        }
    }



    public function personalDetails(Request $request)
    {
        $user = auth()->user()->load('department');

        // Check if user's department is clinical
        $isClinicalDepartment = false;
        if ($user->jobTitle) {
            $isClinicalDepartment = $user->jobTitle->clinical_or_non_clinical === 'Clinical';
        }

        // Check if user is existing (has starting_date in the past)
        $isExistingUser = $user->starting_date && \Carbon\Carbon::parse($user->starting_date)->isPast();

        session(['current_step' => 1]);
        return view('profile.personalDetails', compact('user', 'isClinicalDepartment', 'isExistingUser'));
    }

    public function checkCcbrtCode(Request $request)
    {
        $ccbrtCode = $request->input('ccbrt_code');
        $userId = $request->input('user_id');

        if (!$ccbrtCode) {
            return response()->json(['available' => true]);
        }

        // Format code with leading zeros
        if (preg_match('/^CCBRT(\d+)$/', strtoupper($ccbrtCode), $matches)) {
            $number = str_pad($matches[1], 4, '0', STR_PAD_LEFT);
            $ccbrtCode = 'CCBRT' . $number;
        }

        $exists = User::where('ccbrt_code', $ccbrtCode)
            ->where('id', '!=', $userId)
            ->exists();

        return response()->json(['available' => !$exists]);
    }


    public function savePersonalDetails(Request $request)
    {
        // Check if HR is editing another user's profile
        $userId = $request->input('user_id');
        if ($userId && Auth::user()->hasPermissionTo('view staff details')) {
            $user = User::findOrFail($userId);
        } else {
            $user = Auth::user();
        }

        // Check if user's department is clinical
        $isClinicalDepartment = false;
        if ($user->job_title) {
            $jobTitle = \App\Models\JobTitle::find($user->job_title);
            $isClinicalDepartment = $jobTitle && $jobTitle->clinical_or_non_clinical === 'Clinical';
        }

        // Check if user is existing (has starting_date in the past)
        $isExistingUser = $user->starting_date && \Carbon\Carbon::parse($user->starting_date)->isPast();

        $messages = [
            'NIN.unique' => 'Please enter your unique National Identification Number.',
            'ccbrt_code.unique' => 'This CCBRT code is already in use. Please enter a different code.',
            'ccbrt_code.regex' => 'CCBRT Code must start with "CCBRT" followed by 1-4 digits (e.g., CCBRT0126).',
        ];

        $validationRules = [
            'place_of_birth' => 'required|string|max:255',
            'nationality' => 'required|string|max:255',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            // 'marriage_certificate' => 'nullable|file|mimes:pdf|max:5120',
            'divorce_certificate' => 'nullable|file|mimes:pdf|max:5120',
            'gender' => 'required|in:male,female,other',
            'region' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'popular_landmark' => 'required|string|max:255',
            'domicile' => 'required|string|max:255',
            'religion' => 'required|string|max:255',
        ];

        // NIDA validation - only validate format if place of birth is Tanzania
        $placeOfBirth = $request->input('place_of_birth');
        if ($placeOfBirth === 'other') {
            $placeOfBirth = $request->input('other_place_of_birth_input');
        }

        if ($placeOfBirth === 'Tanzania') {
            $validationRules['NIN'] = 'nullable|regex:/^\d{8}-\d{5}-\d{5}-\d{2}$/|unique:users,NIN,' . Auth::id();
        } else {
            $validationRules['NIN'] = 'nullable|string|max:50|unique:users,NIN,' . Auth::id();
        }

        // Make professional_reg_number required if user is in clinical job title
        if ($isClinicalDepartment) {
            $validationRules['professional_reg_number'] = 'required|string|max:50';
        } else {
            $validationRules['professional_reg_number'] = 'nullable|string|max:50';
        }

        // CCBRT Code validation - always optional
        $validationRules['ccbrt_code'] = [
            'nullable',
            'string',
            'regex:/^CCBRT\d{4}$/',
            Rule::unique('users', 'ccbrt_code')->ignore(auth()->id()),
        ];

        $validator = Validator::make($request->all(), array_merge($validationRules, [
            'nssf_no' => 'nullable|string|min:8|max:14',
            'tin_no' => 'nullable|string|min:8|max:15',
            'passport_no' => 'nullable|string|min:8|max:16',
            'employee_cv' => 'nullable|file|mimes:pdf|max:3120',
            'profile_picture' => 'nullable|file|image|mimes:jpeg,jpg,png|max:4096',
            'marriage_certificate' => 'nullable|file|mimes:pdf|max:3120',
            'divorced_certificate' => 'nullable|file|mimes:pdf|max:3120',
            'nida' => 'nullable|file|mimes:pdf|max:2048',
            'driving_license' => 'nullable|file|mimes:pdf|max:2048',
            'transport_id' => 'nullable|file|mimes:pdf|max:2048',
            'voting_id' => 'nullable|file|mimes:pdf|max:2048',
        ]), $messages);

        // dd($request->all());
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }


        $user = Auth::user();
        $religion = $request->input('religion');
        $nationality = $request->input('nationality');
        $placeOfBirth = $request->input('place_of_birth');


        if ($placeOfBirth === 'other') {
            $placeOfBirth = $request->input('other_place_of_birth_input');
        }


        if ($religion === 'Other' && $request->filled('religion_other')) {
            $religion = $request->input('religion_other');
        }


        if ($nationality === 'Other' && $request->filled('other_nationality')) {
            $nationality = $request->input('other_nationality');
        }

        // if ($request->hasFile('marriage_certificate')) {
        //     $marriageCertificatePath = $request->file('marriage_certificate')->store('marriage', 'public');
        //     $user->marriage_certificate = $marriageCertificatePath;
        // }

        if ($request->hasFile('marriage_certificate')) {
            $path = $request->file('marriage_certificate')->store('marriage_certificates', 'public');
            $user->marriage_certificate = $path;
        }



        if ($request->hasFile('divorce_certificate')) {
            $user->divorced_certificate = $request->file('divorce_certificate')->store('divorce', 'public');
        }

        // Handle Education Certificates Upload
        $educationLevels = ['form_4', 'form_6', 'certificate', 'diploma', 'bachelor', 'masters', 'phd'];  // Added 'certificate' here
        foreach ($educationLevels as $level) {
            // Handle certificate upload
            if ($request->hasFile("{$level}_certificate")) {
                $certificatePath = $request->file("{$level}_certificate")->store("certificates/{$level}", 'public');
                $user->update(["{$level}_certificate" => $certificatePath]);
            }

            // Only diploma and above require transcripts
            if (in_array($level, ['diploma', 'bachelor', 'masters', 'phd']) && $request->hasFile("{$level}_transcript")) {
                $transcriptPath = $request->file("{$level}_transcript")->store("transcripts/{$level}", 'public');
                $user->update(["{$level}_transcript" => $transcriptPath]);
            }

            // Handle file uploads dynamically based on user selection
            $documentFields = ['nida', 'driving_license', 'transport_id', 'voting_id'];

            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    $filePath = $request->file($field)->store("documents/{$field}", 'public');
                    $user->update([$field => $filePath]);
                }
            }
        }



        // Update user data
        $user->update([
            'place_of_birth' => $placeOfBirth,
            'nationality' => $nationality,
            'tin_no' => $request->input('tin_no'),
            'marital_status' => $request->input('marital_status'),
            'gender' => $request->input('gender'),
            'region' => $request->input('region'),
            'district' => $request->input('district'),
            'street' => $request->input('street'),
            'box_no' => $request->input('box_no'),
            'plot_no' => $request->input('plot_no'),
            'passport_no' => $request->input('passport_no'),
            'popular_landmark' => $request->input('popular_landmark'),
            'house_no' => $request->input('house_no'),
            'domicile' => $request->input('domicile'),
            'NIN' => $request->input('NIN'),
            'religion' => $religion,
            'professional_reg_number' => $request->input('professional_reg_number'),
            'nssf_no' => $request->input('nssf_no'),
            'nationality' => $request->input('nationality'),
            'passport_no' => $request->input('passport_no'),
            'tin_no' => $request->input('tin_no'),
            'marriage_certificate' => $request->input('marriage_certificate'),
            'education_level' => $request->input('education_level'), // Save education level
        ]);

        // Handle CCBRT Code - format with leading zeros
        if ($request->input('skip_ccbrt_code')) {
            // Skip CCBRT code for new users
            $user->update(['ccbrt_code' => null]);
        } elseif ($request->input('ccbrt_code')) {
            // Format CCBRT code: ensure it's CCBRT followed by 4 digits with leading zeros
            $ccbrtCode = strtoupper($request->input('ccbrt_code'));
            if (preg_match('/^CCBRT(\d+)$/', $ccbrtCode, $matches)) {
                $number = str_pad($matches[1], 4, '0', STR_PAD_LEFT);
                $user->update(['ccbrt_code' => 'CCBRT' . $number]);
            } else {
                $user->update(['ccbrt_code' => $ccbrtCode]);
            }
        }


        // Handle file upload for CV
        if ($request->hasFile('employee_cv')) {
            $cvPath = $request->file('employee_cv')->store('cvs', 'public');
            $user->update(['employee_cv' => $cvPath]);
        }

        if ($request->hasFile('profile_picture')) {
            // Validate and store profile picture
            $request->validate([
                'profile_picture' => 'nullable|file|image|mimes:jpeg,png,jpg|max:5120', // max size 5MB
            ]);

            if ($request->hasFile('marriage_certificate')) {
                $path = $request->file('marriage_certificate')->store('marriage_certificates', 'public');
                $user->marriage_certificate = $path;
            }

            $profilePicPath = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->update(['profile_picture' => $profilePicPath]);
        }
        // dd($user);
        // Redirect based on whether HR is editing
        if ($userId && Auth::user()->hasPermissionTo('view staff details')) {
            return redirect()->route('employees_details.show', $userId)->with('success', 'Personal details updated successfully!');
        }
        return redirect()->route('profile.familyDetails')->with('success', 'Personal details updated successfully!');
    }



    public function familyDetails(Request $request)
    {
        $user = Auth::user();
        session(['current_step' => 2]);
        $familyData = UserFamilyDetails::where('userId', $user->id)->get();
        $existingCount = $familyData->count();
        $nextOfKinExists = $familyData->contains('next_of_kin', true);
        return view('profile.familyDetails', compact('familyData', 'existingCount', 'nextOfKinExists'));
    }

    public function saveFamilyDetails(Request $request)
    {
        // Check if HR is editing another user's profile
        $userId = $request->input('user_id');
        if ($userId && Auth::user()->hasPermissionTo('view staff details')) {
            $user = User::findOrFail($userId);
        } else {
            $user = Auth::user();
        }
        $existingCount = UserFamilyDetails::where('userId', $user->id)->count();
        $remainingCount = 12 - $existingCount;

        if ($existingCount >= 12) {
            return redirect()->back()->with('error', 'You cannot add more than 12 family details.');
        }

        $request->validate([
            'familyData' => 'required|array|max:' . $remainingCount,
        ]);

        foreach ($request->familyData as $data) {
            if ($existingCount < 12) {
                $familyDetail = new UserFamilyDetails();
                $familyDetail->userId = $user->id;
                $familyDetail->full_name = $data['full_name'];
                // $familyDetail->relationship = $data['relationship'];

                if ($data['relationship'] == 'Other' && !empty($data['other_relationship'])) {
                    $familyDetail->relationship = $data['other_relationship'];
                } else {
                    $familyDetail->relationship = $data['relationship'];
                }
                if ($data['occupation'] == 'Other' && !empty($data['other_occupation'])) {
                    $familyDetail->occupation = $data['other_occupation'];
                } else {
                    $familyDetail->occupation = $data['occupation'];
                    $familyDetail->occupation = $data['occupation'];
                }
                $familyDetail->phone_number = $data['phone_number'];
                $familyDetail->occupation = $data['occupation'];
                $familyDetail->next_of_kin = isset($data['next_of_kin']) ? (bool) $data['next_of_kin'] : false;
                $familyDetail->save();
                $existingCount++;
            }
        }

        $remainingCount = 12 - $existingCount;
        if ($remainingCount > 0) {
            Alert::success('Successful', "Family details added successfully. You may add $remainingCount more.");
        } else {
            Alert::success('Successful', 'Family details added successfully. You have reached the limit of 12.');
        }

        // Prevent redirect until 5 family members are added
        if ($existingCount < 10) {
            // If not all 5 family members are added, stay on the current page.
            return redirect()->back()->with('info', "You can add " . (12 - $existingCount) . " more family member(s).");
        }

        if ($existingCount < 1) {
            return redirect()->back()->with('error', 'You must add at least one family members before proceeding.');
        }

        // If 5 family members are added, redirect to the next step (health details)
        return redirect()->route('profile.healthDetails');
    }

    public function editFamilyDetails($id)
    {
        $familyMember = UserFamilyDetails::findOrFail($id);
        $familyData = UserFamilyDetails::where('userId', Auth::id())->get();
        return view('profile.editfamilyDetails', compact('familyMember', 'familyData'));
    }


    public function updateFamilyDetails(Request $request, $id)
    {
        // Validate incoming request
        $request->validate([
            'full_name' => 'required|string|max:255',
            'relationship' => 'required|string|max:50',
            'phone_number' => 'nullable|string|max:15',
            'occupation' => 'required|string|max:50',
            'other_relationship' => 'nullable|string|max:30',
            'other_occupation' => 'nullable|string|max:50',
            'next_of_kin' => 'nullable|boolean',
        ]);

        $familyMember = UserFamilyDetails::findOrFail($id);

        // Check ownership
        if ($familyMember->userId !== Auth::id()) {
            return redirect()->route('profile.familyDetails')->with('error', 'You can only edit your own family members.');
        }

        // Determine relationship value
        $relationship = $request->relationship;
        if ($relationship == 'Other' && !empty($request->other_relationship)) {
            $relationship = $request->other_relationship;
        }

        // Determine occupation value
        $occupation = $request->occupation;
        if ($occupation == 'Other' && !empty($request->other_occupation)) {
            $occupation = $request->other_occupation;
        }

        // Update the family member's details
        $familyMember->update([
            'full_name' => $request->full_name,
            'relationship' => $relationship,
            'occupation' => $occupation,
            'phone_number' => $request->phone_number ?? null,
            'next_of_kin' => $request->has('next_of_kin') ? true : false,
        ]);

        // Redirect back based on whether HR is editing
        $userId = $request->input('user_id');
        if ($userId && Auth::user()->hasPermissionTo('view staff details')) {
            return redirect()->route('employees_details.show', $userId)->with('success', 'Family member updated successfully!');
        }
        return redirect()->route('profile.familyDetails')->with('success', 'Family member updated successfully!');
    }


    public function deleteFamilyDetail($id)
    {
        $user = Auth::user();
        $familyMember = UserFamilyDetails::where('id', $id)->where('userId', $user->id)->first();

        if (!$familyMember) {
            return redirect()->back()->with('error', 'Family member not found or access denied.');
        }

        $familyMember->delete();

        return redirect()->route('profile.familyDetails')->with('success', 'Family member deleted successfully.');
    }


    public function healthDetails(Request $request)
    {
        session(['current_step' => 3]);
        $user = Auth::user();

        $existingHealthDetail = HealthDetails::where('userId', $user->id)->first();

        $healthInsurance = $existingHealthDetail ? $existingHealthDetail->health_insurance : null;

        return view('profile.healthDetails', [
            'healthDetail' => $existingHealthDetail,
            'healthInsurance' => $healthInsurance // Pass health insurance value to the view
        ]);
    }


    public function saveHealthDetails(Request $request)
    {
        // Validate the request data
        $rules = [
            'physical_disability' => 'required|string',
            'other_disability' => 'nullable|string|max:100',
            'blood_group' => 'nullable|string',
            'illness_history' => 'nullable|string',
            'health_insurance' => 'required|string',
            'insur_name' => 'nullable|string|max:100',
            'insur_no' => 'nullable|string|max:50',
            'allergies' => 'nullable|string',
        ];

        // Additional validation: if health_insurance is 'yes', insur_name and insur_no are required
        if ($request->input('health_insurance') === 'yes') {
            $rules['insur_name'] = 'required|string|max:100';
            $rules['insur_no'] = 'required|string|max:50';
        }

        $validator = Validator::make($request->all(), $rules);

        // Check for validation failures
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if HR is editing another user's profile
        $userId = $request->input('user_id');
        if ($userId && Auth::user()->hasPermissionTo('view staff details')) {
            $user = User::findOrFail($userId);
        } else {
            $user = Auth::user();
        }
        // Check if health details exist for the user
        $existingHealthDetail = HealthDetails::where('userId', $user->id)->first();

        // Determine physical disability value
        $physicalDisability = $request->input('physical_disability');
        if ($physicalDisability == 'Other' && !empty($request->input('other_disability'))) {
            $physicalDisability = $request->input('other_disability');
        }

        // Health insurance value
        $healthInsurance = $request->input('health_insurance');

        if ($existingHealthDetail) {
            // If details exist, update the record
            $existingHealthDetail->update([
                'physical_disability' => $physicalDisability,
                'blood_group' => $request->input('blood_group'),
                'illness_history' => $request->input('illness_history'),
                'health_insurance' => $healthInsurance,
                'insur_name' => $request->input('insur_name'),
                'insur_no' => $request->input('insur_no'),
                'allergies' => $request->input('allergies'),
            ]);

            // Success message
            Alert::success('Successful', 'Health details updated successfully.');
        } else {
            // If no health details exist, create new record
            HealthDetails::create([
                'userId' => $user->id,
                'physical_disability' => $physicalDisability,
                'blood_group' => $request->input('blood_group'),
                'illness_history' => $request->input('illness_history'),
                'health_insurance' => $healthInsurance,
                'insur_name' => $request->input('insur_name'),
                'insur_no' => $request->input('insur_no'),
                'allergies' => $request->input('allergies'),
                'delete_status' => 0, // Assuming '0' means active
            ]);

            Alert::success('Successful', 'Health details added successfully.');
        }

        // Redirect based on whether HR is editing
        if ($userId && Auth::user()->hasPermissionTo('view staff details')) {
            return redirect()->route('employees_details.show', $userId)->with('success', 'Health details saved successfully.');
        }
        return redirect()->route('languageKnowledge');
    }


    public function languageKnowledge(Request $request)
    {
        session(['current_step' => 4]);
        $user = Auth::user();
        $languageKnowledge = $user->languageKnowledge()->first();
        $departments = Departments::all()->keyBy('id');
        $relations = CcbrtRelation::where('userId', $user->id)->get();
        $userLanguageCount = $user->languageKnowledge->count();

        return view('profile.languageKnowledge', compact('languageKnowledge', 'user', 'departments', 'relations', 'userLanguageCount'));
    }

    public function saveLanguageKnowledge(Request $request)
    {
        $rules = [
            'language' => 'required|string|max:255',
            'other_language' => 'nullable|string|max:100',
            'speaking' => 'required|string|max:255',
            'reading' => 'required|string|max:255',
            'writing' => 'required|string|max:255',
        ];

        // If language is "Other", other_language is required
        if ($request->input('language') === 'Other') {
            $rules['other_language'] = 'required|string|max:100';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        // Determine the actual language value
        $language = $request->input('language');
        if ($language === 'Other' && !empty($request->input('other_language'))) {
            $language = $request->input('other_language');
        }

        // Check if the user already has a record for the selected language
        $existingLanguageKnowledge = $user->languageKnowledge()->where('language', $language)->first();

        if ($existingLanguageKnowledge) {
            // If the language already exists, update it
            $existingLanguageKnowledge->update([
                'language' => $language,
                'speaking' => $request->input('speaking'),
                'reading' => $request->input('reading'),
                'writing' => $request->input('writing'),
            ]);
        } else {
            // If the language does not exist, create a new record
            $user->languageKnowledge()->create([
                'language' => $language,
                'speaking' => $request->input('speaking'),
                'reading' => $request->input('reading'),
                'writing' => $request->input('writing'),
                'delete_status' => 0,
            ]);
        }

        return redirect()->route('profile.languageKnowledge')->with('success', 'Language knowledge saved successfully!');
    }



    public function edit($id)
    {
        // Get the language knowledge record by ID
        $languageKnowledge = Auth::user()->languageKnowledge()->findOrFail($id);
        if (!$languageKnowledge) {
            return redirect()->route('profile.languageKnowledge')->with('error', 'Language knowledge not found.');
        }

        return view('profile.languageKnowledge', compact('languageKnowledge'));
    }

    public function updateLanguage(Request $request, $id)
    {
        $rules = [
            'language' => 'required|string|max:255',
            'other_language' => 'nullable|string|max:100',
            'speaking' => 'required|string|max:255',
            'reading' => 'required|string|max:255',
            'writing' => 'required|string|max:255',
        ];

        // If language is "Other", other_language is required
        if ($request->input('language') === 'Other') {
            $rules['other_language'] = 'required|string|max:100';
        }

        // Validate the request data
        $validated = $request->validate($rules);

        // Get the language knowledge record for the logged-in user
        $languageKnowledge = Auth::user()->languageKnowledge()->find($id);

        // Check if the record exists
        if (!$languageKnowledge) {
            return redirect()->route('profile.languageKnowledge')->with('error', 'Language knowledge not found.');
        }

        // Determine the actual language value
        $language = $request->input('language');
        if ($language === 'Other' && !empty($request->input('other_language'))) {
            $language = $request->input('other_language');
        }

        // Update the language knowledge with the new values
        $languageKnowledge->update([
            'language' => $language,
            'speaking' => $request->speaking,
            'reading' => $request->reading,
            'writing' => $request->writing,
        ]);

        // Get the logged-in user
        $user = Auth::user();
        $departments = Departments::all()->keyBy('id');
        $relations = CcbrtRelation::where('userId', $user->id)->get();
        $userLanguageCount = $user->languageKnowledge->count();

        // Redirect to the profile language knowledge page with a success message
        return redirect()->route('profile.languageKnowledge')
            ->with('success', 'Language knowledge updated successfully');
    }



    public function ccbrtrelation()
    {
        session(['current_step' => 5]);
        $user = Auth::user();
        $departments = Departments::all()->keyBy('id');
        $relations = CcbrtRelation::where('userId', $user->id)->get();

        $user = User::find($user->id);

        // Map relations to include department names
        $relations = $relations->map(function ($relation) use ($departments) {
            $relation->department_name = $departments->get($relation->department)->dept_name ?? 'Unknown';

            return $relation;
        });

        // Pass variables to the view
        return view('profile.ccbrt_relation', compact('user', 'departments', 'relations'));
    }



    public function addRelationData(Request $request, $id = null)
    {
        $rules = [
            'userId' => 'required|exists:users,id',
            'names' => 'required|string|max:255',
            'position' => 'required|string|max:100',
            'department' => 'required|exists:departments,id',
            'relation' => 'required|string',
            'other_relation' => 'nullable|string|max:50',
        ];

        // If relation is "Other", other_relation is required
        if ($request->input('relation') === 'Other') {
            $rules['other_relation'] = 'required|string|max:50';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Determine the actual relation value
        $relation = $request->input('relation');
        if ($relation === 'Other' && !empty($request->input('other_relation'))) {
            $relation = $request->input('other_relation');
        }

        if ($id) {
            $relationRecord = CcbrtRelation::findOrFail($id);

            // Check ownership
            if ($relationRecord->userId !== Auth::id()) {
                return redirect()->route('profile.ccbrt_relation')->with('error', 'You can only edit your own relations.');
            }

            // Update the relation record
            $relationRecord->update([
                'userId' => $request->input('userId'),
                'names' => $request->input('names'),
                'position' => $request->input('position'),
                'department' => $request->input('department'),
                'relation' => $relation,
            ]);

            // Success message for update
            return redirect()->route('profile.ccbrt_relation')->with('success', 'Relation updated successfully.');
        } else {
            // Only check for relation count when adding a new relation (no $id)
            $relationCount = CcbrtRelation::where('userId', $request->input('userId'))->count();

            // Only check if user has less than 5 relations
            if ($relationCount >= 5) {
                return redirect()->back()->with('error', 'You can only add up to 5 relations.')->withInput();
            }

            // Create the relation record if no ID is passed (for new relation)
            CcbrtRelation::create([
                'userId' => $request->input('userId'),
                'names' => $request->input('names'),
                'position' => $request->input('position'),
                'department' => $request->input('department'),
                'relation' => $relation,
            ]);

            // Success message for creation
            Alert::success('Relationship added successfully', 'CCBRT related user added');

            // Redirect with success message
            return redirect()->back()->with('success', 'Relation added successfully!');
        }
    }


    public function showConflictOfInterestForm()
    {
        $user = auth()->user();
        session(['current_step' => 6]);
        $conflictData = $user->conflictOfInterestData;
        return view('profile.interest', compact('user', 'conflictData'));
    }

    public function storeConflictOfInterest(Request $request)
    {
        $rules = [
            'conflict_officer_role' => 'required|in:Yes,No',
            'officer_details' => 'nullable|string|max:1000',
            'financial_interest' => 'required|in:Yes,No',
            'financial_details' => 'nullable|string|max:1000',
            'other_interests' => 'required|in:Yes,No',
            'interest_details' => 'nullable|string|max:1000',
            'primary_employer_ccbrt' => 'required|in:Yes,No',
            'primary_employer_details' => 'nullable|string|max:1000',
            'hr_detail_declare' => 'required',
            'court_proceedings' => 'nullable|in:Yes,No',
            'court_details' => 'nullable|string|max:1000',
        ];

        // Conditional validation: if answer is "Yes", details are required
        if ($request->input('conflict_officer_role') === 'Yes') {
            $rules['officer_details'] = 'required|string|max:1000';
        }
        if ($request->input('financial_interest') === 'Yes') {
            $rules['financial_details'] = 'required|string|max:1000';
        }
        if ($request->input('other_interests') === 'Yes') {
            $rules['interest_details'] = 'required|string|max:1000';
        }
        if ($request->input('primary_employer_ccbrt') === 'No') {
            $rules['primary_employer_details'] = 'required|string|max:1000';
        }
        if ($request->input('court_proceedings') === 'Yes') {
            $rules['court_details'] = 'required|string|max:1000';
        }

        $validatedData = $request->validate($rules);

        // Get authenticated user
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        // Update the user with the validated data
        $user->update($validatedData);

        return redirect()->route('signature.index')->with('success', 'Conflict of interest disclosure submitted successfully.');
    }


    public function editRelation($id)
    {
        $user = Auth::user();
        $relationToEdit = CcbrtRelation::findOrFail($id);

        // Check ownership
        if ($relationToEdit->userId !== $user->id) {
            return redirect()->route('profile.ccbrt_relation')->with('error', 'You can only edit your own relations.');
        }

        $departments = Departments::all()->keyBy('id');
        $relations = CcbrtRelation::where('userId', $user->id)->get();

        // Map relations to include department names
        $relations = $relations->map(function ($rel) use ($departments) {
            $rel->department_name = $departments->get($rel->department)->dept_name ?? 'Unknown';
            return $rel;
        });

        return view('profile.ccbrt_relation', compact('user', 'departments', 'relations', 'relationToEdit'));
    }

    public function updateRelationData(Request $request, $id)
    {
        $rules = [
            'userId' => 'required|exists:users,id',
            'names' => 'required|string|max:255',
            'position' => 'required|string|max:100',
            'department' => 'required|exists:departments,id',
            'relation' => 'required|string',
            'other_relation' => 'nullable|string|max:50',
        ];

        // If relation is "Other", other_relation is required
        if ($request->input('relation') === 'Other') {
            $rules['other_relation'] = 'required|string|max:50';
        }

        $validator = Validator::make($request->all(), $rules);

        // If validation fails, redirect back with error messages
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Find the existing relation by ID
        $relation = CcbrtRelation::findOrFail($id);

        // Check ownership
        if ($relation->userId !== Auth::id()) {
            return redirect()->route('profile.ccbrt_relation')->with('error', 'You can only edit your own relations.');
        }

        // Determine the actual relation value
        $relationValue = $request->input('relation');
        if ($relationValue === 'Other' && !empty($request->input('other_relation'))) {
            $relationValue = $request->input('other_relation');
        }

        // Update the relation record
        $relation->update([
            'userId' => $request->input('userId'),
            'names' => $request->input('names'),
            'position' => $request->input('position'),
            'department' => $request->input('department'),
            'relation' => $relationValue,
        ]);

        // Success message for update
        return redirect()->route('profile.ccbrt_relation')->with('success', 'Relation updated successfully.');
    }

    public function deleteRelation($id)
    {
        $user = Auth::user();
        $relation = CcbrtRelation::where('id', $id)->where('userId', $user->id)->first();

        if (!$relation) {
            return redirect()->back()->with('error', 'Relation not found or access denied.');
        }

        $relation->delete();

        return redirect()->route('profile.ccbrt_relation')->with('success', 'Relation deleted successfully.');
    }



    public function confirmView()
    {
        $user = auth()->user();
        $conflictData = $user->only([
            'conflict_officer_role',
            'officer_details',
            'financial_interest',
            'financial_details',
            'other_interests',
            'interest_details',
            'primary_employer_ccbrt',
            'primary_employer_details',
            'court_proceedings',
            'court_details'
        ]);
        // dd($user->conflict_officer_role, $user->officer_details, $user->financial_interest);


        // Retrieve related data
        $languageKnowledge = LanguageKnowledge::where('userId', $user->id)->get();
        $relations = CcbrtRelation::where('userId', $user->id)->get();
        $healthDetails = HealthDetails::where('userId', $user->id)->first();
        $familyDetails = UserFamilyDetails::where('userId', $user->id)->get();
        // dd($relations);
        return view('profile.confirm', compact('user', 'conflictData', 'languageKnowledge', 'relations', 'healthDetails', 'familyDetails'));
    }

    public function submitRegistration(Request $request)
    {
        $user = auth()->user();

        // Ensure all required data is saved before submission
        $languageKnowledge = LanguageKnowledge::where('userId', $user->id)->get();
        $ccbrtRelations = CcbrtRelation::where('userId', $user->id)->get();
        $healthDetails = HealthDetails::where('userId', $user->id)->first();
        $familyDetails = UserFamilyDetails::where('userId', $user->id)->get();

        // CCBRT Relation is optional, so we don't check for it
        if ($languageKnowledge->isEmpty() || !$healthDetails || $familyDetails->isEmpty()) {
            return back()->with('error', 'Please complete all required registration steps before submission.');
        }

        return redirect()->route('hfdghsf')->with('success', 'Registration submitted successfully for HR approval.');
    }

    /**
     * HR methods to manage staff profile details - Using separate views
     */
    public function hrPersonalDetails($id)
    {
        $user = User::with('department', 'jobTitle')->findOrFail($id);
        $isClinicalDepartment = $user->jobTitle && $user->jobTitle->clinical_or_non_clinical === 'Clinical';
        $isExistingUser = $user->starting_date && \Carbon\Carbon::parse($user->starting_date)->isPast();
        return view('hr.staff.personalDetails', compact('user', 'isClinicalDepartment', 'isExistingUser'));
    }

    public function hrSavePersonalDetails(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Check if user has permission
        if (!Auth::user()->hasPermissionTo('view staff details')) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        // Use the same validation and save logic as savePersonalDetails but for the specified user
        $isClinicalDepartment = false;
        if ($user->job_title) {
            $jobTitle = \App\Models\JobTitle::find($user->job_title);
            $isClinicalDepartment = $jobTitle && $jobTitle->clinical_or_non_clinical === 'Clinical';
        }

        $isExistingUser = $user->starting_date && \Carbon\Carbon::parse($user->starting_date)->isPast();

        $messages = [
            'NIN.unique' => 'Please enter a unique National Identification Number.',
            'ccbrt_code.unique' => 'This CCBRT code is already in use. Please enter a different code.',
            'ccbrt_code.regex' => 'CCBRT Code must start with "CCBRT" followed by 1-4 digits (e.g., CCBRT0126).',
        ];

        $validationRules = [
            'place_of_birth' => 'required|string|max:255',
            'nationality' => 'required|string|max:255',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'divorce_certificate' => 'nullable|file|mimes:pdf|max:5120',
            'gender' => 'required|in:male,female,other',
            'region' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'popular_landmark' => 'required|string|max:255',
            'domicile' => 'required|string|max:255',
            'religion' => 'required|string|max:255',
        ];

        $placeOfBirth = $request->input('place_of_birth');
        if ($placeOfBirth === 'other') {
            $placeOfBirth = $request->input('other_place_of_birth_input');
        }

        if ($placeOfBirth === 'Tanzania') {
            $validationRules['NIN'] = 'nullable|regex:/^\d{8}-\d{5}-\d{5}-\d{2}$/|unique:users,NIN,' . $user->id;
        } else {
            $validationRules['NIN'] = 'nullable|string|max:50|unique:users,NIN,' . $user->id;
        }

        if ($isClinicalDepartment) {
            $validationRules['professional_reg_number'] = 'required|string|max:50';
        } else {
            $validationRules['professional_reg_number'] = 'nullable|string|max:50';
        }

        $validationRules['ccbrt_code'] = [
            'nullable',
            'string',
            'regex:/^CCBRT\d{4}$/',
            Rule::unique('users', 'ccbrt_code')->ignore($user->id),
        ];

        $validator = Validator::make($request->all(), array_merge($validationRules, [
            'nssf_no' => 'nullable|string|min:8|max:14',
            'tin_no' => 'nullable|string|min:8|max:15',
            'passport_no' => 'nullable|string|min:8|max:16',
            'employee_cv' => 'nullable|file|mimes:pdf|max:3120',
            'profile_picture' => 'nullable|file|image|mimes:jpeg,jpg,png|max:4096',
            'marriage_certificate' => 'nullable|file|mimes:pdf|max:3120',
            'divorced_certificate' => 'nullable|file|mimes:pdf|max:3120',
            'nida' => 'nullable|file|mimes:pdf|max:2048',
            'driving_license' => 'nullable|file|mimes:pdf|max:2048',
            'transport_id' => 'nullable|file|mimes:pdf|max:2048',
            'voting_id' => 'nullable|file|mimes:pdf|max:2048',
            'other_document' => 'nullable|file|mimes:pdf|max:2048',
        ]), $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update user details (same logic as savePersonalDetails)
        $user->place_of_birth = $placeOfBirth;
        $user->nationality = $request->input('nationality');
        $user->marital_status = $request->input('marital_status');
        $user->gender = $request->input('gender');
        $user->region = $request->input('region');
        $user->district = $request->input('district');
        $user->street = $request->input('street');
        $user->popular_landmark = $request->input('popular_landmark');
        $user->domicile = $request->input('domicile');
        $user->religion = $request->input('religion');
        $user->NIN = $request->input('NIN');
        $user->nssf_no = $request->input('nssf_no');
        $user->tin_no = $request->input('tin_no');
        $user->passport_no = $request->input('passport_no');
        $user->box_no = $request->input('box_no');
        $user->plot_no = $request->input('plot_no');
        $user->house_no = $request->input('house_no');
        $user->professional_reg_number = $request->input('professional_reg_number');
        $user->ccbrt_code = $request->input('ccbrt_code');

        // Handle file uploads (same as savePersonalDetails)
        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $profilePicPath = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->profile_picture = $profilePicPath;
        }

        if ($request->hasFile('employee_cv')) {
            if ($user->employee_cv) {
                Storage::disk('public')->delete($user->employee_cv);
            }
            $cvPath = $request->file('employee_cv')->store('employee_cvs', 'public');
            $user->employee_cv = $cvPath;
        }

        if ($request->hasFile('marriage_certificate') && $user->marital_status === 'married') {
            if ($user->marriage_certificate) {
                Storage::disk('public')->delete($user->marriage_certificate);
            }
            $path = $request->file('marriage_certificate')->store('marriage_certificates', 'public');
            $user->marriage_certificate = $path;
        }

        if ($request->hasFile('divorce_certificate') && $user->marital_status === 'divorced') {
            if ($user->divorce_certificate) {
                Storage::disk('public')->delete($user->divorce_certificate);
            }
            $path = $request->file('divorce_certificate')->store('divorce_certificates', 'public');
            $user->divorce_certificate = $path;
        }

        // Handle other document uploads
        foreach (['nida', 'driving_license', 'transport_id', 'voting_id', 'other_document'] as $doc) {
            if ($request->hasFile($doc)) {
                $oldPath = $user->$doc;
                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
                $path = $request->file($doc)->store($doc . 's', 'public');
                $user->$doc = $path;
            }
        }

        $user->save();

        return redirect()->route('employees_details.show', $user->id)->with('success', 'Personal details updated successfully!');
    }

    public function hrFamilyDetails($id)
    {
        $user = User::findOrFail($id);
        $familyDetails = UserFamilyDetails::where('userId', $user->id)->get();
        $familyData = $familyDetails; // Alias for view compatibility
        $existingCount = $familyDetails->count();
        $nextOfKinExists = $familyDetails->contains('next_of_kin', true);
        return view('hr.staff.familyDetails', compact('user', 'familyDetails', 'familyData', 'existingCount', 'nextOfKinExists'));
    }

    public function hrSaveFamilyDetails(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!Auth::user()->hasPermissionTo('view staff details')) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        $existingCount = UserFamilyDetails::where('userId', $user->id)->count();
        $remainingCount = 12 - $existingCount;

        if ($existingCount >= 12) {
            return redirect()->back()->with('error', 'Cannot add more than 12 family details.');
        }

        $request->validate([
            'familyData' => 'required|array|max:' . $remainingCount,
        ]);

        foreach ($request->familyData as $data) {
            if ($existingCount < 12) {
                $familyDetail = new UserFamilyDetails();
                $familyDetail->userId = $user->id;
                $familyDetail->full_name = $data['full_name'];

                if ($data['relationship'] == 'Other' && !empty($data['other_relationship'])) {
                    $familyDetail->relationship = $data['other_relationship'];
                } else {
                    $familyDetail->relationship = $data['relationship'];
                }

                if ($data['occupation'] == 'Other' && !empty($data['other_occupation'])) {
                    $familyDetail->occupation = $data['other_occupation'];
                } else {
                    $familyDetail->occupation = $data['occupation'];
                }

                $familyDetail->phone_number = $data['phone_number'];
                $familyDetail->next_of_kin = isset($data['next_of_kin']) ? (bool) $data['next_of_kin'] : false;
                $familyDetail->save();
                $existingCount++;
            }
        }

        Alert::success('Successful', 'Family details added successfully.');
        // Stay on the same page to allow adding more members
        return redirect()->back()->with('success', 'Family details added successfully.');
    }

    public function hrHealthDetails($id)
    {
        $user = User::findOrFail($id);
        $healthDetails = HealthDetails::where('userId', $user->id)->first();
        $healthDetail = $healthDetails; // Alias for view compatibility
        $healthInsurance = $healthDetails ? $healthDetails->health_insurance : null;
        return view('hr.staff.healthDetails', compact('user', 'healthDetails', 'healthDetail', 'healthInsurance'));
    }

    public function hrSaveHealthDetails(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!Auth::user()->hasPermissionTo('view staff details')) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        $rules = [
            'physical_disability' => 'required|string',
            'other_disability' => 'nullable|string|max:100',
            'blood_group' => 'nullable|string',
            'illness_history' => 'nullable|string',
            'health_insurance' => 'required|string',
            'insur_name' => 'nullable|string|max:100',
            'insur_no' => 'nullable|string|max:50',
            'allergies' => 'nullable|string',
        ];

        if ($request->input('health_insurance') === 'yes') {
            $rules['insur_name'] = 'required|string|max:100';
            $rules['insur_no'] = 'required|string|max:50';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $existingHealthDetail = HealthDetails::where('userId', $user->id)->first();

        $physicalDisability = $request->input('physical_disability');
        if ($physicalDisability == 'Other' && !empty($request->input('other_disability'))) {
            $physicalDisability = $request->input('other_disability');
        }

        $healthInsurance = $request->input('health_insurance');

        if ($existingHealthDetail) {
            $existingHealthDetail->update([
                'physical_disability' => $physicalDisability,
                'blood_group' => $request->input('blood_group'),
                'illness_history' => $request->input('illness_history'),
                'health_insurance' => $healthInsurance,
                'insur_name' => $request->input('insur_name'),
                'insur_no' => $request->input('insur_no'),
                'allergies' => $request->input('allergies'),
            ]);
            Alert::success('Successful', 'Health details updated successfully.');
        } else {
            HealthDetails::create([
                'userId' => $user->id,
                'physical_disability' => $physicalDisability,
                'blood_group' => $request->input('blood_group'),
                'illness_history' => $request->input('illness_history'),
                'health_insurance' => $healthInsurance,
                'insur_name' => $request->input('insur_name'),
                'insur_no' => $request->input('insur_no'),
                'allergies' => $request->input('allergies'),
                'delete_status' => 0,
            ]);
            Alert::success('Successful', 'Health details added successfully.');
        }

        return redirect()->route('employees_details.show', $user->id)->with('success', 'Health details saved successfully.');
    }

    public function hrLanguageKnowledge($id)
    {
        $user = User::findOrFail($id);
        $languageKnowledge = LanguageKnowledge::where('userId', $user->id)->get();
        $departments = Departments::all()->keyBy('id');
        $relations = CcbrtRelation::where('userId', $user->id)->get();
        $userLanguageCount = $languageKnowledge->count();
        return view('hr.staff.languageKnowledge', compact('user', 'languageKnowledge', 'departments', 'relations', 'userLanguageCount'));
    }

    public function hrSaveLanguageKnowledge(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!Auth::user()->hasPermissionTo('view staff details')) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        $rules = [
            'language' => 'required|string|max:255',
            'other_language' => 'nullable|string|max:100',
            'speaking' => 'required|string|max:255',
            'reading' => 'required|string|max:255',
            'writing' => 'required|string|max:255',
        ];

        // If language is "Other", other_language is required
        if ($request->input('language') === 'Other') {
            $rules['other_language'] = 'required|string|max:100';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Determine the actual language value
        $language = $request->input('language');
        if ($language === 'Other' && !empty($request->input('other_language'))) {
            $language = $request->input('other_language');
        }

        // Check if the user already has a record for the selected language
        $existingLanguageKnowledge = LanguageKnowledge::where('userId', $user->id)->where('language', $language)->first();

        if ($existingLanguageKnowledge) {
            // If the language already exists, update it
            $existingLanguageKnowledge->update([
                'language' => $language,
                'speaking' => $request->input('speaking'),
                'reading' => $request->input('reading'),
                'writing' => $request->input('writing'),
            ]);
            Alert::success('Successful', 'Language knowledge updated successfully.');
        } else {
            // If the language does not exist, create a new record
            LanguageKnowledge::create([
                'userId' => $user->id,
                'language' => $language,
                'speaking' => $request->input('speaking'),
                'reading' => $request->input('reading'),
                'writing' => $request->input('writing'),
                'delete_status' => 0,
            ]);
            Alert::success('Successful', 'Language knowledge added successfully.');
        }

        // Stay on the same page to allow adding more languages
        return redirect()->back()->with('success', 'Language knowledge saved successfully.');
    }

    public function hrCcbrtRelation($id)
    {
        $user = User::findOrFail($id);
        $relations = CcbrtRelation::where('userId', $user->id)->get();
        $departments = Departments::all();
        return view('hr.staff.ccbrtRelation', compact('user', 'relations', 'departments'));
    }

    public function hrSaveCcbrtRelation(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!Auth::user()->hasPermissionTo('view staff details')) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        $rules = [
            'names' => 'required|string|max:255',
            'position' => 'required|string|max:100',
            'department' => 'required|exists:departments,id',
            'relation' => 'required|string',
            'other_relation' => 'nullable|string|max:50',
        ];

        // If relation is "Other", other_relation is required
        if ($request->input('relation') === 'Other') {
            $rules['other_relation'] = 'required|string|max:50';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check for relation count limit (same as addRelationData)
        $relationCount = CcbrtRelation::where('userId', $user->id)->count();
        if ($relationCount >= 5) {
            return redirect()->back()->with('error', 'You can only add up to 5 relations.')->withInput();
        }

        // Determine the actual relation value
        $relationValue = $request->input('relation');
        if ($relationValue === 'Other' && !empty($request->input('other_relation'))) {
            $relationValue = $request->input('other_relation');
        }

        CcbrtRelation::create([
            'userId' => $user->id,
            'names' => $request->input('names'),
            'position' => $request->input('position'),
            'department' => $request->input('department'),
            'relation' => $relationValue,
        ]);

        Alert::success('Successful', 'CCBRT relation added successfully.');
        // Stay on the same page to allow adding more relations
        return redirect()->back()->with('success', 'CCBRT relation added successfully.');
    }

    public function hrConflictInterest($id)
    {
        $user = User::findOrFail($id);
        return view('hr.staff.conflictInterest', compact('user'));
    }

    public function hrSaveConflictInterest(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!Auth::user()->hasPermissionTo('view staff details')) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        $rules = [
            'conflict_officer_role' => 'nullable|in:Yes,No',
            'officer_details' => 'nullable|string|max:1000',
            'financial_interest' => 'nullable|in:Yes,No',
            'financial_details' => 'nullable|string|max:1000',
            'other_interests' => 'nullable|in:Yes,No',
            'interest_details' => 'nullable|string|max:1000',
            'primary_employer_ccbrt' => 'required|in:Yes,No',
            'primary_employer_details' => 'nullable|string|max:1000',
            'court_proceedings' => 'nullable|in:Yes,No',
            'court_details' => 'nullable|string|max:1000',
            'hr_detail_declare' => 'required',
        ];

        // Conditional validation: if answer is "Yes", details are required
        if ($request->input('conflict_officer_role') === 'Yes') {
            $rules['officer_details'] = 'required|string|max:1000';
        }
        if ($request->input('financial_interest') === 'Yes') {
            $rules['financial_details'] = 'required|string|max:1000';
        }
        if ($request->input('other_interests') === 'Yes') {
            $rules['interest_details'] = 'required|string|max:1000';
        }
        if ($request->input('primary_employer_ccbrt') === 'No') {
            $rules['primary_employer_details'] = 'required|string|max:1000';
        }
        if ($request->input('court_proceedings') === 'Yes') {
            $rules['court_details'] = 'required|string|max:1000';
        }

        $validatedData = $request->validate($rules);

        // Ensure hr_detail_declare is set to 'on' if checkbox is checked
        if ($request->has('hr_detail_declare')) {
            $validatedData['hr_detail_declare'] = 'on';
        }

        // Update the user with the validated data
        $user->update($validatedData);

        Alert::success('Successful', 'Conflict of interest details saved successfully.');
        return redirect()->route('employees_details.show', $user->id)->with('success', 'Conflict of interest details saved successfully.');
    }

    /**
     * HR submits registration workflow for manually added staff
     */
    public function hrSubmitRegistration(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();

        // Check if user has permission
        if (!$currentUser->hasPermissionTo('view staff details')) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        // Ensure all required data is saved before submission
        $languageKnowledge = LanguageKnowledge::where('userId', $user->id)->get();
        $ccbrtRelations = CcbrtRelation::where('userId', $user->id)->get();
        $healthDetails = HealthDetails::where('userId', $user->id)->first();
        $familyDetails = UserFamilyDetails::where('userId', $user->id)->get();
        $hasPersonalDetails = $user->place_of_birth && $user->marital_status;
        $hasConflictInterest = $user->hr_detail_declare === 'on';

        // CCBRT Relation is optional, so we don't check for it
        if (
            $languageKnowledge->isEmpty() || !$healthDetails ||
            $familyDetails->isEmpty() || !$hasPersonalDetails || !$hasConflictInterest
        ) {
            return redirect()->back()->with('error', 'Please complete all required profile details before submitting for approval.');
        }

        // Check if workflow already exists
        $existingWorkflow = \App\Models\Workflow::where('hr_form', $user->id)
            ->where('work_flow_completed', 0)
            ->first();

        if ($existingWorkflow) {
            return redirect()->back()->with('error', 'This staff member already has a pending HR form submission.');
        }

        // Fetch all HR users (excluding current user to avoid self-approval)
        $hr_to_approve = User::role('hr')->where('id', '!=', $currentUser->id)->get();

        if ($hr_to_approve->isEmpty()) {
            return redirect()->back()->with('error', 'No other HR approvers found. Please contact system administrator.');
        }

        try {
            DB::beginTransaction();

            // Update user status if inactive
            if ($user->status === 'inactive') {
                $user->status = 'pending';
                $user->save();
            }

            // Create new workflow entry
            $workflow = new \App\Models\Workflow;
            $workflow->user_id = $user->id;
            $workflow->work_flow_status = 'Pending for approval';
            $workflow->work_flow_completed = 0;
            $workflow->hr_form = $user->id;
            $workflow->save();

            // Prepare workflow history entries
            $historyEntries = [];
            $nowFormatted = Carbon::now()->format('d F Y');

            foreach ($hr_to_approve as $hr) {
                $historyEntries[] = [
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $currentUser->id,
                    'attended_by' => $hr->id,
                    'status' => 0, // Pending
                    'remark' => 'Awaiting HR approval',
                    'step_name' => 'HR Approval',
                    'action_taken' => 'Forwarded',
                    'attend_date' => $nowFormatted,
                    'parent_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert into workflow history
            DB::table('work_flow_histories')->insert($historyEntries);

            DB::commit();

            // Prepare request details for email
            $requestDetails = [
                'forwarded_by' => $currentUser->fname . ' ' . $currentUser->lname,
                'request' => "HR Form for " . $user->fname . ' ' . $user->lname,
                'requestDate' => $nowFormatted,
            ];

            // Send queued emails to each HR user
            foreach ($hr_to_approve as $hr) {
                Mail::to($hr->email)->queue(new \App\Mail\ApprovalRequestNotification($hr, $requestDetails));
            }

            return redirect()->route('employees_details.show', $user->id)
                ->with('success', 'Registration workflow submitted successfully! All HR members have been notified for approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting HR registration workflow: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'hr_user_id' => $currentUser->id,
                'exception' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to submit registration workflow. Please try again or contact support.');
        }
    }


    public function getJobTitles($deptId)
    {
        $jobTitles = JobTitle::where('deptId', $deptId)->orderBy('job_title', 'asc')->get();
        return response()->json($jobTitles);
    }

    public function register()
    {
        // Show all HR policies (policies table) - no filtering
        $policies = Policy::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();
        $departments = Departments::orderBy('dept_name', 'asc')->get();

        $employmentTypes = EmploymentTypes::all()->sortBy('employment_type');
        return view('auth.registration', compact('departments', 'employmentTypes', 'policies'));
    }

    /**
     * Show the form for HR to add new staff
     */
    public function showAddStaffForm()
    {
        $departments = Departments::orderBy('dept_name', 'asc')->get();
        $employmentTypes = EmploymentTypes::all()->sortBy('employment_type');
        return view('staff.add', compact('departments', 'employmentTypes'));
    }

    public function handleRegistration(Request $request)
    {
        // Check if selected job title is clinical
        $isClinicalDepartment = false;
        if ($request->job_title) {
            $jobTitle = \App\Models\JobTitle::find($request->job_title);
            $isClinicalDepartment = $jobTitle && $jobTitle->clinical_or_non_clinical === 'Clinical';
        }

        $rules = [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'job_title' => 'required|exists:job_titles,id',
            'email' => 'required|email|max:500|unique:users',
            'deptId' => 'required|exists:departments,id',
            'DOB' => 'required|date|before:' . now()->subYears(18)->format('Y-m-d'),
            'employment_typeId' => 'required|exists:employment_types,id',
            'password' => 'required|confirmed|min:6',
            'mobile' => [
                'required',
                'string',
                'regex:/^[0-9]{9,12}$/', // Validates 9-12 digits (Tanzania phone numbers)
            ],
            'country_code' => 'required|numeric|min:1|max:999',
            'starting_date' => 'required|date|before_or_equal:today',
            'ending_date' => 'required|date|after:starting_date',
        ];

        // Make professional_reg_number and license_provider required if job title is clinical
        if ($isClinicalDepartment) {
            $rules['professional_reg_number'] = 'required|string|max:50';
            $rules['license_provider'] = 'required|string|max:255';
        } else {
            $rules['professional_reg_number'] = 'nullable|string|max:50';
            $rules['license_provider'] = 'nullable|string|max:255';
        }

        $messages = [
            'DOB.before' => 'You must be at least 18 years old to register.',
            'job_title.required' => 'Please select a job title after selecting a department.',
            'job_title.exists' => 'The selected job title is invalid.',
            'mobile.required' => 'Phone number is required.',
            'mobile.regex' => 'Please enter a valid phone number (9-12 digits).',
            'country_code.required' => 'Country code is required.',
            'country_code.numeric' => 'Country code must be a number.',
            'professional_reg_number.required' => 'Professional Registration Number is required for users in clinical job titles.',
            'license_provider.required' => 'License Provider is required for users in clinical job titles.',
            'starting_date.before_or_equal' => 'Starting date cannot be in the future.',
            'ending_date.required' => 'Ending date is required.',
            'ending_date.after' => 'Ending date must be after the starting date.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            \Log::error('Validation failed', ['errors' => $validator->errors()]);

            // Redirect back with input and errors
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_message', 'Please correct the errors below and try again.');
        }

        // Process the registration if validation passes
        $username = strtolower($request->input('fname')) . '.' . strtolower($request->input('lname'));

        $cleanMobile = preg_replace('/[^0-9]/', '', $request->mobile); // Clean mobile input
        $phoneNumber = '+' . $request->country_code . $cleanMobile; // Combine country code and mobile


        $user = User::create([
            'fname' => $request->input('fname'),
            'mname' => $request->input('mname'),
            'lname' => $request->input('lname'),
            'username' => $username,
            'DOB' => $request->input('DOB'),
            'gender' => $request->input('gender'),
            'marital_status' => $request->input('marital_status'),
            'email' => $request->input('email'),
            'religion' => $request->input('religion'),
            'mobile' => $phoneNumber,
            'job_title' => $request->input('job_title'),
            'home_address' => $request->input('home_address'),
            'district' => $request->input('district'),
            'region' => $request->input('region'),
            'professional_reg_number' => $request->input('professional_reg_number'),
            'place_of_birth' => $request->input('place_of_birth'),
            'house_no' => $request->input('house_no'),
            'street' => $request->input('street'),
            'deptId' => $request->input('deptId'),
            'employment_typeId' => $request->input('employment_typeId'),
            'employee_cv' => $request->input('employee_cv'),
            'NIN' => $request->input('NIN'),
            'nssf_no' => $request->input('nssf_no'),
            'domicile' => $request->input('domicile'),
            'starting_date' => $request->input('starting_date'),
            'ending_date' => $request->input('ending_date'),
            'password' => Hash::make($request->input('password')),
            'status' => 'inactive',
        ]);

        //Assigning roles initally from the first registration
        $user->assignRole('requester');

        // Store license provider in session for later use in HR workflow (if clinical)
        if ($isClinicalDepartment && $request->input('license_provider')) {
            session(['pending_license_provider_' . $user->id => $request->input('license_provider')]);
        }

        // Send welcome email
        Mail::to($user->email)->queue(new WelcomeUserMail($user));

        // Flash username for login page modal
        session()->flash('registered_username', $username);
        session()->flash('registration_success', true);

        return redirect()->route('login');
    }

    /**
     * Handle staff registration by HR
     */
    public function handleAddStaff(Request $request)
    {
        // Check if selected job title is clinical
        $isClinicalDepartment = false;
        if ($request->job_title) {
            $jobTitle = \App\Models\JobTitle::find($request->job_title);
            $isClinicalDepartment = $jobTitle && $jobTitle->clinical_or_non_clinical === 'Clinical';
        }

        $rules = [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'job_title' => 'required|exists:job_titles,id',
            'email' => 'required|email|max:500|unique:users',
            'deptId' => 'required|exists:departments,id',
            'DOB' => 'required|date|before:' . now()->subYears(18)->format('Y-m-d'),
            'employment_typeId' => 'required|exists:employment_types,id',
            'password' => 'required|confirmed|min:6',
            'mobile' => [
                'required',
                'string',
                'regex:/^[0-9]{9,12}$/', // Validates 9-12 digits (Tanzania phone numbers)
            ],
            'country_code' => 'required|numeric|min:1|max:999',
            'starting_date' => 'required|date|before_or_equal:today',
            'ending_date' => 'required|date|after:starting_date',
        ];

        // Make professional_reg_number and license_provider required if job title is clinical
        if ($isClinicalDepartment) {
            $rules['professional_reg_number'] = 'required|string|max:50';
            $rules['license_provider'] = 'required|string|max:255';
        } else {
            $rules['professional_reg_number'] = 'nullable|string|max:50';
            $rules['license_provider'] = 'nullable|string|max:255';
        }

        $messages = [
            'DOB.before' => 'The staff member must be at least 18 years old.',
            'professional_reg_number.required' => 'Professional Registration Number is required for users in clinical job titles.',
            'license_provider.required' => 'License Provider is required for users in clinical job titles.',
            'starting_date.before_or_equal' => 'Starting date cannot be in the future.',
            'ending_date.required' => 'Ending date is required.',
            'ending_date.after' => 'Ending date must be after the starting date.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            \Log::error('HR Staff Registration Validation failed', ['errors' => $validator->errors()]);

            // Redirect back with input and errors
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_message', 'Please correct the errors below and try again.');
        }

        // Process the registration if validation passes
        $username = strtolower($request->input('fname')) . '.' . strtolower($request->input('lname'));

        $cleanMobile = preg_replace('/[^0-9]/', '', $request->mobile); // Clean mobile input
        $phoneNumber = '+' . $request->country_code . $cleanMobile; // Combine country code and mobile

        $user = User::create([
            'fname' => $request->input('fname'),
            'mname' => $request->input('mname'),
            'lname' => $request->input('lname'),
            'username' => $username,
            'DOB' => $request->input('DOB'),
            'gender' => $request->input('gender'),
            'marital_status' => $request->input('marital_status'),
            'email' => $request->input('email'),
            'religion' => $request->input('religion'),
            'mobile' => $phoneNumber,
            'job_title' => $request->input('job_title'),
            'home_address' => $request->input('home_address'),
            'district' => $request->input('district'),
            'region' => $request->input('region'),
            'professional_reg_number' => $request->input('professional_reg_number'),
            'place_of_birth' => $request->input('place_of_birth'),
            'house_no' => $request->input('house_no'),
            'street' => $request->input('street'),
            'deptId' => $request->input('deptId'),
            'employment_typeId' => $request->input('employment_typeId'),
            'employee_cv' => $request->input('employee_cv'),
            'NIN' => $request->input('NIN'),
            'nssf_no' => $request->input('nssf_no'),
            'domicile' => $request->input('domicile'),
            'starting_date' => $request->input('starting_date'),
            'ending_date' => $request->input('ending_date'),
            'password' => Hash::make($request->input('password')),
            'status' => 'inactive',
        ]);

        //Assigning roles initially from the first registration
        $user->assignRole('requester');

        // Store license provider in session for later use in HR workflow (if clinical)
        if ($isClinicalDepartment && $request->input('license_provider')) {
            session(['pending_license_provider_' . $user->id => $request->input('license_provider')]);
        }

        // Send welcome email
        Mail::to($user->email)->queue(new WelcomeUserMail($user));

        // Flash message to show on staff details page
        return redirect()->route('employee.index')
            ->with('success', 'Staff member <strong>' . $user->fname . ' ' . $user->lname . '</strong> has been successfully added. They will receive a welcome email with login credentials.');
    }


    //Function shows edit user form
    public function showEditForm($id)
    {
        $user = User::with('department', 'jobTitle', 'assignedEntity', 'assignedEntities')->findOrFail($id);
        $currentUser = Auth::user();

        // Get all roles (always include super-admin so it can be shown as disabled if needed)
        $roles = Role::get();

        $userRoles = $user->roles->pluck('name')->toArray(); // Get user roles

        // Get divisions/entities for finance officer assignment
        $divisions = \App\Models\Division::where(function($q) {
            $q->where('delete_status', '!=', '1')->orWhereNull('delete_status');
        })->orderBy('name')->get();
        if ($divisions->isEmpty()) {
            // Fallback: if filtering returns nothing, show all divisions so assignment is still possible.
            $divisions = \App\Models\Division::orderBy('name')->get();
        }

        return view('role-permission/user.edit', compact('user', 'roles', 'userRoles', 'divisions'));
    }

    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        $exists = User::where('email', $email)->exists();
        return response()->json(['exists' => $exists]);
    }


    public function editUserRole(Request $request, $userId)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'required|exists:roles,name',
            'assigned_entity_ids' => 'nullable|array',
            'assigned_entity_ids.*' => 'nullable|exists:divisions,id',
        ]);

        $user = User::findOrFail($userId);
        $currentUser = Auth::user();
        $oldRoles = $user->roles->pluck('name')->toArray();
        $newRoles = $request->roles;
        $assignedEntityIds = collect($request->input('assigned_entity_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // If an entity is selected, enforce finance officer role automatically.
        if (!empty($assignedEntityIds) && !collect($newRoles)->contains(fn ($role) => strtolower((string) $role) === 'finance officer')) {
            $newRoles[] = 'finance officer';
        }

        // Check if super-admin role is being added or removed
        $hadSuperAdmin = in_array('super-admin', $oldRoles);
        $willHaveSuperAdmin = in_array('super-admin', $newRoles);
        $isSuperAdminChange = $hadSuperAdmin !== $willHaveSuperAdmin;

        // If user being edited has super-admin role but current user is not super-admin,
        // preserve the super-admin role (add it back to the roles array)
        if ($hadSuperAdmin && !$currentUser->hasRole('super-admin')) {
            if (!in_array('super-admin', $newRoles)) {
                $newRoles[] = 'super-admin';
                $willHaveSuperAdmin = true; // Update the flag
                $isSuperAdminChange = false; // No change since we're preserving it
            }
        }

        // Prevent non-super-admin from adding super-admin role
        if (!$hadSuperAdmin && $willHaveSuperAdmin && !$currentUser->hasRole('super-admin')) {
            return redirect()->back()->with('error', 'You do not have permission to assign the super-admin role.');
        }

        // If super-admin role is being changed, check permissions
        if ($isSuperAdminChange) {
            // Only super-admin can add or remove super-admin role
            if (!$currentUser->hasRole('super-admin')) {
                return redirect()->back()->with('error', 'You do not have permission to assign or remove the super-admin role.');
            }

            // If removing super-admin role, check if it's the last one
            if ($hadSuperAdmin && !$willHaveSuperAdmin) {
                // Count all users with super-admin role (this includes the user being edited)
                $superAdminCount = User::role('super-admin')->count();

                // If this user is the only super-admin, prevent removal
                if ($superAdminCount <= 1) {
                    return redirect()->back()->with('error', 'Cannot remove super-admin role. There must be at least one user with the super-admin role.');
                }
            }
        }

        try {
            $user->syncRoles($newRoles);

            // Handle finance officer entity assignment
            $hasFinanceRole = collect($newRoles)->contains(function ($role) {
                return strtolower($role) === 'finance officer';
            });

            if ($hasFinanceRole) {
                // Enforce one finance officer per entity.
                $pivotConflictEntityIds = \Illuminate\Support\Facades\DB::table('user_assigned_entities')
                    ->whereIn('division_id', $assignedEntityIds)
                    ->where('user_id', '!=', $user->id)
                    ->pluck('division_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $legacyConflictEntityIds = User::where('id', '!=', $user->id)
                    ->whereNotNull('assigned_entity_id')
                    ->whereIn('assigned_entity_id', $assignedEntityIds)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'finance officer')
                            ->orWhere('name', 'like', 'finance-officer-%');
                    })
                    ->pluck('assigned_entity_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $conflictEntityIds = collect(array_merge($pivotConflictEntityIds, $legacyConflictEntityIds))
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($conflictEntityIds)) {
                    $conflictEntityNames = \App\Models\Division::whereIn('id', $conflictEntityIds)
                        ->pluck('name')
                        ->filter()
                        ->implode(', ');

                    return redirect()->back()->withInput()->with(
                        'error',
                        'Each entity can only have one finance officer. These entities are already assigned: ' . $conflictEntityNames
                    );
                }

                $user->assignedEntities()->sync($assignedEntityIds);
                // Keep legacy single-entity column populated with first selected entity for compatibility.
                $user->assigned_entity_id = !empty($assignedEntityIds) ? $assignedEntityIds[0] : null;
            } elseif (!$hasFinanceRole) {
                $user->assignedEntities()->sync([]);
                $user->assigned_entity_id = null;
            }
            $user->save();

            return redirect('users')->with('success', 'Role assigned successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to assign role: ' . $e->getMessage());
        }
    }

    /**
     * Get user permissions view for modal
     */
    public function getUserPermissions($id)
    {
        $user = User::with('roles.permissions', 'permissions')->findOrFail($id);
        $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->get();
        $userRoles = $user->roles;
        $userDirectPermissions = $user->permissions->pluck('name')->toArray();
        
        // Group permissions by role
        $permissionsByRole = [];
        foreach ($userRoles as $role) {
            $permissionsByRole[$role->name] = $role->permissions->pluck('name')->toArray();
        }
        
        $html = view('role-permission.user.permissions-modal', compact('user', 'allPermissions', 'userRoles', 'userDirectPermissions', 'permissionsByRole'))->render();
        return response($html);
    }

    /**
     * Update user permissions
     */
    public function updateUserPermissions(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*.permission' => 'required|string|exists:permissions,name',
            'permissions.*.role' => 'nullable|string|exists:roles,name',
        ]);

        try {
            DB::beginTransaction();
            
            // Get all selected permissions
            $selectedPermissions = collect($request->permissions)->pluck('permission')->unique()->toArray();
            
            // Sync direct permissions to user (these override role permissions)
            $user->syncPermissions($selectedPermissions);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'User permissions updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function assignPlatformAndUnits(Request $request, $id)
    {
        $request->validate([
            'platform_ids'        => ['nullable', 'array'],
            'platform_ids.*'      => ['exists:platforms,id'],
            'primary_platform_id' => ['nullable', 'exists:platforms,id'],
        ]);

        $user = User::findOrFail($id);

        $ids = $request->platform_ids ?? [];
        $user->platforms()->sync($ids);

        if ($request->filled('primary_platform_id')) {
            if (!empty($ids) && !in_array((int)$request->primary_platform_id, array_map('intval', $ids), true)) {
                return back()->with('error', 'Primary platform must be one of the selected platforms.');
            }
            $user->primary_platform_id = $request->primary_platform_id;
        } elseif (count($ids) === 1) {
            // convenience: auto-choose the only one as primary
            $user->primary_platform_id = $ids[0];
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'Platforms assigned/unassigned successfully.');
    }

    // Show Assign Role Form
    public function showAssignRoleForm($userId)
    {
        $user = User::findOrFail($userId);
        $roles = Role::where('name', 'not like', 'finance-officer-%')->get();
        return view('user.assign-role', compact('user', 'roles'));
    }

    // Show Remove Role Form
    public function showRemoveRoleForm($userId)
    {
        $user = User::findOrFail($userId);
        return view('user.remove-role', compact('user'));
    }

    // Remove Role from User
    public function removeRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($userId);
        $oldRoles = $user->roles->pluck('name')->toArray();
        $user->removeRole($request->role);
        $newRoles = $user->fresh()->roles->pluck('name')->toArray();


        return redirect()->back()->with('status', 'Role removed successfully');
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }

        $userInfo = [
            'username' => $user->username,
            'email' => $user->email,
            'id' => $user->id
        ];

        $user->delete();


        return redirect()->route('users.index')->with('status', 'User deleted successfully.');
    }


    public function logout()
    {
        $user = Auth::user();
        $sessionId = Session::getId();

        // Remove session from database
        if ($user && $sessionId) {
            UserSession::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->delete();
        }

        Auth::logout();
        Session::flush();

        return redirect()->route('login');
    }

    /**
     * Switch active role (view-as filter). Only allows roles the user actually has.
     * Does not change the user's assigned roles in the database.
     */
    public function switchRole(Request $request)
    {
        $request->validate(['role' => 'required|string|max:255']);
        $roleName = $request->input('role');
        $user = Auth::user();
        $userRoleNames = $user->getRoleNames();
        if ($userRoleNames->isEmpty()) {
            return redirect()->back()->with('error', 'You have no roles assigned.');
        }
        $normalized = $userRoleNames->map(fn ($n) => (string) $n);
        if (!$normalized->contains($roleName)) {
            return redirect()->back()->with('error', 'You do not have that role.');
        }
        Session::put(User::ACTIVE_ROLE_SESSION_KEY, $roleName);
        return redirect()->back()->with('success', 'Viewing as ' . $roleName . '.');
    }

    /**
     * Clear active role so the user sees content for all their roles again.
     */
    public function clearActiveRole()
    {
        Session::forget(User::ACTIVE_ROLE_SESSION_KEY);
        return redirect()->back()->with('success', 'Viewing all roles.');
    }

    /**
     * Manage user sessions - allow only one active session at a time
     * When user logs in from a new browser/device, all previous sessions are terminated
     */
    private function manageUserSessions($user, Request $request)
    {
        $sessionId = Session::getId();
        $sessionLifetime = 2; // 2 minutes (120 seconds) in minutes for cleanup

        // Clean up expired sessions first
        UserSession::cleanupExpired($sessionLifetime);

        // Check if current session already exists
        $currentSession = UserSession::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->first();

        if (!$currentSession) {
            // This is a new login - remove ALL previous sessions for this user (single session enforcement)
            $previousSessions = UserSession::where('user_id', $user->id)
                ->where('session_id', '!=', $sessionId)
                ->get();

            foreach ($previousSessions as $oldSession) {
                // Invalidate the old session by deleting the session file
                $this->invalidateSessionFile($oldSession->session_id);

                // Delete the session record
                $oldSession->delete();
            }

            // Create new session record
            UserSession::create([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_activity' => now(),
            ]);
        } else {
            // Update existing session
            $currentSession->update([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_activity' => now(),
            ]);
        }
    }

    /**
     * Invalidate a session file by session ID
     */
    private function invalidateSessionFile($sessionId)
    {
        if (config('session.driver') === 'file') {
            $sessionPath = storage_path('framework/sessions');
            $sessionFiles = glob($sessionPath . '/*');

            foreach ($sessionFiles as $file) {
                if (is_file($file)) {
                    // Laravel session files are named with session ID hash
                    // Try to match by reading file content
                    $fileContent = @file_get_contents($file);
                    if ($fileContent) {
                        // Check if session ID is in the file content
                        if (strpos($fileContent, $sessionId) !== false) {
                            @unlink($file);
                            continue;
                        }

                        // Also check filename patterns
                        $filename = basename($file);
                        // Laravel uses different naming conventions
                        if (
                            $filename === $sessionId ||
                            $filename === 'sess_' . $sessionId ||
                            preg_match('/^' . preg_quote($sessionId, '/') . '/', $filename)
                        ) {
                            @unlink($file);
                        }
                    }
                }
            }
        } elseif (config('session.driver') === 'database') {
            // If using database sessions, delete from sessions table
            \DB::table('sessions')->where('id', $sessionId)->delete();
        }
    }

    public function showChangePasswordForm()
    {
        return view('user_profile.pass');
    }

    /**
     * Check if user session is still valid
     * Used by idle timeout detection
     */
    public function checkSession()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $sessionId = Session::getId();

            // Check if session exists and is still valid (within 120 seconds)
            $userSession = UserSession::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->first();

            if ($userSession) {
                // Check if session is still active (last activity within 2 minutes / 120 seconds)
                $lastActivity = $userSession->last_activity;
                $secondsSinceActivity = now()->diffInSeconds($lastActivity);

                if ($secondsSinceActivity <= 120) {
                    // Update last activity
                    $userSession->update(['last_activity' => now()]);
                    return response()->json([
                        'valid' => true,
                        'user_id' => Auth::id()
                    ]);
                } else {
                    // Session expired due to inactivity (more than 120 seconds)
                    Auth::logout();
                    Session::flush();
                    $userSession->delete();
                    return response()->json([
                        'valid' => false,
                        'session_expired' => true,
                        'message' => 'Your session has expired due to inactivity.'
                    ], 401);
                }
            } else {
                // Session doesn't exist - might have been logged out from another device
                Auth::logout();
                Session::flush();
                return response()->json([
                    'valid' => false,
                    'session_expired' => true,
                    'message' => 'Your session has expired because you logged in from another browser or device.'
                ], 401);
            }
        }

        return response()->json([
            'valid' => false,
            'session_expired' => true,
            'message' => 'Your session has expired due to inactivity.'
        ], 401);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|different:current_password',
            'new_password_confirmation' => 'required|string|same:new_password',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->with('error', 'The current password is incorrect.');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();
        Alert::success('Password changed Successful', 'Please proceed');
        return redirect()->route('user_profile.pass')->with('success', 'Password changed successfully.');
    }

    public function forgetPassChange(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.exists' => 'We could not find an account with that email address. Please check the email or contact support.',
        ]);

        try {
            $res = Password::sendResetLink($request->only('email'));
            if ($res === Password::RESET_LINK_SENT) {
                return back()->with('status', 'If that email is registered, we have sent you a password reset link. Please check your inbox (and spam folder).');
            }
            return back()->withErrors(['email' => trans($res)]);
        } catch (TransportException $e) {
            \Log::error('Mail transport error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'Unable to send password reset email. Please check your email configuration or contact the administrator.'
            ])->withInput();
        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'An error occurred while sending the password reset email. Please try again later or contact support.'
            ])->withInput();
        }
    }

    public function showResetPasswordForm(Request $request, $token)
    {
        return view('auth.reset', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|confirmed|min:6',
            'token' => 'required',
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.exists' => 'We could not find an account with that email address.',
            'password.required' => 'Please enter a new password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 6 characters.',
            'token.required' => 'Invalid or expired reset link. Please request a new password reset.',
        ]);

        $res = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($res === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset. You can now log in with your new password.');
        }

        $message = trans($res);
        if ($res === Password::INVALID_TOKEN) {
            $message = 'This password reset link has expired or is invalid. Please request a new one from the Forgot password page.';
        }
        return back()->withErrors(['email' => $message])->withInput($request->only('email'));
    }

    public function showForgetPasswordForm()
    {
        return view('auth.forget'); // Assuming you have a view file for this
    }
    public function adminResetUserPassword(Request $request, $userId)
    {
        // Find the user by ID from the route parameter
        $user = User::findOrFail($userId);

        // Generate a new random password
        $newPassword = Str::random(10);

        // Update the user's password
        $user->password = Hash::make($newPassword);
        $user->save();


        // Queue the email notification
        try {
            Mail::to($user->email)->queue(new AdminResetPassword($user, $newPassword));
        } catch (\Exception $e) {
            // Log the error for debugging (optional)
            \Log::error('Failed to queue email for user ' . $user->id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Password reset successfully, but failed to queue email notification.');
        }

        return redirect()->back()->with('success', 'Password reset successfully. The user will be notified via email.');
    }

    public function adminChangeUserPassword(Request $request, $userId)
    {
        $actor = Auth::user();
        if (!$actor || !$actor->hasAnyRole(['super-admin', 'it'])) {
            return redirect()->back()->with('error', 'Only super-admin or IT can manually reset user passwords.');
        }

        // Validate the request
        $request->validate([
            'password' => 'required|min:6', // Ensure password meets minimum requirements
        ]);

        // Find the user
        $user = User::findOrFail($userId);
        if ($user->hasRole('super-admin')) {
            return redirect()->back()->with('error', 'Super-admin password cannot be changed from this action.');
        }

        // Update the user's password
        $user->password = Hash::make($request->password);
        $user->save();


        return redirect()->back()->with('success', 'Password changed successfully.');
    }


    public function editUserDetails($id)
    {
        $user = User::with('department', 'jobTitle')->findOrFail($id);
        $staffContract = Contract::with('contractTemplate')->where('user_id', $id)->latest('id')->first();
        $contractTemplates = ContractTemplate::orderBy('name')->get();
        $departments = Departments::all();
        $jobTitles = $user->department ? JobTitle::where('deptId', $user->department->id)->get() : collect();
        $hec_title = JobTitle::where('deptId', 45)->get();
        $employmentTypes = EmploymentTypes::all();
        $healthDetails = HealthDetails::where('userId', $id)->first();
        $languageKnowledge = LanguageKnowledge::where('userId', $id)->get();
        return view('employees_details.edituser', compact('user', 'staffContract', 'contractTemplates', 'departments', 'jobTitles', 'hec_title', 'employmentTypes', 'healthDetails', 'languageKnowledge'));
    }

    public function getJobTitless($departmentId)
    {
        // Fetch job titles based on the department ID
        $jobTitles = JobTitle::where('deptId', $departmentId)->get();

        // Return the job titles as a JSON response
        return response()->json(['jobTitles' => $jobTitles]);
    }

    public function updateUserDetails(Request $request, $id)
    {
        $educationLevels = ['primary', 'o_level', 'a_level', 'certificate', 'diploma', 'degree', 'masters', 'phd'];

        // Validate incoming request
        $validationRules = [
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'username' => 'nullable|string|max:500|unique:users,username,' . $id,
            'DOB' => 'nullable|date',
            'email' => 'required|email|max:500|unique:users,email,' . $id,
            'gender' => 'nullable|string|in:Male,Female',
            'marital_status' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'mobile' => 'nullable|string|max:20',
            'NIN' => 'nullable|string|max:50',
            'nssf_no' => 'nullable|string|max:50',
            'passport_no' => 'nullable|string|max:50',
            'tin_no' => 'nullable|string|max:50',
            'department' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'employment_typeId' => 'required|integer|exists:employment_types,id',
            'ccbrt_code' => 'nullable|regex:/^CCBRT\d{4}$/',
            'professional_reg_number' => 'nullable|string|max:100',
            'hec_allocation' => 'nullable|integer',
            'home_address' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'place_of_birth' => 'nullable|string|max:255',
            'house_no' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:100',
            'popular_landmark' => 'nullable|string|max:255',
            'employee_cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'contract' => 'nullable|array',
            'contract.start_date' => 'nullable|date',
            'contract.end_date' => 'nullable|date|after_or_equal:contract.start_date',
            'contract.duration' => 'nullable|string|max:255',
            'contract.probation_period' => 'nullable|string|max:255',
            'contract.status' => 'nullable|string|max:100',
            'domicile' => 'nullable|string|max:255',
            'starting_date' => 'nullable|date',
            'ending_date' => 'nullable|date',
            'approvelocum' => 'nullable|boolean',
            'conflict_officer_role' => 'nullable|string|in:Yes,No',
            'officer_details' => 'nullable|string',
            'financial_interest' => 'nullable|string|in:Yes,No',
            'financial_details' => 'nullable|string',
            'other_interests' => 'nullable|string|in:Yes,No',
            'interest_details' => 'nullable|string',
            'primary_employer_ccbrt' => 'nullable|string|in:Yes,No',
            'primary_employer_details' => 'nullable|string',
            'court_proceedings' => 'nullable|string|in:Yes,No',
            'court_details' => 'nullable|string',
            'physical_disability' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'health_insurance' => 'nullable|string|in:Yes,No',
            'insur_name' => 'nullable|string|max:255',
            'insur_no' => 'nullable|string|max:255',
            'illness_history' => 'nullable|string',
            'allergies' => 'nullable|string',
            'languages' => 'nullable|array',
            'languages.*.language' => 'nullable|string|max:100',
            'languages.*.speaking' => 'nullable|string|in:Excellent,Good,Fair,Poor',
            'languages.*.reading' => 'nullable|string|in:Excellent,Good,Fair,Poor',
            'languages.*.writing' => 'nullable|string|in:Excellent,Good,Fair,Poor',
            'signature' => 'nullable|string|max:50000',
        ];

        foreach ($educationLevels as $level) {
            $validationRules["{$level}_institution"] = 'nullable|string|max:255';
            $validationRules["{$level}_country"] = 'nullable|string|max:100';
            $validationRules["{$level}_start_year"] = 'nullable|integer|min:1900|max:' . date('Y');
            $validationRules["{$level}_completion_year"] = 'nullable|integer|min:1900|max:' . date('Y');
            $validationRules["{$level}_certificate"] = 'nullable|file|mimes:pdf|max:2048';

            if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd'])) {
                $validationRules["{$level}_transcript"] = 'nullable|file|mimes:pdf|max:2048';
            }
        }

        $validatedData = $request->validate($validationRules);

        try {
            DB::beginTransaction();

        // Get user details
        $user = User::findOrFail($id);

        // Handle employee CV upload
        if ($request->hasFile('employee_cv')) {
            $fileName = time() . '_' . $request->file('employee_cv')->getClientOriginalName();
            $filePath = $request->file('employee_cv')->storeAs('uploads/cv', $fileName, 'public');
            $user->employee_cv = $filePath;
        }

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $fileName = time() . '_' . $request->file('profile_picture')->getClientOriginalName();
            $filePath = $request->file('profile_picture')->storeAs('profile_pictures', $fileName, 'public');
            $user->profile_picture = $filePath;
        }

        // Prepare signature data if provided
        $signatureDataToSave = null;
        if ($request->has('signature') && $request->input('signature') !== null && !empty($request->input('signature'))) {
            $signatureData = $request->input('signature');
            // Extract base64 data if it's a data URI
            if (strpos($signatureData, 'data:image') !== false) {
                $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
            }
            $signatureDataToSave = $signatureData;
        }

        // Update user details
        $updateData = [
            'fname' => $request->input('fname'),
            'mname' => $request->input('mname'),
            'lname' => $request->input('lname'),
            'username' => $request->input('username', $user->username),
            'DOB' => $request->input('DOB'),
            'email' => $request->input('email'),
            'gender' => $request->input('gender'),
            'marital_status' => $request->input('marital_status'),
            'nationality' => $request->input('nationality'),
            'religion' => $request->input('religion'),
            'mobile' => $request->input('mobile'),
            'NIN' => $request->input('NIN'),
            'nssf_no' => $request->input('nssf_no'),
            'passport_no' => $request->input('passport_no'),
            'tin_no' => $request->input('tin_no'),
            'deptId' => $request->input('department'),
            'job_title' => $request->input('job_title'),
            'employment_typeId' => $request->input('employment_typeId'),
            'ccbrt_code' => $request->input('ccbrt_code'),
            'professional_reg_number' => $request->input('professional_reg_number'),
            'hec_allocation' => $request->input('hec_allocation'),
            'home_address' => $request->input('home_address'),
            'district' => $request->input('district'),
            'region' => $request->input('region'),
            'place_of_birth' => $request->input('place_of_birth'),
            'house_no' => $request->input('house_no'),
            'street' => $request->input('street'),
            'popular_landmark' => $request->input('popular_landmark'),
            'domicile' => $request->input('domicile'),
            'starting_date' => $request->input('starting_date'),
            'ending_date' => $request->input('ending_date'),
            'approvelocum' => $request->input('approvelocum', false),
            'conflict_officer_role' => $request->input('conflict_officer_role'),
            'officer_details' => $request->input('officer_details'),
            'financial_interest' => $request->input('financial_interest'),
            'financial_details' => $request->input('financial_details'),
            'other_interests' => $request->input('other_interests'),
            'interest_details' => $request->input('interest_details'),
            'primary_employer_ccbrt' => $request->input('primary_employer_ccbrt'),
            'primary_employer_details' => $request->input('primary_employer_details'),
            'court_proceedings' => $request->input('court_proceedings'),
            'court_details' => $request->input('court_details'),
        ];

        foreach ($educationLevels as $level) {
            $updateData["{$level}_institution"] = $request->input("{$level}_institution");
            $updateData["{$level}_country"] = $request->input("{$level}_country");
            $updateData["{$level}_start_year"] = $request->input("{$level}_start_year");
            $updateData["{$level}_completion_year"] = $request->input("{$level}_completion_year");

            if ($request->hasFile("{$level}_certificate")) {
                if ($user->{"{$level}_certificate"}) {
                    Storage::disk('public')->delete($user->{"{$level}_certificate"});
                }
                $updateData["{$level}_certificate"] = $request->file("{$level}_certificate")->store("certificates/{$user->id}", 'public');
            }

            if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd']) && $request->hasFile("{$level}_transcript")) {
                if ($user->{"{$level}_transcript"}) {
                    Storage::disk('public')->delete($user->{"{$level}_transcript"});
                }
                $updateData["{$level}_transcript"] = $request->file("{$level}_transcript")->store("transcripts/{$user->id}", 'public');
            }
        }

        // Add signature to update data if provided
        if ($signatureDataToSave !== null) {
            $updateData['signature'] = $signatureDataToSave;
        }

        // Update user details
        $user->update($updateData);

        // Save user (in case signature was set directly)
        $user->save();

        $contractData = array_filter($request->input('contract', []), fn($value) => $value !== null && $value !== '');
        if (!empty($contractData)) {
            if (!empty($contractData['start_date']) && !empty($contractData['end_date'])) {
                $startDate = Carbon::parse($contractData['start_date'])->startOfDay();
                $endDate = Carbon::parse($contractData['end_date'])->startOfDay();

                if ($endDate->greaterThanOrEqualTo($startDate)) {
                    $totalMonths = $startDate->diffInMonths($endDate);
                    $remainingDays = $startDate->copy()->addMonths($totalMonths)->diffInDays($endDate);
                    $years = intdiv($totalMonths, 12);
                    $months = $totalMonths % 12;
                    $durationParts = [];

                    if ($years > 0) {
                        $durationParts[] = $years . ' ' . Str::plural('year', $years);
                    }

                    if ($months > 0) {
                        $durationParts[] = $months . ' ' . Str::plural('month', $months);
                    }

                    if ($remainingDays > 0 || empty($durationParts)) {
                        $durationParts[] = $remainingDays . ' ' . Str::plural('day', $remainingDays);
                    }

                    $contractData['duration'] = implode(' ', $durationParts);
                }
            }

            $existingContract = Contract::where('user_id', $user->id)->latest('id')->first();

            if ($existingContract) {
                $existingContract->update($contractData);
            } elseif (!empty($contractData['start_date']) && !empty($contractData['end_date'])) {
                $contractTemplateId = ContractTemplate::orderBy('id')->value('id')
                    ?: ContractTemplate::create([
                        'name' => 'Staff Profile Contract',
                        'type' => 'Staff profile contract',
                        'content' => 'Staff profile contract details maintained from the HR staff profile.',
                    ])->id;

                if ($contractTemplateId) {
                    Contract::create(array_merge([
                        'contract_template_id' => $contractTemplateId,
                        'user_id' => $user->id,
                    ], $contractData));
                }
            }
        }

        // Update or create health details
        $healthDetails = HealthDetails::where('userId', $id)->first();
        if ($healthDetails) {
            $healthDetails->update([
                'physical_disability' => $request->input('physical_disability') ?? $healthDetails->physical_disability ?? 'None',
                'blood_group' => $request->input('blood_group'),
                'health_insurance' => $request->input('health_insurance') ?? $healthDetails->health_insurance ?? 'No',
                'insur_name' => $request->input('insur_name'),
                'insur_no' => $request->input('insur_no'),
                'illness_history' => $request->input('illness_history') ?? 'None',
                'allergies' => $request->input('allergies') ?? 'None',
            ]);
        } else {
            HealthDetails::create([
                'userId' => $id,
                'physical_disability' => $request->input('physical_disability') ?? 'None',
                'blood_group' => $request->input('blood_group'),
                'health_insurance' => $request->input('health_insurance') ?? 'No',
                'insur_name' => $request->input('insur_name'),
                'insur_no' => $request->input('insur_no'),
                'illness_history' => $request->input('illness_history') ?? 'None',
                'allergies' => $request->input('allergies') ?? 'None',
                'delete_status' => 0,
            ]);
        }

        // Update language knowledge
        $languageRows = collect($request->input('languages', []))
            ->filter(fn ($langData) => !empty($langData['language']))
            ->values();

        if ($languageRows->isNotEmpty()) {
            $existingLanguageIds = [];
            foreach ($languageRows as $langData) {
                if (isset($langData['id'])) {
                    // Update existing
                    $language = LanguageKnowledge::find($langData['id']);
                    if ($language && $language->userId == $id) {
                        $language->update([
                            'language' => $langData['language'],
                            'speaking' => $langData['speaking'] ?? $language->speaking,
                            'reading' => $langData['reading'] ?? $language->reading,
                            'writing' => $langData['writing'] ?? $language->writing,
                        ]);
                        $existingLanguageIds[] = $langData['id'];
                    }
                } elseif (!empty($langData['speaking']) && !empty($langData['reading']) && !empty($langData['writing'])) {
                    // Create new
                    $newLang = LanguageKnowledge::create([
                        'userId' => $id,
                        'language' => $langData['language'],
                        'speaking' => $langData['speaking'],
                        'reading' => $langData['reading'],
                        'writing' => $langData['writing'],
                        'delete_status' => 0,
                    ]);
                    $existingLanguageIds[] = $newLang->id;
                }
            }
            // Delete languages that were removed
            LanguageKnowledge::where('userId', $id)
                ->whereNotIn('id', $existingLanguageIds)
                ->delete();
        }

        DB::commit();

        Alert::success('User details updated', 'Update successful');

        return redirect()->route('employee.index')->with('success', 'User details updated successfully.');
        } catch (QueryException $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Staff details update database error', [
                'user_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Some staff details could not be saved. Please review the form and try again.',
                ], 422);
            }

            Alert::error('Update failed', 'Some staff details could not be saved. Please review the form and try again.');

            return back()
                ->withInput()
                ->withErrors(['staff_update' => 'Some staff details could not be saved. Please review the form and try again.']);
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Staff details update error', [
                'user_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'An unexpected error occurred while saving staff details. Please try again.',
                ], 500);
            }

            Alert::error('Update failed', 'An unexpected error occurred while saving staff details. Please try again.');

            return back()
                ->withInput()
                ->withErrors(['staff_update' => 'An unexpected error occurred while saving staff details. Please try again.']);
        }
    }


    // public function updateUserDetails(Request $request, $id)
    // {
    //     // Validate incoming request
    //     $validatedData = $request->validate([
    //         'department' => 'required|string|max:255',
    //         'job_title' => 'required|string|max:255',
    //         'employment_typeId' => 'required|integer|exists:employment_types,id',
    //         'ccbrt_code' => 'nullable|regex:/^CCBRT\d{4}$/',
    //     ]);

    //     // Get user details
    //     $user = User::findOrFail($id);

    //     // Update user details
    //     $user->update([
    //         'deptId' => $request->input('department'),
    //         'job_title' => $request->input('job_title'),
    //         'employment_typeId' => $request->input('employment_typeId'), // ✅ New line
    //         'ccbrt_code' => $request->input('ccbrt_code'),
    //     ]);

    //     Alert::success('User details updated', 'Update successful');

    //     return redirect()->route('employee.index')->with('success', 'User details updated successfully.');
    // }


    public function downloadHrForm($id)
    {
        $user = User::where('users.id', $id)->first();
        $department = Departments::all();
        $familyDetails = DB::table('user_family_details')->where('userId', $id)->get();

        $healthDetails = DB::table('health_details')->where('userId', $id)->first();

        $languageKnowledge = DB::table('language_knowledge')->where('userId', $id)->get();

        $relations = DB::table('ccbrt_relations')
            ->join('departments', 'departments.id', '=', 'ccbrt_relations.department')
            ->where('userId', $id)
            ->get();

        return view('employees_details.hr_form', compact('user', 'familyDetails', 'healthDetails', 'languageKnowledge', 'relations'));
    }

    public function downloadBankForm($id)
    {
        $user = User::findOrFail($id);
        // dd(BankDetail::where('userId', $user->id)->first());

        $bankForm = BankDetail::join('users', 'users.id', '=', 'bank_details.userId')
            ->leftJoin('workflows', 'workflows.bank_form', '=', 'bank_details.id')
            ->leftJoin('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->where('bank_details.userId', $user->id)
            // ->where('work_flow_histories.attended_by', $user->id)
            ->first([
                'bank_details.*',
                'users.*',
                'work_flow_histories.*',
                'bank_details.id as access_id',
                'work_flow_histories.forwarded_by'
            ]);
        //   dd($bankForm);
        if (!$bankForm) {
            abort(404, 'Bank form not found for this user.');
        }

        $HrToApprove = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })
            ->where('work_flow_histories.work_flow_id', $bankForm->work_flow_id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'desc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        return view('employees_details.bank_form', compact('user', 'bankForm', 'HrToApprove'));
    }

    public function downloadNhifForm($id)
    {
        $user = User::findOrFail($id);
        $nhifForm = NhifRegistration::join('users', 'users.id', '=', 'nhif_registrations.userId')
            ->join('workflows', 'workflows.nhif_form', '=', 'nhif_registrations.id')
            ->join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->where('nhif_registrations.userId', $user->id)
            //  ->where('work_flow_histories.attended_by', $user->id)
            ->first([
                'nhif_registrations.*',
                'users.*',
                'work_flow_histories.*',
                'nhif_registrations.id as access_id',
                'work_flow_histories.forwarded_by'
            ]);
        // dd($nhifForm);
        $HrToApprove = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })
            ->where('work_flow_histories.work_flow_id', $nhifForm->work_flow_id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'desc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        return view('employees_details.nhif_form', compact('user', 'HrToApprove', 'nhifForm'));
    }
    public function downloadHslbForm($id)
    {
        $user = User::findOrFail($id);
        $heslbForm = LoanDeclaration::join('users', 'users.id', '=', 'loan_declarations.userId')
            ->join('workflows', 'workflows.heslb_form', '=', 'loan_declarations.id')
            ->join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->where('loan_declarations.userId', $user->id)
            //  ->where('work_flow_histories.attended_by', $user->id)
            ->first([
                'loan_declarations.*',
                'users.*',
                'work_flow_histories.*',
                'loan_declarations.id as access_id',
                'work_flow_histories.forwarded_by'
            ]);

        $HrToApprove = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })
            ->where('work_flow_histories.work_flow_id', $heslbForm->work_flow_id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'desc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();


        return view('employees_details.hslb_form', compact('user', 'heslbForm', 'HrToApprove'));
    }

    public function downloadIdForm($id)
    {
        $user = User::findOrFail($id);
        $idForm = IDCards::join('users', 'users.id', '=', 'id_card_requests.user_id')
            ->join('workflows', 'workflows.id_form', '=', 'id_card_requests.id')
            ->join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->where('id_card_requests.userId', $user->id)
            // ->where('work_flow_histories.attended_by', $user->id)
            ->first([
                'id_card_requests.*',
                'users.*',
                'work_flow_histories.*',
                'id_card_requests.id as access_id',
                'work_flow_histories.forwarded_by'
            ]);


        $HrToApprove = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })
            ->where('work_flow_histories.work_flow_id', $idForm->work_flow_id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'desc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();


        return view('employees_details.id_form', compact('user', 'idForm', 'HrToApprove'));
    }

    public function downloadItForm(Request $request, $id)
    {
        // Fetch the user details
        $user = User::findOrFail($id);
        //  dd($user);
        // Fetch the form details with full join
        $ictForm = IctAccessResource::join('users', 'users.id', '=', 'ict_access_resources.userId')
            ->join('workflows', 'workflows.ict_request_resource_id', '=', 'ict_access_resources.id')
            ->join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->join('privilege_levels', 'privilege_levels.id', '=', 'ict_access_resources.privilegeId')
            ->join('nhif_qualifications', 'nhif_qualifications.id', '=', 'ict_access_resources.nhifId')
            ->join('employment_types', 'employment_types.id', '=', 'users.employment_typeId')
            ->join('departments', 'departments.id', '=', 'users.deptId')
            ->join('hecs', 'hecs.id', '=', 'departments.hec_id')
            ->join('h_m_i_s_access_levels', 'h_m_i_s_access_levels.id', '=', 'ict_access_resources.hmisId')
            ->where('ict_access_resources.userId', $user->id)
            ->where('work_flow_histories.attended_by', $user->id)
            ->first([
                'ict_access_resources.*',
                'users.*',
                'workflows.*',
                'work_flow_histories.*',
                'ict_access_resources.id as access_id',
                'privilege_levels.prv_name',
                'nhif_qualifications.name',
                'employment_types.employment_type',
                'departments.dept_name',
                'hecs.hec_level_name',
                'h_m_i_s_access_levels.names',
                'work_flow_histories.forwarded_by'
            ]);

        // dd($ictForm);
        // Check if $ictForm is null
        if (!$ictForm) {
            return redirect()->back()->with('error', 'Form not found or you do not have permission to access it.');
        }

        $approver = null;
        $lineManager = null;

        // Check if the requester is a COO, CFO, or CMS
        if (auth()->user()->hasRole(['coo', 'cfo', 'cms'])) {
            $roleMapping = [
                'COO' => 'coo',
                'CFO' => 'cfo',
                'CMS' => 'cms',
            ];

            $requiredRole = $roleMapping[$ictForm->hec_level_name] ?? null;

            if (!$requiredRole) {
                return redirect()->back()->with('error', 'No matching role found for approval.');
            }

            // Fetch the approver
            $approver = User::join('work_flow_histories', 'work_flow_histories.attended_by', '=', 'users.id')
                ->whereHas('roles', function ($query) use ($requiredRole) {
                    $query->where('name', $requiredRole);
                })
                ->where('work_flow_histories.work_flow_id', $ictForm->work_flow_id)
                ->where('work_flow_histories.status', 1)
                ->orderBy('work_flow_histories.updated_at', 'desc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();
        } else {
            // Fetch the line manager
            $lineManager = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'line-manager');
                })
                ->where('users.deptId', $ictForm->deptId)
                ->where('work_flow_histories.work_flow_id', $ictForm->work_flow_id)
                ->where('work_flow_histories.status', 1)
                ->orderBy('work_flow_histories.updated_at', 'desc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();
        }

        // Fetch HR Officer
        $hrOfficer = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })
            ->where('work_flow_histories.work_flow_id', $ictForm->work_flow_id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        // Fetch IT Officer
        $itOfficer = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'it');
            })
            ->where('work_flow_histories.work_flow_id', $ictForm->work_flow_id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        // Pass all the data to the view
        return view('employees_details.it_form', compact('user', 'ictForm', 'lineManager', 'itOfficer', 'hrOfficer', 'approver'));
    }


    public function downloadExitForm(Request $request, $id)
    {
        $user = User::findOrFail($id);
        //   dd($user);
        $clearance = ClearanceForm::join('users', 'users.id', '=', 'clearance_forms.userId')
            ->join('clearance_work_flows', 'clearance_work_flows.requested_resource_id', '=', 'clearance_forms.id')
            ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
            ->join('departments', 'departments.id', '=', 'users.deptId')
            ->join('job_titles', 'job_titles.id', '=', 'users.job_title')
            ->join('hecs', 'hecs.id', '=', 'departments.hec_id')
            ->where('clearance_forms.userId', $user->id)
            ->where('clearance_work_flow_histories.attended_by', $user->id)

            ->first([
                'clearance_forms.*',
                'users.*',
                'clearance_work_flows.*',
                'departments.dept_name',
                'hecs.hec_level_name',
                'job_titles.job_title',
                'clearance_work_flow_histories.*',
                'clearance_forms.id as access_id',
                'clearance_work_flow_histories.forwarded_by'
            ]);
        //  dd($clearance);
        $approver = null;
        $lineManager = null;

        if (auth()->user()->hasRole(['coo', 'cfo', 'cms'])) {
            $roleMapping = [
                'COO' => 'coo',
                'CFO' => 'cfo',
                'CMS' => 'cms',
            ];
            // Check if the role mapping is working
            $requiredRole = $roleMapping[$clearance->hec_level_name] ?? null;

            if (!$requiredRole) {
                dd('No matching role found for approval');
            }

            // Fetch users with the required role
            $testUsers = User::whereHas('roles', function ($query) use ($requiredRole) {
                $query->where('name', $requiredRole);
            })->get();

            // Fetch the approver
            $approver = User::whereHas('roles', function ($query) use ($requiredRole) {
                $query->where('name', $requiredRole);
            })
                ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
                ->where('clearance_work_flow_histories.work_flow_id', $clearance->work_flow_id)
                ->where('clearance_work_flow_histories.status', 1)
                ->first();

            //dd($approver);

        } else {
            $lineManager = User::whereHas('roles', function ($query) {
                $query->where('name', 'line-manager');
            })
                ->where('users.deptId', $clearance->deptId)
                ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
                ->where('clearance_work_flow_histories.work_flow_id', $clearance->work_flow_id)
                ->where('clearance_work_flow_histories.status', 1)
                ->first();


            //dd($lineManager);

        }


        $financeOfficer = User::whereHas('roles', function ($query) {
            $query->where('name', 'finance officer')
                ->orWhere('name', 'like', 'finance-officer-%');
        })
            ->where('users.deptId', $clearance->deptId)
            ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
            ->where('clearance_work_flow_histories.work_flow_id', $clearance->work_flow_id)
            ->where('clearance_work_flow_histories.status', 1)
            ->first();


        $itOfficer = User::whereHas('roles', function ($query) {
            $query->where('name', 'it');
        })
            ->where('users.deptId', $clearance->deptId)
            ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
            ->where('clearance_work_flow_histories.work_flow_id', $clearance->work_flow_id)
            ->where('clearance_work_flow_histories.status', 1)
            ->first();


        $hrOfficer = User::whereHas('roles', function ($query) {
            $query->where('name', 'hr');
        })
            ->where('users.deptId', $clearance->deptId)
            ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
            ->where('clearance_work_flow_histories.work_flow_id', $clearance->work_flow_id)
            ->where('clearance_work_flow_histories.status', 1)
            ->first();

        return view('employees_details.exit_form', compact('user', 'clearance', 'itOfficer', 'hrOfficer', 'lineManager', 'approver', 'financeOfficer'));
    }


    //education
    public function showEducationDetails()
    {
        $user = Auth::user();
        $education_levels = [
            'primary' => 'Primary Education',
            'o_level' => 'O-Level (Form 4)',
            'a_level' => 'A-Level (Form 6)',
            'certificate' => 'Certificate',
            'diploma' => 'Diploma',
            'degree' => 'Degree',
            'masters' => 'Masters',
            'phd' => 'PhD',
        ];

        return view('education_details.index', compact('user', 'education_levels'));
    }

    public function updateEducationDetails(Request $request)
    {
        $user = Auth::user();
        $levels = ['primary', 'o_level', 'a_level', 'certificate', 'diploma', 'degree', 'masters', 'phd'];

        // Handle deletion
        if ($request->has('delete_level')) {
            $level = $request->delete_level;
            if (in_array($level, $levels)) {
                $data = [
                    "{$level}_institution" => null,
                    "{$level}_country" => null,
                    "{$level}_start_year" => null,
                    "{$level}_completion_year" => null,
                    "{$level}_certificate" => null,
                ];
                if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd'])) {
                    $data["{$level}_transcript"] = null;
                }

                if ($user->{"{$level}_certificate"}) {
                    Storage::disk('public')->delete($user->{"{$level}_certificate"});
                }
                if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd']) && $user->{"{$level}_transcript"}) {
                    Storage::disk('public')->delete($user->{"{$level}_transcript"});
                }

                $user->update($data);
                return redirect()->route('education.details')->with('success', 'Education details deleted.');
            }
        }

        // Validate input
        $level = $request->education_level;
        if (!in_array($level, $levels)) {
            return redirect()->back()->withErrors(['education_level' => 'Invalid education level.'])->withInput();
        }

        $validator = Validator::make($request->all(), [
            "{$level}_institution" => 'required|string|max:255',
            "{$level}_country" => 'required|string|max:100',
            "{$level}_start_year" => 'required|integer|min:1900|max:' . date('Y'),
            "{$level}_completion_year" => 'required|integer|min:1900|max:' . date('Y') . '|gte:' . "{$level}_start_year",
            "{$level}_certificate" => 'nullable|file|mimes:pdf|max:2048',
            "{$level}_transcript" => in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd'])
                ? 'nullable|file|mimes:pdf|max:2048'
                : 'nullable',
        ], [
            "{$level}_institution.required" => 'Institution name is required.',
            "{$level}_country.required" => 'Country is required.',
            "{$level}_start_year.required" => 'Start year is required.',
            "{$level}_completion_year.required" => 'Completion year is required.',
            "{$level}_completion_year.gte" => 'Completion year must be on or after start year.',
            "{$level}_certificate.mimes" => 'Certificate must be a PDF.',
            "{$level}_certificate.max" => 'Certificate must not exceed 2MB.',
            "{$level}_transcript.mimes" => 'Transcript must be a PDF.',
            "{$level}_transcript.max" => 'Transcript must not exceed 2MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('edit_level', $level);
        }

        // Prepare update data
        $data = [
            "{$level}_institution" => $request->{"{$level}_institution"},
            "{$level}_country" => $request->{"{$level}_country"},
            "{$level}_start_year" => $request->{"{$level}_start_year"},
            "{$level}_completion_year" => $request->{"{$level}_completion_year"},
        ];

        // Handle certificate upload
        if ($request->hasFile("{$level}_certificate")) {
            if ($user->{"{$level}_certificate"}) {
                Storage::disk('public')->delete($user->{"{$level}_certificate"});
            }
            $data["{$level}_certificate"] = $request->file("{$level}_certificate")->store("certificates/{$user->id}", 'public');
        }

        // Handle transcript upload
        if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd']) && $request->hasFile("{$level}_transcript")) {
            if ($user->{"{$level}_transcript"}) {
                Storage::disk('public')->delete($user->{"{$level}_transcript"});
            }
            $data["{$level}_transcript"] = $request->file("{$level}_transcript")->store("transcripts/{$user->id}", 'public');
        }

        $user->update($data);

        return redirect()->route('education.details')->with('success', 'Education details updated.');
    }
    public function fetch()
    {
        $userLastViewed = auth()->user()->last_viewed_announcement;
        $newCount = Announcement::when($userLastViewed, function ($query, $userLastViewed) {
            return $query->where('created_at', '>', $userLastViewed);
        })->count();
        return response()->json(['newCount' => $newCount]);
    }
    public function deactivate($id)
    {
        $user = User::findOrFail($id);

        // Prevent deactivating super-admin users
        if ($user->hasRole('super-admin')) {
            return redirect()->back()->with('error', 'Cannot deactivate a user with super-admin role.');
        }

        if ($user->status === 'deactivated') {
            return redirect()->back()->with('warning', 'User is already deactivated.');
        }

        $oldStatus = $user->status;
        $user->status = 'deactivated';
        $user->save();


        return redirect()->back()->with('success', 'User has been deactivated successfully.');
    }

    public function activate($id)
    {
        $user = User::findOrFail($id);

        if ($user->status === 'active') {
            return redirect()->back()->with('warning', 'User is already active.');
        }

        $oldStatus = $user->status;
        $user->status = 'active';
        $user->save();


        return redirect()->back()->with('success', 'User has been activated successfully.');
    }

    public function updateOnCallRates(Request $request, $id)
    {
        // Check if user has permission
        if (!Auth::user()->hasAnyRole(['hr', 'super-admin', 'admin'])) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        $user = User::findOrFail($id);

        // Validate the request
        $validated = $request->validate([
            'oncall_rates' => 'nullable|array',
            'oncall_rates.*' => 'exists:on_call_rates,id',
        ]);

        // Sync the assigned rates
        $user->onCallRates()->sync($validated['oncall_rates'] ?? []);

        return redirect()->back()->with('success', 'On-call rates updated successfully.');
    }
}
