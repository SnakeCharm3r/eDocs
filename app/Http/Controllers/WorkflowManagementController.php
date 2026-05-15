<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use App\Models\Clearance_work_flow;
use App\Models\Clearance_work_flow_history;
use App\Models\User;
use App\Models\OnCallRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class WorkflowManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role_or_permission:coo|super-admin|manage workflows');
    }

    /**
     * Display all workflows (including clearance workflows)
     */
    public function index(Request $request)
    {
        // Get regular workflows - optimize to prevent memory issues
        $workflowQuery = Workflow::with([
            'user:id,fname,lname,username,deptId,status',
            'user.department:id,dept_name',
            'user.roles:id,name',
            'workflowHistory' => function ($query) {
                // Limit history records and only load necessary columns
                $query->with([
                    'attendedBy:id,fname,lname,username,deptId,status',
                    'attendedBy.department:id,dept_name',
                    'attendedBy.roles:id,name',
                    'forwardedBy:id,fname,lname,username,deptId,status',
                    'forwardedBy.department:id,dept_name',
                    'forwardedBy.roles:id,name'
                ])
                    ->orderBy('created_at', 'desc')
                    ->limit(50); // Limit history per workflow
            }
        ])
            ->orderBy('created_at', 'desc')
            ->limit(200); // Limit total workflows to prevent memory exhaustion

        // Filter regular workflows by status
        if ($request->has('status') && $request->status != '') {
            if ($request->status == 'completed') {
                $workflowQuery->where('work_flow_completed', 1);
            } elseif ($request->status == 'pending') {
                $workflowQuery->where('work_flow_completed', 0);
            } elseif ($request->status == 'error') {
                // Workflows with errors (attended_by user doesn't exist or inactive)
                $workflowQuery->whereHas('workflowHistory', function ($q) {
                    $q->where('status', 0)
                        ->where(function ($query) {
                            $query->whereDoesntHave('attendedBy')
                                ->orWhereHas('attendedBy', function ($userQuery) {
                                    $userQuery->where('status', '!=', 'active');
                                });
                        });
                });
            }
        }
        
        // Filter by approver role - show pending workflows assigned to users with this role
        if ($request->has('approver_role') && $request->approver_role != '') {
            $roleName = $request->approver_role;
            $workflowQuery->whereHas('workflowHistory', function ($q) use ($roleName) {
                $q->where('status', 0) // Pending
                    ->whereHas('attendedBy.roles', function ($roleQuery) use ($roleName) {
                        $roleQuery->where('name', $roleName);
                    });
            })->where('work_flow_completed', 0);
        }

        // Filter regular workflows by form type
        if ($request->has('form_type') && $request->form_type != '') {
            $formType = $request->form_type;
            if ($formType == 'ict') {
                $workflowQuery->whereNotNull('ict_request_resource_id');
            } elseif ($formType == 'hr') {
                $workflowQuery->whereNotNull('hr_form');
            } elseif ($formType == 'requisition') {
                $workflowQuery->whereNotNull('requisition_id');
            } elseif ($formType == 'locum') {
                $workflowQuery->whereNotNull('locum_request_id');
            } elseif ($formType == 'oncall') {
                $workflowQuery->whereNotNull('on_call_request_id');
            } elseif ($formType == 'clearance') {
                // For clearance, we'll filter it out from regular workflows and only show clearance workflows
                $workflowQuery->whereRaw('1 = 0'); // Return no results
            }
        }

        $regularWorkflows = $workflowQuery->get();

        // Add type identifier to regular workflows
        foreach ($regularWorkflows as $workflow) {
            $workflow->workflow_type = 'regular';
        }

        // Get clearance workflows - optimize to prevent memory issues
        $clearanceQuery = Clearance_work_flow::with([
            'user:id,fname,lname,username,deptId,status',
            'user.department:id,dept_name',
            'user.roles:id,name',
            'histories' => function ($query) {
                // Limit history records and only load necessary columns
                $query->with([
                    'attendedBy:id,fname,lname,username,deptId,status',
                    'attendedBy.department:id,dept_name',
                    'attendedBy.roles:id,name',
                    'forwardedBy:id,fname,lname,username,deptId,status',
                    'forwardedBy.department:id,dept_name',
                    'forwardedBy.roles:id,name'
                ])
                    ->orderBy('created_at', 'desc')
                    ->limit(50); // Limit history per workflow
            }
        ])
            ->orderBy('created_at', 'desc')
            ->limit(200); // Limit total workflows to prevent memory exhaustion

        // Filter clearance workflows by status
        if ($request->has('status') && $request->status != '') {
            if ($request->status == 'completed') {
                $clearanceQuery->where('work_flow_completed', 1);
            } elseif ($request->status == 'pending') {
                $clearanceQuery->where('work_flow_completed', 0);
            } elseif ($request->status == 'error') {
                // Clearance workflows with errors
                $clearanceQuery->whereHas('histories', function ($q) {
                    $q->where('status', 0)
                        ->where(function ($query) {
                            $query->whereDoesntHave('attendedBy')
                                ->orWhereHas('attendedBy', function ($userQuery) {
                                    $userQuery->where('status', '!=', 'active');
                                });
                        });
                });
            }
        }
        
        // Filter clearance workflows by approver role
        if ($request->has('approver_role') && $request->approver_role != '') {
            $roleName = $request->approver_role;
            $clearanceQuery->whereHas('histories', function ($q) use ($roleName) {
                $q->where('status', 0) // Pending
                    ->whereHas('attendedBy.roles', function ($roleQuery) use ($roleName) {
                        $roleQuery->where('name', $roleName);
                    });
            })->where('work_flow_completed', 0);
        }

        // Filter clearance workflows by form type
        if ($request->has('form_type') && $request->form_type != '') {
            $formType = $request->form_type;
            if ($formType == 'clearance') {
                // Show only clearance workflows
            } else {
                // For other form types, exclude clearance workflows
                $clearanceQuery->whereRaw('1 = 0'); // Return no results
            }
        }

        $clearanceWorkflows = $clearanceQuery->get();

        // Add type identifier to clearance workflows and map to similar structure
        foreach ($clearanceWorkflows as $workflow) {
            $workflow->workflow_type = 'clearance';
            // Map histories to workflowHistory for consistency
            $workflow->workflowHistory = $workflow->histories;
        }

        // Merge both collections
        $allWorkflows = $regularWorkflows->concat($clearanceWorkflows);

        // Get all roles for the filter dropdown
        $roles = Role::orderBy('name')->pluck('name');

        // Sort by created_at descending
        $allWorkflows = $allWorkflows->sortByDesc(function ($workflow) {
            return $workflow->created_at;
        })->values();

        return view('workflow-management.index', compact('allWorkflows', 'roles'));
    }

    /**
     * Display clearance workflows
     */
    public function clearanceWorkflows(Request $request)
    {
        $query = Clearance_work_flow::with(['user', 'histories.attendedBy', 'histories.forwardedBy'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            if ($request->status == 'completed') {
                $query->where('work_flow_completed', 1);
            } elseif ($request->status == 'pending') {
                $query->where('work_flow_completed', 0);
            } elseif ($request->status == 'error') {
                // Workflows with errors
                $query->whereHas('histories', function ($q) {
                    $q->where('status', 0)
                        ->whereDoesntHave('attendedBy')
                        ->orWhereHas('attendedBy', function ($userQuery) {
                            $userQuery->where('status', '!=', 'active');
                        });
                });
            }
        }

        $workflows = $query->paginate(20);

        return view('workflow-management.clearance', compact('workflows'));
    }

    /**
     * Show workflow details and history
     */
    public function show($id)
    {
        $workflow = Workflow::with([
            'user.department',
            'user.roles',
            'workflowHistory.attendedBy.department',
            'workflowHistory.attendedBy.roles',
            'workflowHistory.forwardedBy.department',
            'workflowHistory.forwardedBy.roles',
            'workflowHistory.approver.department',
            'workflowHistory.approver.roles',
            'ictAccessResource',
            'locumRequest',
            'onCallRequest'
        ])->findOrFail($id);

        $histories = $workflow->workflowHistory()->orderBy('created_at', 'asc')->get();

        return view('workflow-management.show', compact('workflow', 'histories'));
    }

    /**
     * Update workflow status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'work_flow_status' => 'required|integer|in:0,1,2',
            'work_flow_completed' => 'required|integer|in:0,1',
        ]);

        $workflow = Workflow::findOrFail($id);

        DB::beginTransaction();
        try {
            $workflow->work_flow_status = $request->work_flow_status;
            $workflow->work_flow_completed = $request->work_flow_completed;
            $workflow->save();

            DB::commit();
            return redirect()->route('workflow-management.show', $id)
                ->with('success', 'Workflow status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update workflow status: ' . $e->getMessage()]);
        }
    }

    /**
     * Update form-specific status
     */
    public function updateFormStatus(Request $request, $id)
    {
        $workflow = Workflow::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($workflow->locum_request_id && $request->has('locum_request_status')) {
                $request->validate([
                    'history_id' => 'required|exists:work_flow_histories,id',
                    'locum_request_status' => 'required|integer|in:0,1,2,3,4,5,6',
                ]);

                $history = WorkFlowHistory::findOrFail($request->history_id);
                if ($history->work_flow_id != $workflow->id) {
                    throw new \Exception('History does not belong to this workflow.');
                }

                $history->locum_request_status = $request->locum_request_status;
                $history->save();

            } elseif ($workflow->on_call_request_id) {
                if ($request->has('on_call_request_status')) {
                    $request->validate([
                        'history_id' => 'required|exists:work_flow_histories,id',
                        'on_call_request_status' => 'required|integer|in:0,1,2,3,4,5,6',
                    ]);

                    $history = WorkFlowHistory::findOrFail($request->history_id);
                    if ($history->work_flow_id != $workflow->id) {
                        throw new \Exception('History does not belong to this workflow.');
                    }

                    $history->on_call_request_status = $request->on_call_request_status;
                    $history->save();
                }

                // Update direct status on on_call_requests table if provided
                if ($request->has('on_call_status') && $workflow->onCallRequest) {
                    $request->validate([
                        'on_call_status' => 'required|string|in:pending,approved,rejected',
                    ]);

                    $workflow->onCallRequest->status = $request->on_call_status;
                    $workflow->onCallRequest->save();
                }
            } else {
                throw new \Exception('This workflow does not have a form-specific status field.');
            }

            DB::commit();
            return redirect()->route('workflow-management.show', $id)
                ->with('success', 'Form status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update form status: ' . $e->getMessage()]);
        }
    }

    /**
     * Show clearance workflow details
     */
    public function showClearance($id)
    {
        $workflow = Clearance_work_flow::with([
            'user.department',
            'user.roles',
            'histories.attendedBy.department',
            'histories.attendedBy.roles',
            'histories.forwardedBy.department',
            'histories.forwardedBy.roles',
            'histories.approver.department',
            'histories.approver.roles'
        ])->findOrFail($id);

        $histories = $workflow->histories()->orderBy('created_at', 'asc')->get();

        return view('workflow-management.show-clearance', compact('workflow', 'histories'));
    }

    /**
     * Edit workflow history - reassign to different user
     */
    public function editHistory($id)
    {
        $history = WorkFlowHistory::with(['workflow', 'attendedBy.department', 'attendedBy.roles', 'forwardedBy.department', 'forwardedBy.roles'])->findOrFail($id);

        // Get active users for reassignment
        $users = User::where('status', 'active')->with('department', 'roles')->orderBy('fname')->get();

        return view('workflow-management.edit-history', compact('history', 'users'));
    }

    /**
     * Update workflow history
     */
    public function updateHistory(Request $request, $id)
    {
        $request->validate([
            'attended_by' => 'required|exists:users,id',
            'remark' => 'nullable|string|max:500',
        ]);

        $history = WorkFlowHistory::findOrFail($id);

        // Check if new user is active
        $newUser = User::findOrFail($request->attended_by);
        if ($newUser->status != 'active') {
            return redirect()->back()->withErrors(['attended_by' => 'Selected user is not active.']);
        }

        DB::beginTransaction();
        try {
            $oldUserId = $history->attended_by;
            $history->attended_by = $request->attended_by;
            if ($request->remark) {
                $history->remark = ($history->remark ? $history->remark . "\n\n" : '') .
                    'Reassigned from User ID ' . $oldUserId . ' to User ID ' . $request->attended_by .
                    ' by ' . Auth::user()->username . ' on ' . now()->format('Y-m-d H:i:s') .
                    ($request->remark ? '. Note: ' . $request->remark : '');
            }
            $history->save();

            // Clear error count cache
            Cache::forget('workflow_errors_count');

            DB::commit();
            return redirect()->route('workflow-management.show', $history->work_flow_id)
                ->with('success', 'Workflow history updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update workflow history: ' . $e->getMessage()]);
        }
    }

    /**
     * Edit clearance workflow history
     */
    public function editClearanceHistory($id)
    {
        $history = Clearance_work_flow_history::with(['workflow', 'attendedBy.department', 'attendedBy.roles', 'forwardedBy.department', 'forwardedBy.roles'])->findOrFail($id);

        $users = User::where('status', 'active')->with('department', 'roles')->orderBy('fname')->get();

        return view('workflow-management.edit-clearance-history', compact('history', 'users'));
    }

    /**
     * Update clearance workflow history
     */
    public function updateClearanceHistory(Request $request, $id)
    {
        $request->validate([
            'attended_by' => 'required|exists:users,id',
            'remark' => 'nullable|string|max:500',
        ]);

        $history = Clearance_work_flow_history::findOrFail($id);

        $newUser = User::findOrFail($request->attended_by);
        if ($newUser->status != 'active') {
            return redirect()->back()->withErrors(['attended_by' => 'Selected user is not active.']);
        }

        DB::beginTransaction();
        try {
            $oldUserId = $history->attended_by;
            $history->attended_by = $request->attended_by;
            if ($request->remark) {
                $history->remark = ($history->remark ? $history->remark . "\n\n" : '') .
                    'Reassigned from User ID ' . $oldUserId . ' to User ID ' . $request->attended_by .
                    ' by ' . Auth::user()->username . ' on ' . now()->format('Y-m-d H:i:s') .
                    ($request->remark ? '. Note: ' . $request->remark : '');
            }
            $history->save();

            // Clear error count cache
            Cache::forget('workflow_errors_count');

            DB::commit();
            return redirect()->route('workflow-management.show-clearance', $history->work_flow_id)
                ->with('success', 'Workflow history updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update workflow history: ' . $e->getMessage()]);
        }
    }

    /**
     * Get workflows with errors
     */
    public function errors()
    {
        // Workflows where attended_by user doesn't exist or is inactive
        // Limit to only load necessary columns and relationships
        $errorWorkflows = Workflow::whereHas('workflowHistory', function ($q) {
            $q->where('status', 0)
                ->where(function ($query) {
                    $query->whereDoesntHave('attendedBy')
                        ->orWhereHas('attendedBy', function ($userQuery) {
                            $userQuery->where('status', '!=', 'active');
                        });
                });
        })
            ->with([
                'user:id,fname,lname,username,deptId,status',
                'user.department:id,dept_name',
                'user.roles:id,name',
                'workflowHistory' => function ($query) {
                    // Limit history records and only load necessary columns
                    $query->with([
                        'attendedBy:id,fname,lname,username,deptId,status',
                        'attendedBy.department:id,dept_name',
                        'attendedBy.roles:id,name',
                        'forwardedBy:id,fname,lname,username,deptId,status',
                        'forwardedBy.department:id,dept_name',
                        'forwardedBy.roles:id,name'
                    ])
                        ->orderBy('created_at', 'desc')
                        ->limit(50); // Limit history per workflow to prevent memory issues
                }
            ])
            ->orderBy('created_at', 'desc')
            ->limit(100) // Limit total workflows to prevent memory exhaustion
            ->get();

        // Add type identifier
        foreach ($errorWorkflows as $workflow) {
            $workflow->workflow_type = 'regular';
        }

        // Clearance workflows with errors
        $errorClearanceWorkflows = Clearance_work_flow::whereHas('histories', function ($q) {
            $q->where('status', 0)
                ->where(function ($query) {
                    $query->whereDoesntHave('attendedBy')
                        ->orWhereHas('attendedBy', function ($userQuery) {
                            $userQuery->where('status', '!=', 'active');
                        });
                });
        })
            ->with([
                'user:id,fname,lname,username,deptId,status',
                'user.department:id,dept_name',
                'user.roles:id,name',
                'histories' => function ($query) {
                    // Limit history records and only load necessary columns
                    $query->with([
                        'attendedBy:id,fname,lname,username,deptId,status',
                        'attendedBy.department:id,dept_name',
                        'attendedBy.roles:id,name',
                        'forwardedBy:id,fname,lname,username,deptId,status',
                        'forwardedBy.department:id,dept_name',
                        'forwardedBy.roles:id,name'
                    ])
                        ->orderBy('created_at', 'desc')
                        ->limit(50); // Limit history per workflow to prevent memory issues
                }
            ])
            ->orderBy('created_at', 'desc')
            ->limit(100) // Limit total workflows to prevent memory exhaustion
            ->get();

        // Add type identifier and map histories
        foreach ($errorClearanceWorkflows as $workflow) {
            $workflow->workflow_type = 'clearance';
            $workflow->workflowHistory = $workflow->histories;
        }

        // Merge both collections
        $allErrorWorkflows = $errorWorkflows->concat($errorClearanceWorkflows);

        // Sort by created_at descending
        $allErrorWorkflows = $allErrorWorkflows->sortByDesc(function ($workflow) {
            return $workflow->created_at;
        })->values();

        return view('workflow-management.errors', compact('allErrorWorkflows'));
    }

    /**
     * Delete a workflow and its history
     */
    public function destroy($id)
    {
        try {
            $workflow = Workflow::findOrFail($id);

            DB::beginTransaction();

            // Delete all workflow history first (due to foreign key constraints)
            WorkFlowHistory::where('work_flow_id', $workflow->id)->delete();

            // Delete the workflow
            $workflow->delete();

            // Clear error count cache
            Cache::forget('workflow_errors_count');

            DB::commit();

            return redirect()->route('workflow-management.index')
                ->with('success', 'Workflow and all associated history have been deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a clearance workflow and its history
     */
    public function destroyClearance($id)
    {
        try {
            $workflow = Clearance_work_flow::findOrFail($id);

            DB::beginTransaction();

            // Delete all workflow history first (due to foreign key constraints)
            Clearance_work_flow_history::where('work_flow_id', $workflow->id)->delete();

            // Delete the workflow
            $workflow->delete();

            // Clear error count cache
            Cache::forget('workflow_errors_count');

            DB::commit();

            return redirect()->route('workflow-management.index')
                ->with('success', 'Clearance workflow and all associated history have been deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete clearance workflow: ' . $e->getMessage()]);
        }
    }

    /**
     * Update history status for regular workflows
     */
    public function updateHistoryStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|integer|in:0,1,2',
            'remark' => 'nullable|string|max:1000',
        ]);

        $history = WorkFlowHistory::findOrFail($id);

        DB::beginTransaction();
        try {
            $oldStatus = $history->status;
            $history->status = $request->status;
            
            if ($request->remark) {
                $history->remark = ($history->remark ? $history->remark . "\n\n" : '') .
                    'Status changed from ' . ($oldStatus == 0 ? 'Pending' : ($oldStatus == 1 ? 'Approved' : 'Rejected')) .
                    ' to ' . ($request->status == 0 ? 'Pending' : ($request->status == 1 ? 'Approved' : 'Rejected')) .
                    ' by ' . Auth::user()->username . ' on ' . now()->format('Y-m-d H:i:s') .
                    ($request->remark ? '. Note: ' . $request->remark : '');
            }
            
            if ($request->status == 1 || $request->status == 2) {
                $history->attend_date = now();
            }
            
            $history->save();

            DB::commit();
            return redirect()->route('workflow-management.show', $history->work_flow_id)
                ->with('success', 'History status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update history status: ' . $e->getMessage()]);
        }
    }

    /**
     * Update history status for clearance workflows
     */
    public function updateClearanceHistoryStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|integer|in:0,1,2',
            'remark' => 'nullable|string|max:1000',
        ]);

        $history = Clearance_work_flow_history::findOrFail($id);

        DB::beginTransaction();
        try {
            $oldStatus = $history->status;
            $history->status = $request->status;
            
            if ($request->remark) {
                $history->remark = ($history->remark ? $history->remark . "\n\n" : '') .
                    'Status changed from ' . ($oldStatus == 0 ? 'Pending' : ($oldStatus == 1 ? 'Approved' : 'Rejected')) .
                    ' to ' . ($request->status == 0 ? 'Pending' : ($request->status == 1 ? 'Approved' : 'Rejected')) .
                    ' by ' . Auth::user()->username . ' on ' . now()->format('Y-m-d H:i:s') .
                    ($request->remark ? '. Note: ' . $request->remark : '');
            }
            
            if ($request->status == 1 || $request->status == 2) {
                $history->attend_date = now();
            }
            
            $history->save();

            DB::commit();
            return redirect()->route('workflow-management.show-clearance', $history->work_flow_id)
                ->with('success', 'History status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update history status: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk delete workflows and their histories
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'workflow_ids' => 'required|array|min:1',
            'workflow_ids.*' => 'integer|distinct'
        ]);

        try {
            DB::beginTransaction();

            $workflows = Workflow::whereIn('id', $validated['workflow_ids'])->get();

            foreach ($workflows as $workflow) {
                WorkFlowHistory::where('work_flow_id', $workflow->id)->delete();
                $workflow->delete();
            }

            Cache::forget('workflow_errors_count');

            DB::commit();

            return redirect()->back()->with('success', 'Selected workflows and their history have been deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete selected workflows: ' . $e->getMessage()]);
        }
    }
}
