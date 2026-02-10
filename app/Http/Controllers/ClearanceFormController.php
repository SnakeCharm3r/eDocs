<?php
namespace App\Http\Controllers;

use App\Mail\ApprovalRequestNotification;
use App\Models\Clearance_work_flow;
use App\Models\Clearance_work_flow_history;
use App\Models\ClearanceForm;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;

class ClearanceFormController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userRoles = $user->getRoleNames();
        $isLineManager = $userRoles->contains('line-manager');
        $isHR = $userRoles->contains('hr');
        $isFinanceOfficer = $userRoles->contains('finance officer');
        $isITOfficer = $userRoles->contains('it');
        
        // Get clearance forms - either own forms, forms created by line manager for staff, or forms for HR/Finance Officer/IT Officer to approve
        $clearance = null;
        $staffClearances = collect([]);
        $hrClearances = collect([]);
        $financeClearances = collect([]);
        $itClearances = collect([]);
        
        // Get user's own clearance forms
        $ownClearance = ClearanceForm::where('userId', $user->id)
            ->orderByRaw("CASE WHEN status = 'rejected' THEN 1 ELSE 0 END") // Non-rejected first
            ->orderBy('created_at', 'desc') // Then by most recent
            ->first();
        
        // If line manager, get forms they created for their staff AND forms pending their approval
        if ($isLineManager) {
            // 1. Get workflow IDs where this line manager created the form (Line Manager Submission step)
            // This identifies forms that the line manager created for their staff
            $createdWorkflowIds = DB::table('clearance_work_flow_histories')
                ->where('forwarded_by', $user->id)
                ->where('step_name', 'Line Manager Submission')
                ->where('status', 1)
                ->distinct()
                ->pluck('work_flow_id')
                ->toArray();
            
            // 2. Get workflow IDs where forms are pending this line manager's approval
            // This identifies forms created by employees that need line manager approval
            $pendingApprovalWorkflowIds = Clearance_work_flow_history::where('attended_by', $user->id)
                ->where('step_name', 'Line Manager')
                ->where('status', 0) // Pending approval
                ->whereIn('id', function($subquery) use ($user) {
                    // Get only the latest pending record for each workflow
                    $subquery->selectRaw('MAX(id)')
                        ->from('clearance_work_flow_histories')
                        ->where('status', 0)
                        ->where('attended_by', $user->id)
                        ->where('step_name', 'Line Manager')
                        ->groupBy('work_flow_id');
                })
                ->whereHas('workflow', function($query) {
                    // Only show if workflow is not completed (still in process)
                    $query->where(function($q) {
                        $q->where('work_flow_completed', '!=', 1)
                          ->orWhereNull('work_flow_completed');
                    });
                })
                ->pluck('work_flow_id')
                ->toArray();
            
            // Combine both sets of workflow IDs
            $allWorkflowIds = array_unique(array_merge($createdWorkflowIds, $pendingApprovalWorkflowIds));
            
            // Get clearance forms for those workflows
            if (!empty($allWorkflowIds)) {
                $staffClearances = ClearanceForm::whereHas('workflow', function($query) use ($allWorkflowIds) {
                    $query->whereIn('id', $allWorkflowIds);
                })
                ->where('userId', '!=', $user->id) // Exclude own forms
                ->where('status', '!=', 'rejected') // Exclude rejected forms
                ->with(['user', 'workflow'])
                ->orderBy('created_at', 'desc')
                ->get();
            } else {
                $staffClearances = collect([]);
            }
        }
        
        // If HR, get all clearance forms assigned to them (pending and approved)
        if ($isHR) {
            $hrClearances = ClearanceForm::whereHas('workflow.histories', function($query) use ($user) {
                $query->where('attended_by', $user->id);
            })
            ->where('status', '!=', 'rejected') // Exclude rejected forms
            ->with(['user', 'workflow'])
            ->orderBy('created_at', 'desc')
            ->distinct()
            ->get();
        }
        
        // If Finance Officer, get clearance forms currently pending at Finance Officer step
        if ($isFinanceOfficer) {
            // Get workflow IDs that have pending Finance Officer step assigned to this user
            $pendingWorkflowIds = Clearance_work_flow_history::where('attended_by', $user->id)
                ->where('step_name', 'Finance Officer')
                ->where('status', 0)
                ->whereIn('id', function($subquery) use ($user) {
                    // Get only the latest pending record for each workflow
                    $subquery->selectRaw('MAX(id)')
                        ->from('clearance_work_flow_histories')
                        ->where('status', 0)
                        ->where('attended_by', $user->id)
                        ->where('step_name', 'Finance Officer')
                        ->groupBy('work_flow_id');
                })
                ->pluck('work_flow_id');
            
            // Get clearance forms for those workflows
            $financeClearances = ClearanceForm::whereHas('workflow', function($query) use ($pendingWorkflowIds) {
                $query->whereIn('id', $pendingWorkflowIds)
                      ->where(function($q) {
                          $q->where('work_flow_completed', '!=', 1)
                            ->orWhereNull('work_flow_completed');
                      });
            })
            ->where('status', '!=', 'rejected') // Exclude rejected forms
            ->with(['user', 'workflow'])
            ->orderBy('created_at', 'desc')
            ->get();
        }
        
        // If IT Officer, get clearance forms currently pending at IT Officer step
        if ($isITOfficer) {
            // Get workflow IDs that have pending IT Officer step assigned to this user
            $pendingWorkflowIds = Clearance_work_flow_history::where('attended_by', $user->id)
                ->where('step_name', 'IT Officer')
                ->where('status', 0)
                ->whereIn('id', function($subquery) use ($user) {
                    // Get only the latest pending record for each workflow
                    $subquery->selectRaw('MAX(id)')
                        ->from('clearance_work_flow_histories')
                        ->where('status', 0)
                        ->where('attended_by', $user->id)
                        ->where('step_name', 'IT Officer')
                        ->groupBy('work_flow_id');
                })
                ->pluck('work_flow_id');
            
            // Get clearance forms for those workflows
            $itClearances = ClearanceForm::whereHas('workflow', function($query) use ($pendingWorkflowIds) {
                $query->whereIn('id', $pendingWorkflowIds)
                      ->where(function($q) {
                          $q->where('work_flow_completed', '!=', 1)
                            ->orWhereNull('work_flow_completed');
                      });
            })
            ->where('status', '!=', 'rejected') // Exclude rejected forms
            ->with(['user', 'workflow'])
            ->orderBy('created_at', 'desc')
            ->get();
        }
        
        // Set primary clearance (own form if exists, otherwise null)
        $clearance = $ownClearance;
        
        // Get workflow information for own clearance
        $workflow = null;
        $currentApprover = null;
        $currentStep = null;
        
        if ($clearance) {
            $workflow = Clearance_work_flow::where('requested_resource_id', $clearance->id)->first();
            
            // Get current approver information (only if not rejected)
            if ($workflow && $clearance->status !== 'rejected') {
                $currentHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                    ->where('status', 0)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($currentHistory) {
                    $currentApprover = User::find($currentHistory->attended_by);
                    $currentStep = $currentHistory->step_name;
                }
            }
        }
        
        return view('clearance.index', compact('user', 'clearance', 'workflow', 'currentApprover', 'currentStep', 'staffClearances', 'hrClearances', 'financeClearances', 'itClearances', 'isLineManager', 'isHR', 'isFinanceOfficer', 'isITOfficer'));
    }

    public function create()
    {
        $user = Auth::user();
        $isLineManager = $user->hasRole('line-manager');
        $isRequester = $user->hasRole('requester');
        
        // Check if user has "requester" role OR is a line manager
        if (!$isRequester && !$isLineManager) {
            Alert::error('Access Denied', 'Only users with the "requester" role or line managers can create clearance forms.');
            return redirect()->route('clearance.index');
        }
        
        // If requester (not line manager), check for existing form
        if ($isRequester && !$isLineManager) {
            $existingClearance = ClearanceForm::where('userId', $user->id)
                ->whereIn('status', ['pending', 'approved'])
                ->first();
            
            if ($existingClearance) {
                Alert::info('Clearance Form Exists', 'You already have a clearance form in progress. Please check your clearance status.');
                return redirect()->route('clearance.index');
            }
        }
        
        // If line manager, get active employees from their department
        $employees = collect([]);
        if ($isLineManager && $user->deptId) {
            $employees = User::where('deptId', $user->deptId)
                ->where('id', '!=', $user->id) // Exclude line manager themselves
                ->where('status', '=', 'active') // Only show active employees
                ->with('department', 'jobTitle')
                ->orderBy('fname')
                ->orderBy('lname')
                ->get();
        }
        
        return view('clearance.create', compact('user', 'isLineManager', 'employees'));
    }

    public function getUserDetails($id)
    {
        try {
            $user = User::with('department', 'jobTitle')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'user' => [
                    'fname' => $user->fname,
                    'mname' => $user->mname,
                    'lname' => $user->lname,
                    'emp_id' => $user->emp_id,
                    'ccbrt_code' => $user->ccbrt_code,
                    'username' => $user->username,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'starting_date' => $user->starting_date ? \Carbon\Carbon::parse($user->starting_date)->format('Y-m-d') : null,
                    'ending_date' => $user->ending_date ? \Carbon\Carbon::parse($user->ending_date)->format('Y-m-d') : null,
                    'department' => $user->department ? ['dept_name' => $user->department->dept_name] : null,
                    'job_title' => $user->jobTitle ? ['job_title' => $user->jobTitle->job_title] : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    public function review(Request $request)
    {
        try {
            $user = Auth::user();
            $isLineManager = $user->hasRole('line-manager');
            $isRequester = $user->hasRole('requester');
            
            // Check if user has "requester" role OR is a line manager
            if (!$isRequester && !$isLineManager) {
                Alert::error('Access Denied', 'Only users with the "requester" role or line managers can create clearance forms.');
                return redirect()->route('clearance.index');
            }
            
            $formData = [];
            
            // If GET request, try to get from old input (from error redirect)
            if ($request->isMethod('get')) {
                $formData = $request->old();
                if (is_array($formData)) {
                    unset($formData['_token']);
                }
            } else {
                // Validate the form data for POST requests
                $validated = $request->validate([
                    'userId' => 'required|exists:users,id',
                    'date_of_hire' => 'required|date',
                    'end_of_contract' => [
                        'required',
                        'date',
                        function ($attribute, $value, $fail) use ($request) {
                            $dateOfHire = $request->input('date_of_hire');
                            if ($dateOfHire && $value) {
                                $hireDate = \Carbon\Carbon::parse($dateOfHire)->startOfDay();
                                $endOfContractDate = \Carbon\Carbon::parse($value)->startOfDay();
                                
                                if ($endOfContractDate->lt($hireDate)) {
                                    $fail('The end of contract must be on or after the date of hire.');
                                }
                            }
                        },
                    ],
                    'exit_reason' => 'nullable|array',
                    'exit_reason.*' => 'nullable|string',
                    'exit_explanation' => 'nullable|string|max:2000',
                    'suggestions' => 'nullable|string|max:2000',
                    'would_recommend' => 'nullable|string|max:2000',
                ], [
                    'userId.required' => 'User ID is required.',
                    'userId.exists' => 'Invalid user selected.',
                    'date_of_hire.required' => 'Date of hire is required.',
                    'date_of_hire.date' => 'Date of hire must be a valid date.',
                    'end_of_contract.required' => 'End of contract is required.',
                    'end_of_contract.date' => 'End of contract must be a valid date.',
                ]);
                
                // Use validated data (this ensures we have all required fields)
                $formData = $validated;
                
                // Include Line Manager fields if present (for line managers creating forms for staff)
                // Always include these fields even if empty, so they're available in review page
                $lineManagerFields = [
                    'changing_room_keys',
                    'office_keys',
                    'mobile_phone',
                    'camera',
                    'ccbrt_uniforms',
                    'office_car_keys',
                    'other_items',
                    'handover_report_received',
                    'work_responsibilities_transferred',
                    'projects_tasks_closed',
                    'line_manager_comments'
                ];
                
                foreach ($lineManagerFields as $field) {
                    // Always include the field, use input value or default
                    if ($field === 'other_items' || $field === 'line_manager_comments') {
                        // Text fields can be empty strings
                        $formData[$field] = $request->input($field, '');
                    } else {
                        // Select fields - get the value or default to 'N/A' for items, 'No' for review fields
                        $defaultValue = in_array($field, ['handover_report_received', 'work_responsibilities_transferred', 'projects_tasks_closed']) ? 'No' : 'N/A';
                        $formData[$field] = $request->input($field, $defaultValue);
                    }
                }
                
                // Log Line Manager fields for debugging
                \Log::info('Review method - Line Manager fields collected', [
                    'line_manager_fields' => array_intersect_key($formData, array_flip($lineManagerFields)),
                    'is_line_manager' => $user->hasRole('line-manager'),
                    'requesting_for_staff' => isset($formData['userId']) && $formData['userId'] != $user->id
                ]);
            }
            
            // Ensure userId is set (use authenticated user's ID as fallback)
            if (!isset($formData['userId']) || empty($formData['userId'])) {
                $formData['userId'] = $user->id;
            }
            
            // Log final formData for debugging
            \Log::info('Review method - final formData', [
                'formData' => $formData,
                'request_method' => $request->method()
            ]);
            
            // Validate that we have all required fields
            if (empty($formData['date_of_hire']) || empty($formData['end_of_contract'])) {
                \Log::warning('Review method - missing required fields, redirecting to create', ['formData' => $formData]);
                return redirect()->route('clearance.create')
                    ->with('error', 'Please fill in the form first.')
                    ->withInput();
            }
            
            // Pass data to review view
            return view('clearance.review', [
                'user' => $user,
                'formData' => $formData
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('clearance.create')
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Please correct the errors below and try again.');
        } catch (\Exception $e) {
            \Log::error('Error in review method: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            Alert::error('Error', 'An error occurred while processing your request. Please try again.');
            return redirect()->route('clearance.create')
                ->withInput()
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    public function store(Request $request)
    {
        try {
            // Log the incoming request data for debugging
            \Log::info('Clearance form store request', [
                'all_data' => $request->all(),
                'userId' => $request->input('userId'),
                'date_of_hire' => $request->input('date_of_hire'),
                'last_working_day' => $request->input('last_working_day'),
                'reason_for_leaving' => $request->input('reason_for_leaving'),
                'line_manager_fields' => [
                    'changing_room_keys' => $request->input('changing_room_keys'),
                    'office_keys' => $request->input('office_keys'),
                    'mobile_phone' => $request->input('mobile_phone'),
                    'camera' => $request->input('camera'),
                    'ccbrt_uniforms' => $request->input('ccbrt_uniforms'),
                    'office_car_keys' => $request->input('office_car_keys'),
                    'other_items' => $request->input('other_items'),
                    'handover_report_received' => $request->input('handover_report_received'),
                    'work_responsibilities_transferred' => $request->input('work_responsibilities_transferred'),
                    'projects_tasks_closed' => $request->input('projects_tasks_closed'),
                    'line_manager_comments' => $request->input('line_manager_comments'),
                ],
            ]);
            
            $currentUser = Auth::user();
            $userRoles = $currentUser->getRoleNames();
            $isLineManager = $currentUser->hasRole('line-manager');
            $isRequester = $currentUser->hasRole('requester');
            
            // Check if user has "requester" role OR is a line manager
            if (!$isRequester && !$isLineManager) {
                Alert::error('Access Denied', 'Only users with the "requester" role or line managers can create clearance forms.');
                return redirect()->back()->withInput()->withErrors(['error' => 'Only users with the "requester" role or line managers can create clearance forms.']);
            }
            
            // Determine target user
            if ($isLineManager) {
                // Line manager is creating form for an employee
                $targetUserId = $request->input('userId');
                if (!$targetUserId || $targetUserId == $currentUser->id) {
                    Alert::error('Error', 'Please select an employee to create a clearance form for.');
                    return redirect()->back()->withInput()->withErrors(['error' => 'Please select an employee.']);
                }
                $targetUser = User::find($targetUserId);
                if (!$targetUser) {
                    Alert::error('Error', 'Selected employee not found.');
                    return redirect()->back()->withInput()->withErrors(['error' => 'Selected employee not found.']);
                }
                // Verify employee is in same department and has active status
                if ($targetUser->deptId != $currentUser->deptId) {
                    Alert::error('Error', 'You can only create clearance forms for employees in your department.');
                    return redirect()->back()->withInput()->withErrors(['error' => 'Employee must be in your department.']);
                }
                if ($targetUser->status !== 'active') {
                    Alert::error('Error', 'You can only create clearance forms for employees with active status.');
                    return redirect()->back()->withInput()->withErrors(['error' => 'Employee must have active status.']);
                }
            } else {
                // Requester is creating form for themselves
                $targetUserId = $currentUser->id;
                $targetUser = $currentUser;
                $targetUserRoles = $targetUser->getRoleNames();
                
                // Role restrictions: These roles cannot request exit forms for themselves
                $restrictedRoles = ['hec member', 'hr', 'finance officer', 'it'];
                $hasRestrictedRole = $targetUserRoles->intersect($restrictedRoles)->isNotEmpty();
                
                if ($hasRestrictedRole) {
                    Alert::error('Access Denied', 'You cannot request an exit form for yourself. HEC Members, HR, Finance Officers, and IT Officers are not allowed to request exit forms for themselves.');
                    return redirect()->back()->withInput()->withErrors(['error' => 'You cannot request an exit form for yourself.']);
                }
            }
            
            // Ensure userId is set
            $request->merge(['userId' => $targetUserId]);
            
            // Validate basic employee information
            // Exit interview questions are only required if NOT created by line manager
            $validationRules = [
                'userId' => 'required|exists:users,id',
                'date_of_hire' => 'required|date',
                'end_of_contract' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) use ($request) {
                        $dateOfHire = $request->input('date_of_hire');
                        if ($dateOfHire && $value) {
                            $hireDate = \Carbon\Carbon::parse($dateOfHire)->startOfDay();
                            $endOfContractDate = \Carbon\Carbon::parse($value)->startOfDay();
                            
                            if ($endOfContractDate->lt($hireDate)) {
                                $fail('The end of contract must be on or after the date of hire.');
                            }
                        }
                    },
                ],
            ];
            
            // Only require exit interview questions if NOT created by line manager
            if (!$isLineManager) {
                $validationRules['exit_reason'] = 'nullable|array';
                $validationRules['exit_reason.*'] = 'nullable|string';
                $validationRules['exit_explanation'] = 'nullable|string|max:2000';
                $validationRules['suggestions'] = 'nullable|string|max:2000';
                $validationRules['would_recommend'] = 'nullable|string|max:2000';
            }
            
            $validated = $request->validate($validationRules, [
                'userId.required' => 'User ID is required.',
                'userId.exists' => 'Invalid user selected.',
                'date_of_hire.required' => 'Date of hire is required.',
                'date_of_hire.date' => 'Date of hire must be a valid date.',
                'end_of_contract.required' => 'End of contract is required.',
                'end_of_contract.date' => 'End of contract must be a valid date.',
            ]);

            // Check if user already has a pending or approved clearance form (but allow new form if rejected)
            // Check if status column exists before using it
            $existingClearanceQuery = ClearanceForm::where('userId', $request->input('userId'));
            
            // Check if status column exists in the table
            if (Schema::hasColumn('clearance_forms', 'status')) {
                $existingClearance = $existingClearanceQuery
                    ->whereIn('status', ['pending', 'approved'])
                    ->first();
            } else {
                // If status column doesn't exist, just check if any form exists
                $existingClearance = $existingClearanceQuery->first();
            }
            
            if ($existingClearance) {
                Alert::warning('Clearance Form Already Exists', 'You already have a clearance form in progress. Please check your clearance status.');
                return redirect()->route('clearance.index')->withInput();
            }

            // Start database transaction
            DB::beginTransaction();
            // Only save basic employee information
            // All other sections will be filled by approvers during the approval process
            $clearance = new ClearanceForm();
            $clearance->userId = $request->input('userId');
            $clearance->status = 'pending';
            $clearance->date_of_hire = $request->input('date_of_hire');
            $clearance->last_working_day = $request->input('end_of_contract'); // Keep field name for backward compatibility
            $clearance->end_of_contract = $request->input('end_of_contract');
            
            // Save exit interview questions only if NOT created by line manager
            if (!$isLineManager) {
                if ($request->has('exit_reason')) {
                    $clearance->exit_reason = json_encode($request->input('exit_reason'));
                }
                $clearance->exit_explanation = $request->input('exit_explanation');
                $clearance->suggestions = $request->input('suggestions');
                $clearance->would_recommend = $request->input('would_recommend');
            }
            
            // All other fields remain null and will be filled by approvers
            $clearance->save();
            
            // Determine workflow based on who created the form
            if ($isLineManager) {
                // Line Manager created form - workflow starts with Line Manager approval
                $workflow = $this->saveClearanceWorkflow([
                    'user_id' => $targetUserId,
                    'requested_resource_id' => $clearance->id,
                    'work_flow_status' => 'Pending Approval - Line Manager',
                    'work_flow_completed' => 0,
                ]);

                // Create initial workflow history (submitted by line manager)
                $this->saveClearanceWorkflowHistory([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $currentUser->id,
                    'attended_by' => $currentUser->id,
                    'status' => '1',
                    'remark' => 'Clearance Form Created by Line Manager',
                    'step_name' => 'Line Manager Submission',
                    'attend_date' => Carbon::now()->format('Y-m-d'),
                    'parent_id' => null,
                ]);

                // Forward to Line Manager (themselves) for approval
                $this->forwardClearanceWorkflowHistory([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $currentUser->id,
                    'attended_by' => $currentUser->id, // Line manager approves their own section
                    'status' => '0', // Pending approval
                    'remark' => 'Pending Line Manager review and approval',
                    'step_name' => 'Line Manager',
                    'attend_date' => Carbon::now()->format('Y-m-d'),
                    'parent_id' => $workflow->id,
                ]);
                
                // Send email to Line Manager (themselves) for approval
                try {
                    Mail::to($currentUser->email)->queue(new \App\Mail\ClearanceFormApprovalRequest(
                        clearanceForm: $clearance,
                        workflow: $workflow,
                        submitter: $targetUser,
                        approver: $currentUser,
                        stepName: 'Line Manager'
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send email to Line Manager', ['error' => $e->getMessage()]);
                }
            } else {
                // Normal workflow: Employee created form - Start with Line Manager
                // Find Line Manager for initial approval
                $lineManager = $this->findLineManagerForRequesterDepartment();
                
                if (!$lineManager) {
                    DB::rollBack();
                    Alert::error('Error', 'No Line Manager found for your department. Please contact the administrator to assign a Line Manager before submitting clearance forms.');
                    return redirect()->back()->withInput()->withErrors(['error' => 'No Line Manager found for your department.']);
                }

                $workflow = $this->saveClearanceWorkflow([
                    'user_id' => $targetUserId,
                    'requested_resource_id' => $clearance->id,
                    'work_flow_status' => 'Pending Approval - Line Manager',
                    'work_flow_completed' => 0,
                ]);

                // Create initial workflow history (submitted by employee)
                $this->saveClearanceWorkflowHistory([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => Auth::user()->id,
                    'attended_by' => Auth::user()->id,
                    'status' => '1',
                    'remark' => 'Clearance Form Submitted',
                    'step_name' => 'Employee Submission',
                    'attend_date' => Carbon::now()->format('Y-m-d'),
                    'parent_id' => null,
                ]);

                // Forward to Line Manager (first approver)
                $this->forwardClearanceWorkflowHistory([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => Auth::user()->id,
                    'attended_by' => $lineManager->id,
                    'status' => '0',
                    'remark' => 'Forwarded for Line Manager approval',
                    'step_name' => 'Line Manager',
                    'attend_date' => Carbon::now()->format('Y-m-d'),
                    'parent_id' => $workflow->id,
                ]);

                // Send email to Line Manager
                try {
                    Mail::to($lineManager->email)->queue(new \App\Mail\ClearanceFormApprovalRequest(
                        clearanceForm: $clearance,
                        workflow: $workflow,
                        submitter: Auth::user(),
                        approver: $lineManager,
                        stepName: 'Line Manager'
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send email to Line Manager', ['error' => $e->getMessage()]);
                    // Continue even if email fails
                }
            }

            DB::commit();
            
            // Send success message and redirect
            if ($isLineManager) {
                Alert::success('Clearance Form Created Successfully', 'The clearance form has been created and is now pending your review and approval.');
            } else {
                Alert::success('Clearance Form Submitted Successfully', 'Your clearance form has been submitted and sent to Line Manager for approval.');
            }
            return redirect()->route('clearance.index')->with('success', 'Clearance form submitted successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $errorMessages = $e->errors();
            \Log::warning('Validation error storing clearance form', [
                'errors' => $errorMessages,
                'validator' => $e->validator ? 'exists' : 'null'
            ]);
            // Use validator instance for proper error bag
            return redirect()->route('clearance.review')
                ->withErrors($e->validator ?? $errorMessages)
                ->withInput()
                ->with('error', 'Please correct the errors below and try again.');
        } catch (\Exception $e) {
            // Handle other errors
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Log::error('Error storing clearance form: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            Alert::error('Failed to Submit Clearance Form', 'An error occurred while submitting your clearance form: ' . $e->getMessage());
            return redirect()->route('clearance.review')
                ->withInput()
                ->with('error', 'Failed to process request: ' . $e->getMessage());
        }
    }

    public function saveClearanceWorkflow($input)
    {
        // dd($input);
        return Clearance_work_flow::create($input);
    }


    public function saveClearanceWorkflowHistory($input)
    {
        return Clearance_work_flow_history::create($input);
    }

       // Method to find Line Manager for Requester department
       public function findLineManagerForRequesterDepartment()
       {
           try {
                  // Get the role name of the requester (Line Manager)
               $requesterRole = Auth::user()->getRoleNames()->first();
                 //dd( $requesterRole);
               if ($requesterRole === 'line-manager') {
                   // Get the department and hec_id of the Line Manager
                   $lineManager = Auth::user();
                   //dd( $lineManager);
                   $lineManagerDepartment = $lineManager->department;
                  // dd($lineManagerDepartment);
                   $hec_id = $lineManagerDepartment->hec_id;
                    // dd($hec_id);
                   // tafuta hec level name sahihi (COO, CFO, CMS, CRHDO)
                   $hec = $lineManagerDepartment->hec;
                      //dd($hec);
                   if ($hec) {
                       //ringaniche hec_level na role husika
                       $approverRoleName = null;

                       // check role na zipe uwezo kulingana heck_level
                       switch ($hec->hec_level_name) {
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
                               throw new \Exception('No valid hec_level_name found for this department.');
                       }
                        //    dd($approverRoleName);
                       // Find the approver with the relevant role
                       $approver = User::role($approverRoleName)->first();
                        //    dd($approver);
                       if ($approver) {
                           return $approver;
                       } else {
                           throw new \Exception('No approver found for the requested role.');
                       }
                   } else {
                       throw new \Exception('No matching hec_id found for this department.');
                   }
               } else {
                      // Role name of the Line Manager (approver role)
                   $approverRoleName = 'line-manager';

                      // Department ID of the Requester
                      $requesterDepartmentId = Auth::user()->deptId; // Get the department ID of the requester

                      // Query to find the Line Manager in the same department as the requester
                   $approver = User::role($approverRoleName)
                          ->where('deptId', $requesterDepartmentId) // Look for line-manager in the same department
                       ->first();

                      // If no approver found, throw an exception
                   if (!$approver) {
                       throw new \Exception('Line Manager for Requester department not found or unauthorized');
                   }

                   return $approver;
               }

           } catch (\Exception $e) {
                  // Log the error for debugging purposes
               \Log::error('Error finding approver: ' . $e->getMessage());

                  // Rethrow the exception for further handling
               throw $e;
           }
       }


public function forwardClearanceWorkflowHistory ($input){
    return Clearance_work_flow_history::create($input);
}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $clearForm = ClearanceForm::findOrFail($id);
        return view('clearance.show', compact('clearForm'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($workflowId)
    {
        $workflow = Clearance_work_flow::findOrFail($workflowId);

        $clear = $workflow->clearForm;
        if(!$clear){
            Alert::error('Clearance form not found', 'Error');
            return redirect()->route('request.index')->with('error', 'Clearance form not found');
        }
        return view('clearance.edit', compact('clearanceForm'));
    }

    /**
     * Update the specified resource in storage.
     */


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClearanceForm $clearanceForm)
    {
        $clearanceForm->delete();

        // Redirect back with a success message
        return redirect()->route('clearance.index')->with('success', 'Form deleted successfully.');
    }

    //start of exit approve
    public function approveForm($id)
    {
        $form = ClearanceForm::findOrFail($id);
        $form->status = 'approved';
        $form->save();

        return redirect()->route('exit_forms.approvers')->with('success', 'Form approved successfully.');
    }
    public function getForm($id)
    {
        $form = ClearanceForm::findOrFail($id);
        return view('exit_forms.show', compact('form'));
    }

    public function getApprover()
    {
        $forms = ClearanceForm::where('status', 'pending')->get();
        return view('exit_forms.approvers', compact('forms'));
    }

    public function rejectForm(Request $request, $id)
    {
        DB::beginTransaction();
        
        try {
            $workflow = Clearance_work_flow::where('requested_resource_id', $id)->first();
            
            if (!$workflow) {
                DB::rollBack();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Clearance workflow not found.',
                        'error' => 'Clearance workflow not found.'
                    ], 404);
                }
                Alert::error('Error', 'Clearance workflow not found.');
                return redirect()->back()->with('error', 'Clearance workflow not found.');
            }

            // Find the current workflow history
            $workflowHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->where('attended_by', Auth::id())
                ->first();

            if (!$workflowHistory) {
                DB::rollBack();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No pending approval found for you. This form may have already been processed.',
                        'error' => 'No pending approval found for you.'
                    ], 400);
                }
                Alert::error('Error', 'No pending approval found for you.');
                return redirect()->back()->with('error', 'No pending approval found for you.');
            }

            // Validate rejection reason
            $request->validate([
                'reason' => 'required|string|min:10|max:1000'
            ], [
                'reason.required' => 'Please provide a reason for rejection.',
                'reason.min' => 'Rejection reason must be at least 10 characters.',
                'reason.max' => 'Rejection reason must not exceed 1000 characters.'
            ]);

            // Mark workflow as rejected
            $workflow->work_flow_status = 'Rejected';
            $workflow->work_flow_completed = 0;
            $workflow->save();

            // Update workflow history
            $workflowHistory->status = 2; // 2 = rejected
            $workflowHistory->who_approve = Auth::id();
            $workflowHistory->remark = 'Rejected: ' . $request->input('reason');
            $workflowHistory->save();

            // Update clearance form
            $form = ClearanceForm::findOrFail($id);
            $form->status = 'rejected';
            $form->rejection_reason = $request->input('reason');
            $form->save();

            // Get submitter for email
            $submitter = $form->user ?? User::find($workflow->user_id);

            // Send rejection email to submitter
            try {
                Mail::to($submitter->email)->queue(new \App\Mail\ClearanceFormStatusUpdate(
                    clearanceForm: $form,
                    workflow: $workflow,
                    submitter: $submitter,
                    status: 'rejected',
                    message: "Your clearance form has been rejected by " . Auth::user()->fname . " " . Auth::user()->lname . ". Reason: " . $request->input('reason'),
                    approver: Auth::user()
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to send rejection email', ['error' => $e->getMessage()]);
            }

            DB::commit();
            
            $successMsg = 'Clearance form has been rejected successfully. The employee has been notified.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'redirect' => route('clearance.index')
                ], 200);
            }
            
            Alert::success('Rejected', $successMsg);
            return redirect()->route('clearance.index')->with('success', $successMsg);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error rejecting clearance form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'form_id' => $id
            ]);
            
            $errorMsg = 'An error occurred while rejecting the clearance form: ' . $e->getMessage();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'error' => $e->getMessage()
                ], 500);
            }
            
            Alert::error('Error', $errorMsg);
            return redirect()->back()->with('error', $errorMsg);
        }
    }





}
