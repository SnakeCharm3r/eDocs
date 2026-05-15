<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IctAccessResource;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use App\Models\User;
use App\Models\Departments;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\IctAccessController;
use App\Models\Clearance_work_flow;
use App\Models\Clearance_work_flow_history;
use App\Models\ClearanceForm;
use App\Models\Contract;
use App\Models\UserSession;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovalRequestNotification;
use App\Mail\IctAccessRejectionNotification;
use Illuminate\Support\Facades\DB; // import DB
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use Barryvdh\DomPDF\Facade\Pdf;

use function Laravel\Prompts\select;

class FormController extends Controller
{

    public function getform(Request $request)
    {
        $user = Auth::user();

        // Fetch the form details
        //         $ictForm = IctAccessResource::join('users', 'users.id', '=', 'ict_access_resources.userId')
        //             ->join('workflows', 'workflows.ict_request_resource_id', '=', 'ict_access_resources.id')
        //             ->join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
        //              ->join('privilege_levels', 'privilege_levels.id', '=', 'ict_access_resources.privilegeId')
        //              ->join('nhif_qualifications', 'nhif_qualifications.id', '=', 'ict_access_resources.nhifId')
        //              ->join('employment_types', 'employment_types.id', '=', 'users.employment_typeId')
        //              ->join('departments', 'departments.id', '=', 'users.deptId')
        //              ->join('hecs', 'hecs.id', '=', 'departments.hec_id')
        // ->join('h_m_i_s_access_levels', function ($join) {
        //     $join->whereRaw("JSON_CONTAINS(ict_access_resources.hmisId, JSON_QUOTE(h_m_i_s_access_levels.id))");
        // })            // ->where('ict_access_resources.id', $request->id)
        //             // ->where('work_flow_histories.attended_by', $user->id)
        //             ->first([
        //                 'ict_access_resources.*',
        //                 'users.*',
        //                 'workflows.*',
        //                 'work_flow_histories.*',
        //                  'ict_access_resources.id as access_id',
        //                  'privilege_levels.prv_name',
        //                  'nhif_qualifications.name',
        //                  'employment_types.employment_type',
        //                  'departments.dept_name',
        //                  'hecs.hec_level_name',
        //                  'h_m_i_s_access_levels.names',
        //                 // 'work_flow_histories.forwarded_by'
        //             ]);
        // First, get the ICT access resource and workflow
        $ictAccessResource = IctAccessResource::find($request->id);
        if (!$ictAccessResource) {
            Alert::error('Error', 'ICT Access Form not found.');
            return redirect()->route('requestapprove.index')->with('error', 'ICT Access Form not found.');
        }

        $workflow = Workflow::where('ict_request_resource_id', $request->id)->first();
        if (!$workflow) {
            Alert::error('Error', 'Workflow not found for this form.');
            return redirect()->route('requestapprove.index')->with('error', 'Workflow not found for this form.');
        }

        // Log initial access attempt for debugging
        $allWorkflowHistories = WorkFlowHistory::where('work_flow_id', $workflow->id)->get();
        Log::info('ICT Access Form: View attempt', [
            'viewer_user_id' => $user->id,
            'viewer_username' => $user->username,
            'viewer_roles' => $user->getRoleNames()->toArray(),
            'form_id' => $request->id,
            'workflow_id' => $workflow->id,
            'workflow_user_id' => $workflow->user_id,
            'workflow_histories_count' => $allWorkflowHistories->count(),
            'workflow_histories' => $allWorkflowHistories->map(function($h) {
                return [
                    'id' => $h->id,
                    'attended_by' => $h->attended_by,
                    'status' => $h->status,
                    'step_name' => $h->step_name,
                ];
            })->toArray(),
        ]);

        // Check if user has permission to view this form:
        // 1. User is the requester (created the form)
        // 2. User has a pending workflow history (status = 0) for this form
        // 3. User has an approved workflow history (status = 1) for this form (for review)
        // 4. User is an HEC member viewing a form from their department
        // 5. User is CEO viewing a form from an HEC member
        $isRequester = $workflow->user_id === $user->id;
        $hasWorkflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->where('attended_by', $user->id)
            ->exists();
        
        // Fetch requester user once for all checks and logging
        $requesterUser = User::find($workflow->user_id);
        $canIctAccessReport = $user->can('ict_acces_report');
        
        // Also check if user is HEC member and form was submitted by someone in their department
        $isHecMember = $user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro']);
        $isCeo = $user->hasRole('ceo');
        $canViewAsHec = false;
        $canViewAsCeo = false;
        
        if ($isHecMember && !$isRequester && !$hasWorkflowHistory && $requesterUser) {
            // Check if the requester's department is under this HEC member's oversight
            if ($requesterUser->department) {
                $requesterHec = $requesterUser->department->hec;
                if ($requesterHec) {
                    $hecRoleMapping = [
                        'COO' => 'coo',
                        'CFO' => 'cfo',
                        'CMS' => 'cms',
                        'CCDRO' => 'ccdro',
                    ];
                    $requiredHecRole = $hecRoleMapping[$requesterHec->hec_level_name] ?? null;
                    if ($requiredHecRole && $user->hasRole($requiredHecRole)) {
                        $canViewAsHec = true;
                    }
                }
            }
        }
        
        // Check if CEO is viewing a form - CEO can view any pending ICT form
        if ($isCeo && !$isRequester && !$hasWorkflowHistory) {
            // CEO can view forms from HEC members (coo, cfo, cms, ccdro)
            if ($requesterUser) {
                $isRequesterHecMember = $requesterUser->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro']);
                if ($isRequesterHecMember) {
                    $canViewAsCeo = true;
                }
            }
            
            // Also check if there's a pending workflow history with step "CEO Approval" for this form
            $hasCeoApprovalStep = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('step_name', 'like', '%CEO%')
                ->exists();
            if ($hasCeoApprovalStep) {
                $canViewAsCeo = true;
            }
            
            // CEO can view any form that has a pending workflow step
            // This is important because CEO may need to view forms even if not directly assigned
            $hasPendingStep = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0) // Pending
                ->exists();
            if ($hasPendingStep) {
                $canViewAsCeo = true;
            }
        }

        // Get workflow history details for logging
        $workflowHistories = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->orderBy('id', 'desc')
            ->get(['id', 'attended_by', 'step_name', 'status', 'forwarded_by']);
        
        $requesterRoles = $requesterUser ? $requesterUser->getRoleNames()->toArray() : [];
        $userRoles = $user->getRoleNames()->toArray();

        if (!$isRequester && !$hasWorkflowHistory && !$canViewAsHec && !$canViewAsCeo && !$canIctAccessReport) {
            // Determine which permission is missing and what's required
            $missingPermissions = [];
            $requiredPermissions = [];
            
            if (!$isRequester) {
                $missingPermissions[] = 'User is not the requester';
            }
            
            if (!$hasWorkflowHistory) {
                $missingPermissions[] = 'User has no workflow history assigned';
                // Check what the current pending step is
                $pendingHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->where('status', 0)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($pendingHistory) {
                    $requiredPermissions[] = "User needs to be assigned to workflow step: '{$pendingHistory->step_name}' (ID: {$pendingHistory->id}, Currently assigned to User ID: {$pendingHistory->attended_by})";
                } else {
                    $requiredPermissions[] = 'No pending workflow step found - form may be completed or rejected';
                }
            }
            
            if ($isHecMember && !$canViewAsHec) {
                $missingPermissions[] = 'User is HEC member but cannot view as HEC (requester department mismatch)';
                if ($requesterUser && $requesterUser->department && $requesterUser->department->hec) {
                    $requiredPermissions[] = "Requester's department HEC level: '{$requesterUser->department->hec->hec_level_name}' - User needs matching HEC role";
                }
            }
            
            if ($isCeo && !$canViewAsCeo) {
                $missingPermissions[] = 'User is CEO but cannot view (requester is not HEC member or no CEO approval step)';
                if ($requesterUser) {
                    $isRequesterHecMember = $requesterUser->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro']);
                    if (!$isRequesterHecMember) {
                        $requiredPermissions[] = 'Requester is not an HEC member - CEO can only view HEC member requests';
                    }
                }
                $hasCeoStep = WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->where('step_name', 'like', '%CEO%')
                    ->exists();
                if (!$hasCeoStep) {
                    $requiredPermissions[] = 'No CEO approval step found in workflow history';
                }
            }
            
            if (!$isHecMember && !$isCeo) {
                $requiredPermissions[] = 'User is not an HEC member or CEO - needs workflow history assignment to view';
            }
            
            // Get current pending workflow step details
            $pendingHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->orderBy('id', 'desc')
                ->first();
            
            // Log detailed permission check failure with explicit requirements
            Log::warning('ICT Access Form: Permission denied - Missing Requirements', [
                '=== PERMISSION DENIED ===' => 'User does not have permission to view this form',
                'user_id' => $user->id,
                'user_name' => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: $user->username,
                'user_username' => $user->username,
                'user_roles' => $userRoles,
                'form_id' => $request->id,
                'workflow_id' => $workflow->id,
                'requester_id' => $workflow->user_id,
                'requester_name' => $requesterUser ? (trim(($requesterUser->fname ?? '') . ' ' . ($requesterUser->lname ?? '')) ?: $requesterUser->username) : 'Unknown',
                'requester_username' => $requesterUser ? $requesterUser->username : 'Unknown',
                'requester_roles' => $requesterRoles,
                '=== PERMISSION CHECKS ===' => [
                    'is_requester' => $isRequester,
                    'has_workflow_history' => $hasWorkflowHistory,
                    'is_hec_member' => $isHecMember,
                    'is_ceo' => $isCeo,
                    'can_view_as_hec' => $canViewAsHec,
                    'can_view_as_ceo' => $canViewAsCeo,
                    'can_view_as_ict_access_report' => $canIctAccessReport,
                ],
                '=== MISSING PERMISSIONS ===' => $missingPermissions,
                '=== REQUIRED PERMISSIONS/ACTIONS ===' => $requiredPermissions,
                '=== CURRENT WORKFLOW STATUS ===' => [
                    'workflow_status' => $workflow->work_flow_status,
                    'workflow_completed' => $workflow->work_flow_completed,
                    'pending_step' => $pendingHistory ? [
                        'id' => $pendingHistory->id,
                        'step_name' => $pendingHistory->step_name,
                        'attended_by' => $pendingHistory->attended_by,
                        'attended_by_user' => $pendingHistory->attended_by ? (User::find($pendingHistory->attended_by)?->username ?? 'User not found') : 'Not assigned',
                        'status' => $pendingHistory->status,
                    ] : 'No pending step',
                ],
                '=== ALL WORKFLOW HISTORIES ===' => $workflowHistories->map(function($history) {
                    $attendedByUser = $history->attended_by ? (User::find($history->attended_by)?->username ?? 'User not found') : 'Not assigned';
                    return [
                        'id' => $history->id,
                        'step_name' => $history->step_name,
                        'attended_by' => $history->attended_by,
                        'attended_by_username' => $attendedByUser,
                        'status' => $history->status . ($history->status == 0 ? ' (Pending)' : ($history->status == 1 ? ' (Approved)' : ' (Rejected)')),
                        'forwarded_by' => $history->forwarded_by,
                    ];
                })->toArray(),
            ]);
            
            Alert::error('Error', 'ICT Access Form not found or you do not have permission to view it.');
            return redirect()->route('requestapprove.index')->with('error', 'ICT Access Form not found or you do not have permission to view it.');
        }

        // Get the workflow history for the current user (if exists) or the latest pending one
        $workflowHistory = null;
        if ($hasWorkflowHistory) {
            // Get the most recent workflow history for this user
            $workflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', $user->id)
                ->orderBy('id', 'desc')
                ->first();
        } else {
            // Get the latest pending workflow history
            $workflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->orderBy('id', 'desc')
                ->first();
            
            // If no pending workflow history found, get any workflow history for this workflow
            // This is important for CEO/HEC members who can view but aren't directly assigned
            if (!$workflowHistory && ($canViewAsCeo || $canViewAsHec || $isRequester)) {
                $workflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->orderBy('id', 'desc')
                    ->first();
            }
        }

        // Now build the form data query
        // Use LEFT JOINs for optional tables to prevent query failure
        $ictForm = IctAccessResource::join('users', 'users.id', '=', 'ict_access_resources.userId')
            ->join('workflows', 'workflows.ict_request_resource_id', '=', 'ict_access_resources.id')
            ->leftJoin('work_flow_histories', function($join) use ($workflowHistory) {
                $join->on('work_flow_histories.work_flow_id', '=', 'workflows.id');
                if ($workflowHistory) {
                    $join->where('work_flow_histories.id', '=', $workflowHistory->id);
                }
            })
            ->leftJoin('privilege_levels', 'privilege_levels.id', '=', 'ict_access_resources.privilegeId')
            ->leftJoin('nhif_qualifications', 'nhif_qualifications.id', '=', 'ict_access_resources.nhifId')
            ->leftJoin('employment_types', 'employment_types.id', '=', 'users.employment_typeId')
            ->leftJoin('departments', 'departments.id', '=', 'users.deptId')
            ->leftJoin('hecs', 'hecs.id', '=', 'departments.hec_id')
            ->where('ict_access_resources.id', $request->id)
            ->first([
                'ict_access_resources.*',
                'users.*',
                'users.email as user_email',
                'workflows.id as workflow_id',
                'workflows.user_id as workflow_user_id',
                'workflows.ict_request_resource_id',
                'workflows.status as workflow_status',
                'workflows.created_at as workflow_created_at',
                'workflows.updated_at as workflow_updated_at',
                'work_flow_histories.id as workflow_history_id',
                'work_flow_histories.forwarded_by',
                'work_flow_histories.attended_by',
                'work_flow_histories.status as history_status',
                'work_flow_histories.remark',
                'work_flow_histories.attend_date',
                'work_flow_histories.who_approve',
                'work_flow_histories.rejection_reason',
                'work_flow_histories.comments',
                'work_flow_histories.created_at as history_created_at',
                'work_flow_histories.updated_at as history_updated_at',
                'ict_access_resources.id as access_id',
                'ict_access_resources.hardware_request',
                'privilege_levels.prv_name',
                'nhif_qualifications.name',
                'employment_types.employment_type',
                'departments.dept_name',
                'hecs.hec_level_name'
            ]);

        // Check if form exists
        if (!$ictForm) {
            // Log the failure with details about the query conditions
            Log::warning('ICT Access Form: Query returned null', [
                'user_id' => $user->id,
                'user_name' => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: $user->username,
                'user_roles' => $user->getRoleNames()->toArray(),
                'form_id' => $request->id,
                'workflow_id' => $workflow->id,
                'workflow_history_id' => $workflowHistory ? $workflowHistory->id : 'null',
                'workflow_history_status' => $workflowHistory ? $workflowHistory->status : 'null',
                'workflow_history_step_name' => $workflowHistory ? $workflowHistory->step_name : 'null',
                'permission_context' => [
                    'is_requester' => $isRequester,
                    'has_workflow_history' => $hasWorkflowHistory,
                    'can_view_as_hec' => $canViewAsHec,
                    'can_view_as_ceo' => $canViewAsCeo,
                ],
                'error' => 'Form query returned null - check JOIN conditions',
            ]);
            Alert::error('Error', 'ICT Access Form not found or you do not have permission to view it.');
            return redirect()->route('requestapprove.index')->with('error', 'ICT Access Form not found or you do not have permission to view it.');
        }

        // Fetch the ICT access resource directly to ensure all fields are available
        $ictAccessResource = IctAccessResource::find($request->id);
        if ($ictAccessResource) {
            // Merge hardware_request from the direct model fetch
            $ictForm->hardware_request = $ictAccessResource->hardware_request;
            // Merge edocs from the direct model fetch
            $ictForm->edocs = $ictAccessResource->edocs;
            // Ensure ICT email access fields are not overwritten by users.email
            $ictForm->email = $ictAccessResource->email;
            $ictForm->requested_email_address = $ictAccessResource->requested_email_address;
        }

        // Handle hmisId - it's now JSON, so decode it if it's a string
        $hmisIds = $ictForm->hmisId;
        if (is_string($hmisIds)) {
            $hmisIds = json_decode($hmisIds, true);
        }
        if (!is_array($hmisIds)) {
            $hmisIds = [];
        }
        
        $hmaccess = DB::table('h_m_i_s_access_levels')->whereIn('id', $hmisIds)->get();
        
        // Handle edocs - it's JSON array, so decode it if it's a string
        $edocsIds = $ictForm->edocs ?? null;
        if (is_string($edocsIds)) {
            $edocsIds = json_decode($edocsIds, true);
        }
        if (!is_array($edocsIds)) {
            $edocsIds = [];
        }
        
        $edocsLevels = !empty($edocsIds) ? \App\Models\EdocsLevel::whereIn('id', $edocsIds)->get() : collect([]);
        
        // Check if user's department is clinical and get HR workflow history for license info
        $requestUser = User::with('department')->find($ictForm->userId);
        $isClinicalDepartment = $requestUser && $requestUser->jobTitle && $requestUser->jobTitle->clinical_or_non_clinical === 'Clinical';
        $hrWorkflowHistory = null;
        $licenseProviderName = '';
        
        if ($isClinicalDepartment && $requestUser->professional_reg_number) {
            // Find HR workflow for this user
            $hrWorkflow = Workflow::where('hr_form', $requestUser->id)->orderBy('id', 'desc')->first();
            if ($hrWorkflow) {
                // Get the most recent HR workflow history with license info
                $hrWorkflowHistory = WorkFlowHistory::where('work_flow_id', $hrWorkflow->id)
                    ->whereNotNull('license_valid_until')
                    ->orderBy('id', 'desc')
                    ->first();
            }
            
            // Parse professional registration number to extract provider name
            $regNumber = $requestUser->professional_reg_number ?? '';
            if (preg_match('/^([A-Z]+):\s*(.+)$/', $regNumber, $matches)) {
                $regType = $matches[1];
                $licenseProviders = [
                    'MCT' => 'Medical Council of Tanzania',
                    'TNMC' => 'Tanzania Nursing and Midwifery Council',
                    'TPB' => 'Tanzania Pharmacy Board',
                    'TPC' => 'Tanzania Physiotherapy Council',
                    'TMDC' => 'Tanzania Medical and Dental Council',
                    'Other' => 'Other'
                ];
                $licenseProviderName = $licenseProviders[$regType] ?? $regType;
            }
        }
        
        // dd($hmaccess);
        $approver = null;
        $lineManager = null;
        $ceoApprover = null;

        // Check if the requester was an HEC member (CEO would have approved)
        $requesterUser = User::find($ictForm->userId);
        $isRequesterHecMember = $requesterUser && $requesterUser->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro']);

        // Fetch CEO approver if they approved this form
        $ceoApprover = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'ceo');
            })
            ->where('work_flow_histories.work_flow_id', $ictForm->work_flow_id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'desc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        // Check for HEC member approver (coo, cfo, cms, ccdro)
        $users = User::role(['coo', 'cfo', 'cms', 'ccdro'])->get();
        $roleMapping = [
            'COO' => 'coo',
            'CFO' => 'cfo',
            'CMS' => 'cms',
            'CCDRO' => 'ccdro',
        ];

        $requiredRole = $roleMapping[$ictForm->hec_level_name] ?? null;

        if ($users->isNotEmpty() && $requiredRole) {
            $approver = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
                ->whereHas('roles', function ($query) use ($requiredRole) {
                    $query->where('name', $requiredRole);
                })
                ->where('work_flow_histories.work_flow_id', $ictForm->work_flow_id)
                ->where('work_flow_histories.status', 1)
                ->orderBy('work_flow_histories.updated_at', 'desc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();
        }

        // Always check for line manager regardless of the above condition
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

        // Debugging
        // dd(compact('approver', 'lineManager'));
        //    dd($approver);
        //    dd($lineManager);

        $hrOfficer = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })
            ->where('work_flow_histories.work_flow_id', $ictForm->work_flow_id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        $itOfficer = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'it');
            })
            ->where('work_flow_histories.work_flow_id', $ictForm->work_flow_id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        $latestRejectionHistory = WorkFlowHistory::where('work_flow_id', $ictForm->work_flow_id)
            ->where('status', -1)
            ->orderBy('id', 'desc')
            ->first();

        // Convert $ictForm to an object with proper relationships if needed
        // The query result should already have the data, but we need to ensure relationships work
        // Since we're using joins, the data is already in the result object

        return view('ict_resource_form', compact('ictForm', 'hmaccess', 'edocsLevels', 'user', 'lineManager', 'itOfficer', 'hrOfficer', 'approver', 'ceoApprover', 'isRequesterHecMember', 'isClinicalDepartment', 'hrWorkflowHistory', 'licenseProviderName', 'requestUser', 'latestRejectionHistory'));
    }

    public function getFormPdf(Request $request, $id)
    {
        $ictFormBase = IctAccessResource::join('users', 'users.id', '=', 'ict_access_resources.userId')
            ->leftJoin('workflows', 'workflows.ict_request_resource_id', '=', 'ict_access_resources.id')
            ->leftJoin('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->leftJoin('privilege_levels', 'privilege_levels.id', '=', 'ict_access_resources.privilegeId')
            ->leftJoin('nhif_qualifications', 'nhif_qualifications.id', '=', 'ict_access_resources.nhifId')
            ->leftJoin('employment_types', 'employment_types.id', '=', 'users.employment_typeId')
            ->leftJoin('departments', 'departments.id', '=', 'users.deptId')
            ->leftJoin('hecs', 'hecs.id', '=', 'departments.hec_id')
            ->where('ict_access_resources.id', $id)
            ->first([
                'ict_access_resources.*',
                'users.*',
                'users.email as user_email',
                'workflows.*',
                'work_flow_histories.*',
                'ict_access_resources.id as access_id',
                'ict_access_resources.hardware_request',
                'privilege_levels.prv_name',
                'nhif_qualifications.name',
                'employment_types.employment_type',
                'departments.dept_name',
                'hecs.hec_level_name',
                'work_flow_histories.forwarded_by',
                'work_flow_histories.work_flow_id',
            ]);

        if (!$ictFormBase) {
            abort(404, 'ICT Access Form not found.');
        }

        $ictForm = $ictFormBase;

        // Resolve privilege/level objects
        $activeDrtPrivilege = $ictForm->active_drt ? \App\Models\PrivilegeLevel::find($ictForm->active_drt) : null;
        $emailPrivilege     = $ictForm->email       ? \App\Models\PrivilegeLevel::find($ictForm->email)       : null;
        $vpnPrivilege       = $ictForm->VPN         ? \App\Models\PrivilegeLevel::find($ictForm->VPN)         : null;
        $pbaxPrivilege      = $ictForm->pbax        ? \App\Models\PrivilegeLevel::find($ictForm->pbax)        : null;
        $folderPrivilege    = $ictForm->folder_privilege ? \App\Models\PrivilegeLevel::find($ictForm->folder_privilege) : null;
        $sapLevel           = $ictForm->ASPId       ? \App\Models\SAPLevels::find($ictForm->ASPId)            : null;

        $arutiId = $ictForm->aruti;
        if (is_array($arutiId)) { $arutiId = !empty($arutiId) ? $arutiId[0] : null; }
        $arutiPrivilege = $arutiId ? \App\Models\ArutiLevel::find($arutiId) : null;

        $hmisIds = $ictForm->hmisId;
        if (is_string($hmisIds)) { $hmisIds = json_decode($hmisIds, true); }
        $hmaccess = !empty($hmisIds) ? \App\Models\HMISAccessLevel::whereIn('id', (array) $hmisIds)->get() : collect([]);

        $edocsIds = $ictForm->edocs;
        if (is_string($edocsIds)) { $edocsIds = json_decode($edocsIds, true); }
        $edocsLevels = !empty($edocsIds) ? \App\Models\EdocsLevel::whereIn('id', (array) $edocsIds)->get() : collect([]);

        $requestUser = \App\Models\User::with(['jobTitle', 'department'])->find($ictForm->userId);

        $isRequesterHecMember = $requestUser && $requestUser->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro']);
        $isClinicalDepartment = $requestUser && $requestUser->jobTitle && $requestUser->jobTitle->clinical_or_non_clinical === 'Clinical';

        $workflowId = $ictForm->work_flow_id;

        $lineManager = \App\Models\User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
            ->where('work_flow_histories.work_flow_id', $workflowId)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'desc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        $itOfficer = \App\Models\User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', fn($q) => $q->where('name', 'it'))
            ->where('work_flow_histories.work_flow_id', $workflowId)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        $hrOfficer = \App\Models\User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', fn($q) => $q->where('name', 'hr'))
            ->where('work_flow_histories.work_flow_id', $workflowId)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'asc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        $ceoApprover = \App\Models\User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', fn($q) => $q->where('name', 'ceo'))
            ->where('work_flow_histories.work_flow_id', $workflowId)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'desc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        $approver = null;
        $roleMapping = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CCDRO' => 'ccdro'];
        $requiredRole = $roleMapping[$ictForm->hec_level_name] ?? null;
        if ($requiredRole) {
            $approver = \App\Models\User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
                ->whereHas('roles', fn($q) => $q->where('name', $requiredRole))
                ->where('work_flow_histories.work_flow_id', $workflowId)
                ->where('work_flow_histories.status', 1)
                ->orderBy('work_flow_histories.updated_at', 'desc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();
        }

        return view('pdf.ict_access_form', compact(
            'ictForm', 'hmaccess', 'edocsLevels', 'requestUser',
            'activeDrtPrivilege', 'emailPrivilege', 'vpnPrivilege',
            'pbaxPrivilege', 'folderPrivilege', 'sapLevel', 'arutiPrivilege',
            'lineManager', 'itOfficer', 'hrOfficer', 'approver', 'ceoApprover',
            'isRequesterHecMember', 'isClinicalDepartment'
        ));
    }

    public function removeHardwareItem(Request $request)
    {
        try {
            $request->validate([
                'access_id' => 'required|exists:ict_access_resources,id',
                'item' => 'required|string'
            ]);

            $ictAccessResource = IctAccessResource::findOrFail($request->access_id);
            
            // Get current hardware items
            $hardwareRequest = $ictAccessResource->hardware_request;
            if (empty($hardwareRequest)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hardware items found.'
                ], 404);
            }

            // Parse the comma-separated string
            $hardwareItems = array_filter(array_map('trim', explode(',', $hardwareRequest)));
            
            // Remove the specified item
            $hardwareItems = array_filter($hardwareItems, function($item) use ($request) {
                return trim($item) !== trim($request->item);
            });

            // Update the hardware_request field
            $newHardwareRequest = !empty($hardwareItems) ? implode(',', $hardwareItems) : null;
            $ictAccessResource->hardware_request = $newHardwareRequest;
            $ictAccessResource->save();

            Log::info('Hardware item removed', [
                'access_id' => $request->access_id,
                'removed_item' => $request->item,
                'remaining_items' => $hardwareItems
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hardware item removed successfully.',
                'remaining_items' => array_values($hardwareItems)
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Remove hardware item: Exception occurred', [
                'access_id' => $request->access_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the hardware item. Please try again.'
            ], 500);
        }
    }

    public function approveForm(Request $request)
    {
        try {
            Log::info('ICT Approval: Request received', [
                'access_id' => $request->access_id,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->username
            ]);

            $request->validate([
                'access_id' => 'required|exists:ict_access_resources,id'
            ]);

            Log::info('ICT Approval: Validation passed');

            DB::beginTransaction();

            // Find the workflow associated with the access request
            $workflow = Workflow::where('ict_request_resource_id', $request->access_id)->first();
            if (!$workflow) {
                DB::rollBack();
                Log::error('ICT Approval: Workflow not found', ['access_id' => $request->access_id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Workflow not found for this access request.'
                ], 404);
            }

            Log::info('ICT Approval: Workflow found', ['workflow_id' => $workflow->id]);

            // Find the current workflow history where status is 0 (pending)
            $workflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->where('attended_by', Auth::id())
                ->first();

            Log::info('ICT Approval: WorkflowHistory query executed', [
                'workflow_id' => $workflow->id,
                'user_id' => Auth::id(),
                'found' => $workflowHistory ? 'yes' : 'no'
            ]);

            if (!$workflowHistory) {
                DB::rollBack();
                // Check what workflow histories exist for debugging
                $allHistories = WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->get(['id', 'attended_by', 'status', 'created_at'])
                    ->toArray();
                Log::error('ICT Approval: WorkflowHistory not found', [
                    'workflow_id' => $workflow->id,
                    'user_id' => Auth::id(),
                    'all_histories_for_workflow' => $allHistories
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No pending approval found for this request.'
                ], 404);
            }

            Log::info('ICT Approval: WorkflowHistory found', ['history_id' => $workflowHistory->id]);

            $user = Auth::user();
            $userRoles = $user->getRoleNames()->toArray();

            Log::info('ICT Approval: User roles retrieved', [
                'user_id' => $user->id,
                'roles' => $userRoles
            ]);

            // Determine the appropriate role for approval workflow
            // Priority: it > hr > ceo > hec members > line-manager
            $approvalRolePriority = ['it', 'hr', 'ceo', 'coo', 'cfo', 'cms', 'ccdro', 'line-manager'];
            $roles = null;
            foreach ($approvalRolePriority as $priorityRole) {
                if (in_array($priorityRole, $userRoles)) {
                    $roles = $priorityRole;
                    break;
                }
            }

            // Fallback to first role if no priority role found
            if (!$roles) {
                $roles = $userRoles[0] ?? null;
            }

            Log::info('ICT Approval: Role determined', [
                'user_id' => $user->id,
                'all_roles' => $userRoles,
                'selected_role' => $roles,
            ]);

            $ictAccessResource = IctAccessResource::findOrFail($request->access_id);
            $requester = $ictAccessResource->user;

            Log::info('ICT Approval: ICT Access Resource loaded', [
                'access_id' => $ictAccessResource->id,
                'current_status' => $ictAccessResource->status
            ]);

            // Update the current WorkflowHistory to approved (status = 1)
            if ($roles != 'hr' && $roles != 'it') {
                Log::info('ICT Approval: Updating workflow history to approved', ['history_id' => $workflowHistory->id]);
                $workflowHistory->status = 1;
                $workflowHistory->who_approve = $user->id;
                $workflowHistory->comments = 'Approved';
                if (!$workflowHistory->save()) {
                    throw new \Exception('Failed to update workflow history');
                }
                Log::info('ICT Approval: Workflow history updated successfully');
            } else {
                Log::info('ICT Approval: Skipping workflow history update (HR/IT role)', ['role' => $roles]);
            }

            // Get requester user for email
            $requesterUser = User::join('workflows', 'workflows.user_id', '=', 'users.id')
                ->where('workflows.ict_request_resource_id', $request->access_id)
                ->select('users.*')
                ->first();

            if (!$requesterUser) {
                throw new \Exception('Requester user not found');
            }

            // Build request details summary
            $requestSummary = [];
            
            // Domain Access
            if ($ictAccessResource->active_drt) {
                $drtPrivilege = \App\Models\PrivilegeLevel::find($ictAccessResource->active_drt);
                if ($drtPrivilege) {
                    $requestSummary[] = "Domain Access: " . $drtPrivilege->prv_name;
                }
            }
            
            // Email Access
            if ($ictAccessResource->email) {
                $emailPrivilege = \App\Models\PrivilegeLevel::find($ictAccessResource->email);
                if ($emailPrivilege) {
                    $requestSummary[] = "Email Access: " . $emailPrivilege->prv_name;
                }
            }
            if (!empty($ictAccessResource->requested_email_address)) {
                $requestSummary[] = "Requested Email Address: " . $ictAccessResource->requested_email_address;
            }
            
            // Network Folder Access
            if ($ictAccessResource->network_folder) {
                $folderPrivilege = \App\Models\PrivilegeLevel::find($ictAccessResource->folder_privilege);
                $folderAccess = "Network Folder: " . $ictAccessResource->network_folder;
                if ($folderPrivilege) {
                    $folderAccess .= " (" . $folderPrivilege->prv_name . ")";
                }
                $requestSummary[] = $folderAccess;
            }
            
            // Additional System Access
            $additionalSystems = [];
            
            // HealthAI HMIS
            if ($ictAccessResource->hmisId) {
                $hmisIds = is_string($ictAccessResource->hmisId) ? json_decode($ictAccessResource->hmisId, true) : $ictAccessResource->hmisId;
                if (is_array($hmisIds) && !empty($hmisIds)) {
                    $hmisLevels = DB::table('h_m_i_s_access_levels')->whereIn('id', $hmisIds)->pluck('names')->toArray();
                    if (!empty($hmisLevels)) {
                        $additionalSystems[] = "HealthAI HMIS: " . implode(', ', $hmisLevels);
                    }
                }
            }
            
            // Aruti HR MIS
            if ($ictAccessResource->aruti && $ictAccessResource->aruti != '0') {
                $arutiLevel = \App\Models\ArutiLevel::find($ictAccessResource->aruti);
                if ($arutiLevel) {
                    $additionalSystems[] = "Aruti HR MIS: " . $arutiLevel->aruti_name;
                }
            }
            
            // eDocs
            if ($ictAccessResource->edocs) {
                $edocsIds = is_string($ictAccessResource->edocs) ? json_decode($ictAccessResource->edocs, true) : $ictAccessResource->edocs;
                if (is_array($edocsIds) && !empty($edocsIds)) {
                    $edocsLevels = \App\Models\EdocsLevel::whereIn('id', $edocsIds)->pluck('edocs_name')->toArray();
                    if (!empty($edocsLevels)) {
                        $additionalSystems[] = "eDocs System: " . implode(', ', $edocsLevels);
                    }
                }
            }
            
            // VPN
            if ($ictAccessResource->VPN && $ictAccessResource->VPN != '0') {
                $vpnPrivilege = \App\Models\PrivilegeLevel::find($ictAccessResource->VPN);
                if ($vpnPrivilege) {
                    $additionalSystems[] = "Network Access (VPN): " . $vpnPrivilege->prv_name;
                }
            }
            
            // SAP
            if ($ictAccessResource->ASPId && $ictAccessResource->ASPId != '0') {
                $sapLevel = \App\Models\SAPLevels::find($ictAccessResource->ASPId);
                if ($sapLevel) {
                    $additionalSystems[] = "SAP ERP Access: " . $sapLevel->access_name;
                }
            }
            
            // PABX
            if ($ictAccessResource->pbax && $ictAccessResource->pbax != '0') {
                $pbaxPrivilege = \App\Models\PrivilegeLevel::find($ictAccessResource->pbax);
                if ($pbaxPrivilege) {
                    $additionalSystems[] = "Call Manager-PABX: " . $pbaxPrivilege->prv_name;
                }
            }
            
            // Access Key Cards
            if ($ictAccessResource->access_key_card_id) {
                $keyCardIds = is_string($ictAccessResource->access_key_card_id) ? json_decode($ictAccessResource->access_key_card_id, true) : $ictAccessResource->access_key_card_id;
                if (is_array($keyCardIds) && !empty($keyCardIds)) {
                    $keyCards = \App\Models\AccessKeyCard::whereIn('id', $keyCardIds)->pluck('card_number')->toArray();
                    if (!empty($keyCards)) {
                        $additionalSystems[] = "Access Key Cards: " . implode(', ', $keyCards);
                    }
                }
            }
            
            if (!empty($additionalSystems)) {
                $requestSummary = array_merge($requestSummary, $additionalSystems);
            }
            
            // Hardware Request
            if ($ictAccessResource->hardware_request) {
                $requestSummary[] = "Hardware: " . $ictAccessResource->hardware_request;
            }

            $emailsToSend = [];
            $nextApproverRole = null;

            Log::info('ICT Approval: Entering role switch', ['role' => $roles]);

            switch ($roles) {
                case 'line-manager':
                case 'ceo':
                case 'coo':
                case 'cfo':
                case 'cms':
                case 'ccdro':
                    Log::info('ICT Approval: Case line-manager/HEC/CEO - forwarding to HR');
                    $nextApproverRole = 'hr';
                    break;
                case 'hr':
                    // Mark all HR approvals as complete
                    $workflowHistoryHR = WorkFlowHistory::where('work_flow_id', $workflow->id)
                        ->join('users', 'users.id', '=', 'work_flow_histories.attended_by')
                        ->where('work_flow_histories.status', 0)
                        ->whereHas('user.roles', function ($query) {
                            $query->where('name', 'hr');
                        })
                        ->select('work_flow_histories.*')
                        ->get();

                    foreach ($workflowHistoryHR as $whr) {
                        $whr->status = 1;
                        $whr->who_approve = $user->id;
                        $whr->comments = 'Approved';
                        if (!$whr->save()) {
                            throw new \Exception('Failed to update HR workflow history');
                        }
                    }

                    $nextApproverRole = 'it';
                    break;
                case 'it':
                    // Mark all IT approvals as complete and finalize workflow
                    $workflowHistoryIT = WorkFlowHistory::where('work_flow_id', $workflow->id)
                        ->join('users', 'users.id', '=', 'work_flow_histories.attended_by')
                        ->where('work_flow_histories.status', 0)
                        ->whereHas('user.roles', function ($query) {
                            $query->where('name', 'it');
                        })
                        ->select('work_flow_histories.*')
                        ->get();

                    foreach ($workflowHistoryIT as $wit) {
                        $wit->status = 1;
                        $wit->who_approve = $user->id;
                        $wit->comments = 'Approved';
                        if (!$wit->save()) {
                            throw new \Exception('Failed to update IT workflow history');
                        }
                    }

                    // Update workflow status to approved
                    $workflow->work_flow_status = "approved";
                    $workflow->work_flow_completed = 1;
                    if (!$workflow->save()) {
                        throw new \Exception('Failed to update workflow status');
                    }

                    // Update ICT access resource status
                    $ictAccessResource->status = 1;
                    if (!$ictAccessResource->save()) {
                        throw new \Exception('Failed to update ICT access resource status');
                    }

                    DB::commit();

                    Log::info('ICT Access Request fully approved', [
                        'access_id' => $request->access_id,
                        'approved_by' => $user->id
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'ICT Access Request has been fully approved.'
                    ], 200);

                default:
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'No valid role found for approval.'
                    ], 400);
            }

            // Forward to next approver
            $ict = new IctAccessController();

            if ($nextApproverRole == 'hr' || $nextApproverRole == 'it') {
                // Get all users with the specified role
                $approvers = User::role($nextApproverRole)->get();
                if ($approvers->isEmpty()) {
                    DB::rollBack();
                    Log::error('ICT Approval: No approvers found', ['role' => $nextApproverRole]);
                    return response()->json([
                        'success' => false,
                        'message' => "No approvers with role {$nextApproverRole} found."
                    ], 404);
                }

                // Save workflow history for each approver
                foreach ($approvers as $approver) {
                    $input = [
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $approver->id,
                        'status' => '0',
                        'remark' => 'Forwarded for approval',
                        'attend_date' => Carbon::now()->format('d F Y'),
                        'parent_id' => $workflowHistory->id
                    ];
                    $ict->saveWorkflowHistory($input);

                    // Prepare email for queue
                    $requesterFullName = trim(($requesterUser->fname ?? '') . ' ' . ($requesterUser->lname ?? '')) ?: $requesterUser->username;
                    $requestDetails = [
                        'forwarded_by' => $requesterFullName,
                        'request' => "IT Access Form",
                        'requestDate' => Carbon::now()->format('d F Y'),
                        'user_type' => $ictAccessResource->user_type ?? null,
                        'access_required' => $ictAccessResource->access_required ?? null,
                        'request_summary' => $requestSummary,
                    ];
                    $emailsToSend[] = [
                        'to' => $approver->email,
                        'mailable' => new ApprovalRequestNotification($approver, $requestDetails)
                    ];
                }
            } else {
                // Handle single approver case for other roles
                $approver = User::role($nextApproverRole)->first();
                if (!$approver) {
                    DB::rollBack();
                    Log::error('ICT Approval: Next approver not found', ['role' => $nextApproverRole]);
                    return response()->json([
                        'success' => false,
                        'message' => "Next approver with role {$nextApproverRole} not found."
                    ], 404);
                }

                // Save workflow history for single approver
                $input = [
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $approver->id,
                    'status' => '0',
                    'remark' => 'Forwarded for approval',
                    'attend_date' => Carbon::now()->format('d F Y'),
                    'parent_id' => $workflowHistory->id
                ];
                $ict->saveWorkflowHistory($input);

                // Prepare email for queue
                $requesterFullName = trim(($requesterUser->fname ?? '') . ' ' . ($requesterUser->lname ?? '')) ?: $requesterUser->username;
                $requestDetails = [
                    'forwarded_by' => $requesterFullName,
                    'request' => "IT Access Form",
                    'requestDate' => Carbon::now()->format('d F Y'),
                    'user_type' => $ictAccessResource->user_type ?? null,
                    'access_required' => $ictAccessResource->access_required ?? null,
                    'request_summary' => $requestSummary,
                ];
                $emailsToSend[] = [
                    'to' => $approver->email,
                    'mailable' => new ApprovalRequestNotification($approver, $requestDetails)
                ];
            }

            Log::info('ICT Approval: About to commit transaction', [
                'next_approver_role' => $nextApproverRole,
                'email_count' => count($emailsToSend)
            ]);

            DB::commit();

            Log::info('ICT Approval: Transaction committed successfully');

            // Send emails via queue (after commit)
            foreach ($emailsToSend as $emailJob) {
                try {
                    Mail::to($emailJob['to'])->queue($emailJob['mailable']);
                } catch (\Throwable $mailEx) {
                    Log::error('ICT Approval: Email queue failed', [
                        'to' => $emailJob['to'],
                        'error' => $mailEx->getMessage()
                    ]);
                    // Don't fail the request if email fails
                }
            }

            Log::info('ICT Access Request approved and forwarded', [
                'access_id' => $request->access_id,
                'approved_by' => $user->id,
                'next_role' => $nextApproverRole
            ]);

            return response()->json([
                'success' => true,
                'message' => 'The request has been forwarded to the next approver.'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ICT Approval: Exception occurred', [
                'access_id' => $request->access_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the approval. Please try again.'
            ], 500);
        }
    }


    public function rejectForm(Request $request)
    {
        try {
            $request->validate([
                'access_id' => 'required|exists:ict_access_resources,id',
                'reason' => 'required|string|min:10|max:1000'
            ]);

            DB::beginTransaction();

            // Find the workflow associated with the access request
            $workflow = Workflow::where('ict_request_resource_id', $request->access_id)->first();
            if (!$workflow) {
                DB::rollBack();
                Log::error('ICT Rejection: Workflow not found', ['access_id' => $request->access_id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Workflow not found for this access request.'
                ], 404);
            }

            // Find the current workflow history where status is 0 (pending)
            $workflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->where('attended_by', Auth::id())
                ->first();

            if (!$workflowHistory) {
                DB::rollBack();
                Log::error('ICT Rejection: WorkflowHistory not found', [
                    'workflow_id' => $workflow->id,
                    'user_id' => Auth::id()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No pending approval found for this request.'
                ], 404);
            }

            $user = Auth::user();
            $ictAccessResource = IctAccessResource::findOrFail($request->access_id);
            $requester = $ictAccessResource->user;

            if (!$requester) {
                throw new \Exception('Requester user not found');
            }

            // Update the status to rejected and save the reason
            $workflowHistory->status = -1;
            $workflowHistory->who_approve = $user->id;
            $workflowHistory->rejection_reason = $request->reason;
            $workflowHistory->comments = $request->reason;
            if (!$workflowHistory->save()) {
                throw new \Exception('Failed to update workflow history');
            }

            // Update workflow status to rejected
            $workflow->work_flow_status = "rejected";
            $workflow->work_flow_completed = 1;
            if (!$workflow->save()) {
                throw new \Exception('Failed to update workflow status');
            }

            // Update ICT access resource status
            $ictAccessResource->status = -1;
            if (!$ictAccessResource->save()) {
                throw new \Exception('Failed to update ICT access resource status');
            }

            DB::commit();

            // Send rejection email via queue (after commit)
            try {
                Mail::to($requester->email)->queue(new IctAccessRejectionNotification(
                    $ictAccessResource,
                    $workflow,
                    $requester,
                    $request->reason,
                    $user
                ));
            } catch (\Throwable $mailEx) {
                Log::error('ICT Rejection: Email queue failed', [
                    'to' => $requester->email,
                    'error' => $mailEx->getMessage()
                ]);
                // Don't fail the request if email fails
            }

            Log::info('ICT Access Request rejected', [
                'access_id' => $request->access_id,
                'rejected_by' => $user->id,
                'reason_length' => strlen($request->reason)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Form rejected successfully.'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ICT Rejection: Exception occurred', [
                'access_id' => $request->access_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the rejection. Please try again.'
            ], 500);
        }
    }


    public function getClearance(Request $request)
    {
        $user = Auth::user();
        
        // First, get the clearance form
        $clearanceForm = ClearanceForm::findOrFail($request->id);
        
        // Check if user is the owner of the form
        $isOwner = $clearanceForm->userId === $user->id;
        
        // Check if user has permission to view clearance forms
        $hasViewPermission = $user->hasPermissionTo('view clearance forms');
        
        // Check if user is an approver (has a workflow history record - either pending or approved)
        $workflow = Clearance_work_flow::where('requested_resource_id', $clearanceForm->id)->first();
        $isApprover = false;
        $userApprovalStep = null;
        if ($workflow) {
            // Check for both pending (status 0) and approved (status 1) workflow history
            $userApprovalHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                ->where('attended_by', $user->id)
                ->whereIn('status', [0, 1]) // Both pending and approved
                ->first();
            if ($userApprovalHistory) {
                $isApprover = true;
                $userApprovalStep = $userApprovalHistory->step_name;
            }
            
            // Also check for approved steps (for review mode)
            $userApprovedHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                ->where('attended_by', $user->id)
                ->where('status', 1) // Only approved steps
                ->first();
            if ($userApprovedHistory && !$userApprovalStep) {
                $userApprovalStep = $userApprovedHistory->step_name;
            }
        }
        
        // HR can view all completed clearance forms
        $isHR = $user->hasRole('hr');
        $workflowCompleted = $workflow && $workflow->work_flow_completed == 1;
        $canHRViewCompleted = $isHR && $workflowCompleted;
        
        // Previous approvers (Line Manager, Finance Officer, IT) can review after they have approved
        // (even before full workflow completion, so they can track progress)
        $canReviewPreviousApproval = false;
        if ($userApprovalStep && $workflow) {
            $userApprovedHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                ->where('attended_by', $user->id)
                ->where('status', 1)
                ->where('step_name', $userApprovalStep)
                ->first();
            if ($userApprovedHistory) {
                $userRoles = $user->getRoleNames();
                $roleMatches = false;
                if ($userApprovalStep === 'Line Manager' && $userRoles->contains('line-manager')) {
                    $roleMatches = true;
                } elseif ($userApprovalStep === 'Finance Officer' && $user->hasFinanceOfficerRole()) {
                    $roleMatches = true;
                } elseif ($userApprovalStep === 'IT Officer' && $userRoles->contains('it')) {
                    $roleMatches = true;
                }
                $canReviewPreviousApproval = $roleMatches;
            }
        }
        
        // Allow access if:
        // 1. User is the owner
        // 2. User is an approver (has pending or approved workflow history)
        // 3. User is HR viewing completed form
        // 4. User is a previous approver reviewing after HR approval
        // 5. User has "view clearance forms" permission
        if (!$isOwner && !$isApprover && !$canHRViewCompleted && !$canReviewPreviousApproval && !$hasViewPermission) {
            Alert::error('Access Denied', 'You do not have permission to view this clearance form.');
            return redirect()->route('dashboard');
        }
        
        // Get the employee user to access their department (load before using)
        $employeeUser = User::with('department', 'jobTitle')->find($clearanceForm->userId);
        if (!$employeeUser) {
            Alert::error('Error', 'Employee information not found.');
            return redirect()->route('dashboard');
        }
        
        // Use the already loaded clearanceForm model directly (has all fields from database)
        // This ensures all Line Manager fields are properly loaded
        $clearance = $clearanceForm;
        
        // Log Line Manager fields to verify they're loaded from database
        \Log::info('Clearance Form - Line Manager fields loaded', [
            'clearance_id' => $clearanceForm->id,
            'changing_room_keys' => $clearanceForm->changing_room_keys,
            'office_keys' => $clearanceForm->office_keys,
            'mobile_phone' => $clearanceForm->mobile_phone,
            'camera' => $clearanceForm->camera,
            'ccbrt_uniforms' => $clearanceForm->ccbrt_uniforms,
            'office_car_keys' => $clearanceForm->office_car_keys,
            'other_items' => $clearanceForm->other_items,
            'handover_report_received' => $clearanceForm->handover_report_received,
            'work_responsibilities_transferred' => $clearanceForm->work_responsibilities_transferred,
            'projects_tasks_closed' => $clearanceForm->projects_tasks_closed,
            'line_manager_comments' => $clearanceForm->line_manager_comments,
        ]);
        
        // Add user and related data to clearance object for view compatibility
        $clearance->fname = $employeeUser->fname;
        $clearance->mname = $employeeUser->mname;
        $clearance->lname = $employeeUser->lname;
        $clearance->email = $employeeUser->email;
        $clearance->mobile = $employeeUser->mobile;
        $clearance->username = $employeeUser->username;
        $clearance->ccbrt_code = $employeeUser->ccbrt_code;
        $clearance->dept_name = $employeeUser->department ? $employeeUser->department->dept_name : null;
        $clearance->entity_name = $employeeUser->department && $employeeUser->department->divisions
            ? $employeeUser->department->divisions->pluck('name')->filter()->implode(', ')
            : null;
        $clearance->job_title = $employeeUser->jobTitle ? $employeeUser->jobTitle->job_title : null;
        $clearance->access_id = $clearanceForm->id;

        $staffContract = Contract::where('user_id', $employeeUser->id)->latest('id')->first();
        $clearance->date_of_hire = $employeeUser->starting_date
            ?: ($staffContract?->start_date ?: $clearanceForm->date_of_hire);
        $clearance->last_working_day = $employeeUser->ending_date
            ?: ($staffContract?->end_date ?: $clearanceForm->last_working_day);
        $clearance->end_of_contract = $employeeUser->ending_date
            ?: ($staffContract?->end_date ?: $clearanceForm->end_of_contract);
        
        // Get workflow history for current user if exists
        $userWorkflowHistory = null;
        if ($workflow) {
            $userWorkflowHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                ->where('attended_by', $user->id)
                ->first();
            if ($userWorkflowHistory) {
                $clearance->forwarded_by = $userWorkflowHistory->forwarded_by;
            }
        }

        $approver = null;
        $lineManager = null;

        if (auth()->user()->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro'])) {
            $roleMapping = [
                'COO' => 'coo',
                'CFO' => 'cfo',
                'CMS' => 'cms',
                'CCDRO' => 'ccdro',
            ];
            // Check if the role mapping is working
            $requiredRole = $roleMapping[$clearance->hec_level_name ?? ''] ?? null;

            // Fetch the approver (only if workflow exists and role is mapped)
            $workFlowId = $workflow ? $workflow->id : null;
            if ($workFlowId && $requiredRole) {
                $approver = User::whereHas('roles', function ($query) use ($requiredRole) {
                    $query->where('name', $requiredRole);
                })
                    ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
                    ->where('clearance_work_flow_histories.work_flow_id', $workFlowId)
                    ->where('clearance_work_flow_histories.status', 1)
                    ->first();
            }

            //dd($approver);

        } else {
            $workFlowId = $workflow ? $workflow->id : null;
            if ($employeeUser->deptId && $workFlowId) {
            $lineManager = User::whereHas('roles', function ($query) {
                $query->where('name', 'line-manager');
            })
                    ->where('users.deptId', $employeeUser->deptId)
                ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
                ->where('clearance_work_flow_histories.work_flow_id', $workFlowId)
                ->where('clearance_work_flow_histories.status', 1)
                ->first();
            }
        }

        // Get approvers only if workflow exists
        $financeOfficer = null;
        $itOfficer = null;
        $hrOfficer = null;
        $workFlowId = $workflow ? $workflow->id : null;

        if ($workFlowId && $employeeUser->deptId) {
        $financeOfficer = User::whereHas('roles', function ($query) {
            $query->where('name', 'finance officer')
                ->orWhere('name', 'like', 'finance-officer-%');
        })
                ->where('users.deptId', $employeeUser->deptId)
            ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
            ->where('clearance_work_flow_histories.work_flow_id', $workFlowId)
            ->where('clearance_work_flow_histories.status', 1)
            ->first();

        $itOfficer = User::whereHas('roles', function ($query) {
            $query->where('name', 'it');
        })
                ->where('users.deptId', $employeeUser->deptId)
            ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
            ->where('clearance_work_flow_histories.work_flow_id', $workFlowId)
            ->where('clearance_work_flow_histories.status', 1)
            ->first();

        $hrOfficer = User::whereHas('roles', function ($query) {
            $query->where('name', 'hr');
        })
                ->where('users.deptId', $employeeUser->deptId)
            ->join('clearance_work_flow_histories', 'clearance_work_flow_histories.who_approve', '=', 'users.id')
            ->where('clearance_work_flow_histories.work_flow_id', $workFlowId)
            ->where('clearance_work_flow_histories.status', 1)
            ->first();
        }

        // Get all previous approvers from workflow history (approved ones only)
        $allApprovers = collect([]);
        $allApproversWithDetails = collect([]);
        // Get work_flow_id from workflow relationship if it exists
        $workFlowId = $workflow ? $workflow->id : null;
        
        \Log::info('Getting approval details', [
            'clearance_id' => $clearanceForm->id,
            'workflow_id' => $workFlowId,
            'workflow_exists' => $workflow ? true : false
        ]);
        
        if ($workFlowId) {
            // Check if workflow is completed - if so, only show HR approvers for reference
            $workflowCompleted = $workflow && $workflow->work_flow_completed == 1;
            
            $allApprovers = Clearance_work_flow_history::where('work_flow_id', $workFlowId)
                ->where('status', 1) // Only approved steps
                ->where(function($query) {
                    $query->whereNotNull('who_approve')
                          ->orWhereNotNull('attended_by'); // Include entries where attended_by is set (for LM created forms)
                })
                ->orderBy('created_at', 'asc')
                ->get();
            
            \Log::info('Workflow histories found', [
                'count' => $allApprovers->count(),
                'histories' => $allApprovers->map(function($h) {
                    return [
                        'id' => $h->id,
                        'step_name' => $h->step_name,
                        'who_approve' => $h->who_approve,
                        'attended_by' => $h->attended_by,
                        'status' => $h->status
                    ];
                })
            ]);
            
            $allApprovers = $allApprovers->map(function ($history) use ($clearance) {
                    // Use who_approve if available, otherwise fall back to attended_by (for LM created forms)
                    $approverId = $history->who_approve ?? $history->attended_by;
                    $approver = User::find($approverId);
                    $stepName = $history->step_name ?? 'N/A';
                    
                    // Get section-specific details based on approval step
                    $sectionDetails = [];
                    if ($stepName === 'Line Manager') {
                        $sectionDetails = [
                            'Handover report received' => $clearance->handover_report_received ? 'Yes' : 'No',
                            'Work responsibilities transferred' => $clearance->work_responsibilities_transferred ? 'Yes' : 'No',
                            'Projects/tasks closed' => $clearance->projects_tasks_closed ? 'Yes' : 'No',
                        ];
                    } elseif ($stepName === 'Finance Officer') {
                        $sectionDetails = [
                            'Repaid advance on Salary' => $clearance->repaid_salary_advance ?? 'N/A',
                            'Salary advance amount' => isset($clearance->repaid_salary_advance_amount) && $clearance->repaid_salary_advance_amount !== null ? number_format((float) $clearance->repaid_salary_advance_amount, 2) : 'N/A',
                            'Staff has bonding agreement' => $clearance->has_bonding_agreement ?? 'N/A',
                            'Bonding agreement amount' => isset($clearance->bonding_agreement_amount) && $clearance->bonding_agreement_amount !== null ? number_format((float) $clearance->bonding_agreement_amount, 2) : 'N/A',
                            'Staff informed Finance of outstanding loan balances' => $clearance->loan_balances_informed ?? 'N/A',
                            'All salary advances cleared' => $clearance->all_salary_advances_cleared ? 'Yes' : 'No',
                            'Allowances reconciled' => $clearance->allowances_reconciled ? 'Yes' : 'No',
                            'Pending claims settled' => $clearance->pending_claims_settled ? 'Yes' : 'No',
                            'Outstanding loans recovered' => $clearance->outstanding_loans_recovered ? 'Yes' : 'No',
                            'Repaid outstanding imprest' => $clearance->repaid_outstanding_imprest ?? 'N/A',
                            'Cleared/accounted for imprest or business advance' => $clearance->cleared_imprest_or_business_advance ?? 'N/A',
                            'Employee eligible for final payment' => isset($clearance->employee_eligible_for_final_payment) ? ($clearance->employee_eligible_for_final_payment ? 'Yes' : 'No') : 'N/A',
                            'Finance Comments' => $clearance->finance_comments ?? 'N/A',
                        ];
                    } elseif ($stepName === 'HR Officer') {
                        $sectionDetails = [
                            'Resignation letter received' => $clearance->resignation_letter_received ? 'Yes' : 'No',
                            'Exit interview completed' => $clearance->exit_interview_completed ? 'Yes' : 'No',
                            'Leave balance confirmed' => $clearance->leave_balance_confirmed ? 'Yes' : 'No',
                            'Contract file reviewed' => $clearance->contract_file_reviewed ? 'Yes' : 'No',
                            'Office keys returned' => $clearance->office_keys_returned ?? 'N/A',
                            'Uniform/PPE returned' => $clearance->uniform_ppe_returned ?? 'N/A',
                            'Tools & equipment returned' => $clearance->tools_equipment_returned ?? 'N/A',
                            'Vehicle clearance' => $clearance->vehicle_clearance ?? 'N/A',
                            'Changing Room Keys' => $clearance->changing_room_keys ?? 'N/A',
                            'Office Keys' => $clearance->office_keys ?? 'N/A',
                            'Mobile Phone' => $clearance->mobile_phone ?? 'N/A',
                            'Camera' => $clearance->camera ?? 'N/A',
                            'CCBRT Uniforms' => $clearance->ccbrt_uniforms ?? 'N/A',
                            'Office Car Keys' => $clearance->office_car_keys ?? 'N/A',
                            'Other items' => $clearance->other_items ?? 'N/A',
                        ];
                    } elseif ($stepName === 'IT Officer') {
                        $sectionDetails = [
                            'Laptop returned' => $clearance->laptop_returned ?? 'N/A',
                            'Keyboard/Mouse returned' => $clearance->keyboard_mouse_returned ?? 'N/A',
                            'Phone returned' => $clearance->phone_returned ?? 'N/A',
                            'ID Card returned' => $clearance->id_card_returned ?? 'N/A',
                            'IT Ticket No' => $clearance->it_ticket_no ?? 'N/A',
                        ];
                    }
                    
                    $approverData = [
                        'step_name' => $stepName,
                        'approver_name' => $approver ? trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) : 'N/A',
                        'approver_email' => $approver ? ($approver->email ?? 'N/A') : 'N/A',
                        'signature' => $approver ? ($approver->signature ?? null) : null,
                        'approval_date' => $history->updated_at ?? $history->created_at,
                        'remark' => $history->remark ?? '',
                        'approver_id' => $approverId,
                        'section_details' => $sectionDetails
                    ];
                    
                    \Log::info('Approver details mapped', [
                        'step_name' => $stepName,
                        'approver_name' => $approverData['approver_name'],
                        'has_signature' => !empty($approverData['signature']),
                        'approver_id' => $approverId,
                        'approver_found' => $approver ? true : false
                    ]);
                    
                    return $approverData;
                });
            
            $allApproversWithDetails = $allApprovers;
            
            \Log::info('Final approval details collection', [
                'total_approvers' => $allApproversWithDetails->count(),
                'approver_names' => $allApproversWithDetails->pluck('approver_name')->toArray()
            ]);
        }

        // Get current approval step for the logged-in user
        $currentApprovalStep = null;
        $canEdit = false;
        $isFinalApproval = false;
        if ($workflow) {
            // Try exact attended_by match first (Line Manager, Finance Officer, or previously assigned IT/HR)
            $currentHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->where('attended_by', $user->id)
                ->first();

            // For Finance/IT/HR: fall back to role-based step detection (any user with that role can approve)
            if (!$currentHistory) {
                $userRoles = $user->getRoleNames();
                if ($user->hasFinanceOfficerRole() && $workflow->work_flow_status === 'Pending Approval - Finance Officer') {
                    $currentHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                        ->where('status', 0)
                        ->where('step_name', 'Finance Officer')
                        ->first();
                } elseif ($userRoles->contains('it') && $workflow->work_flow_status === 'Pending Approval - IT Officer') {
                    $currentHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                        ->where('status', 0)
                        ->where('step_name', 'IT Officer')
                        ->first();
                } elseif ($userRoles->contains('hr') && $workflow->work_flow_status === 'Pending Approval - HR Officer') {
                    $currentHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                        ->where('status', 0)
                        ->where('step_name', 'HR Officer')
                        ->first();
                }
            }

            if ($currentHistory) {
                $currentApprovalStep = $currentHistory->step_name ?? null;
                if ($currentApprovalStep === 'HR Officer' || $workflow->work_flow_status === 'Pending Approval - HR Officer') {
                    $isFinalApproval = true;
                    $canEdit = false;
                } else {
                    $canEdit = true;
                }
            }
        }
        
        // If previous approver is reviewing after HR approval, make everything read-only
        if ($canReviewPreviousApproval && $workflowCompleted) {
            $canEdit = false;
            $isFinalApproval = false; // Not final approval, just review
        }
        
        // Debug: Log the values (remove in production)
        \Log::info('Clearance Form View Variables', [
            'user_id' => $user->id,
            'canEdit' => $canEdit,
            'currentApprovalStep' => $currentApprovalStep,
            'workflow_id' => $workflow ? $workflow->id : null,
            'clearance_id' => $clearanceForm->id
        ]);

        //  dd($clearance);
        return view('exit_form', compact('clearance', 'user', 'lineManager', 'financeOfficer', 'itOfficer', 'hrOfficer', 'approver', 'allApprovers', 'allApproversWithDetails', 'currentApprovalStep', 'canEdit', 'workflow', 'isFinalApproval', 'workflowCompleted', 'userApprovalStep', 'canReviewPreviousApproval'));
    }

    public function downloadClearancePDF($id)
    {
        $user = Auth::user();
        
        // Check if user is HR
        if (!$user->hasRole('hr')) {
            abort(403, 'Access Denied. Only HR can download clearance forms.');
        }

        // Get the clearance form by access_id (which is the id field)
        $clearanceForm = ClearanceForm::findOrFail($id);
        
        // Check if form is fully approved
        $workflow = Clearance_work_flow::where('requested_resource_id', $clearanceForm->id)->first();
        if (!$workflow || $workflow->work_flow_completed != 1) {
            Alert::error('Error', 'This clearance form has not been fully approved yet.');
            return redirect()->back();
        }

        // Get all approvers with details (same logic as getClearance)
        $allApprovers = [];
        $allApproversWithDetails = collect([]);
        
        if ($workflow) {
            $histories = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                ->where('status', 1)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($histories as $history) {
                $approver = User::find($history->attended_by);
                if ($approver) {
                    $allApprovers[] = [
                        'step_name' => $history->step_name,
                        'approver_name' => trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')),
                        'approval_date' => $history->updated_at,
                        'signature' => $approver->signature ?? null,
                        'comments' => $history->remark ?? null,
                    ];
                }
            }
            
            $allApproversWithDetails = collect($allApprovers);
        }

        // Get employee details
        $employeeUser = User::with('department', 'jobTitle')->find($clearanceForm->userId);
        if (!$employeeUser) {
            Alert::error('Error', 'Employee information not found.');
            return redirect()->back();
        }

        // Add user and related data to clearance object for view compatibility
        $clearance = $clearanceForm;
        $clearance->fname = $employeeUser->fname;
        $clearance->mname = $employeeUser->mname;
        $clearance->lname = $employeeUser->lname;
        $clearance->email = $employeeUser->email;
        $clearance->mobile = $employeeUser->mobile;
        $clearance->username = $employeeUser->username;
        $clearance->ccbrt_code = $employeeUser->ccbrt_code;
        $clearance->dept_name = $employeeUser->department ? $employeeUser->department->dept_name : null;
        $clearance->job_title = $employeeUser->jobTitle ? $employeeUser->jobTitle->job_title : null;
        $clearance->access_id = $clearanceForm->id;
        
        // Format dates
        $submittedDate = $clearanceForm->created_at
            ? \Carbon\Carbon::parse($clearanceForm->created_at)->format('d F Y, H:i')
            : 'N/A';
        $dateOfHire = $clearanceForm->date_of_hire
            ? \Carbon\Carbon::parse($clearanceForm->date_of_hire)->format('d F Y')
            : 'N/A';
        $lastWorkingDay = $clearanceForm->last_working_day
            ? \Carbon\Carbon::parse($clearanceForm->last_working_day)->format('d F Y')
            : 'N/A';
        $endOfContract = $clearanceForm->end_of_contract
            ? \Carbon\Carbon::parse($clearanceForm->end_of_contract)->format('d F Y')
            : 'N/A';

        // Generate PDF
        $pdf = Pdf::loadView('clearance.pdf', compact(
            'clearance',
            'allApproversWithDetails',
            'submittedDate',
            'dateOfHire',
            'lastWorkingDay',
            'endOfContract',
            'workflow'
        ));

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'dpi' => 96
        ]);

        $filename = 'clearance-form-' . $clearanceForm->access_id . '-' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    public function approveClearanceForm(Request $request)
    {
        DB::beginTransaction();
        
        try {
        $workflow = Clearance_work_flow::where('requested_resource_id', $request->access_id)->first();

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

            // Find the corresponding workflow history
            // Try exact attended_by match first, then fall back to role-based matching
            $userRolesForApproval = Auth::user()->getRoleNames();

            // First try attended_by (exact assignment)
            $workflowHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->where('attended_by', Auth::id())
                ->first();

            // Fall back to role-based step detection for Finance/IT/HR
            if (!$workflowHistory) {
                if (Auth::user()->hasFinanceOfficerRole() && $workflow->work_flow_status === 'Pending Approval - Finance Officer') {
                    $workflowHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                        ->where('status', 0)
                        ->where('step_name', 'Finance Officer')
                        ->first();
                } elseif ($userRolesForApproval->contains('it') && $workflow->work_flow_status === 'Pending Approval - IT Officer') {
                    $workflowHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                        ->where('status', 0)
                        ->where('step_name', 'IT Officer')
                        ->first();
                } elseif ($userRolesForApproval->contains('hr') && $workflow->work_flow_status === 'Pending Approval - HR Officer') {
                    $workflowHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                        ->where('status', 0)
                        ->where('step_name', 'HR Officer')
                        ->first();
                }
            }

            if (!$workflowHistory) {
                DB::rollBack();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No pending approval found for you. This form may have already been approved or is not assigned to you.',
                        'error' => 'No pending approval found for you.'
                    ], 400);
                }
                Alert::error('Error', 'No pending approval found for you.');
                return redirect()->back()->with('error', 'No pending approval found for you.');
            }

            // Mark current step as approved
                $workflowHistory->status = 1;
                $workflowHistory->who_approve = Auth::id();
                $workflowHistory->attended_by = Auth::id(); // Record who actually approved
                
                // Get current step name
                $currentStep = $workflowHistory->step_name;
                
                // Save comments/remarks based on approval step
                $comments = '';
                if ($currentStep === 'Line Manager' && $request->has('line_manager_comments')) {
                    $comments = $request->line_manager_comments;
                } elseif ($currentStep === 'Finance Officer' && $request->has('finance_comments')) {
                    $comments = $request->finance_comments;
                } elseif ($currentStep === 'IT Officer' && $request->has('it_comments')) {
                    $comments = $request->it_comments;
                } elseif ($currentStep === 'HR Officer' && $request->has('hr_comments')) {
                    $comments = $request->hr_comments;
                }
                
                if (!empty($comments)) {
                    $workflowHistory->remark = $comments;
                }
                
                if (!$workflowHistory->save()) {
                    DB::rollBack();
                    throw new \Exception('Failed to update workflow history status');
                }

                // For role-based steps (IT/HR), close all OTHER pending entries so no other user sees it
                if (in_array($currentStep, ['IT Officer', 'HR Officer'])) {
                    Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                        ->where('step_name', $currentStep)
                        ->where('status', 0)
                        ->where('id', '!=', $workflowHistory->id)
                        ->update([
                            'status'      => 1,
                            'who_approve' => Auth::id(),
                            'remark'      => 'Closed — approved by ' . Auth::user()->fname . ' ' . Auth::user()->lname,
                        ]);
                }

        $user = Auth::user();
        $roles = $user->getRoleNames();
            $clearanceForm = ClearanceForm::find($request->access_id);
            $submitter = $clearanceForm->user ?? User::find($workflow->user_id);

            // Initialize variables
            $isFinalApproval = false;
            $updateData = [];
            
            // Helper function to normalize Yes/No/N/A values to string (for string columns)
            $normalizeValue = function($value) {
                if ($value === 'Yes' || $value === 'yes' || $value === '1' || $value === 1 || $value === true) {
                    return 'Yes';
                } elseif ($value === 'No' || $value === 'no' || $value === '0' || $value === 0 || $value === false) {
                    return 'No';
                } elseif ($value === 'N/A' || $value === 'Not Applicable' || $value === 'n/a') {
                    return 'N/A';
                } elseif ($value === null || $value === '') {
                    return 'N/A'; // Default to N/A for empty values
                }
                return $value; // Return as-is if it's already a valid string
            };

            // Helper function to convert Yes/No/N/A to boolean (integer) for boolean columns
            $convertToBoolean = function($value) {
                if ($value === 'Yes' || $value === 'yes' || $value === '1' || $value === 1 || $value === true) {
                    return 1;
                } elseif ($value === 'No' || $value === 'no' || $value === '0' || $value === 0 || $value === false) {
                    return 0;
                } elseif ($value === 'N/A' || $value === 'Not Applicable' || $value === 'n/a' || $value === null || $value === '') {
                    return 0;
                }
                return 0;
            };

            // Helper for tri-state string columns (Yes / No / N/A) — used by Finance fields
            $convertToTriState = function($value) {
                if ($value === 'Yes' || $value === 'yes' || $value === '1' || $value === 1 || $value === true) {
                    return 'Yes';
                } elseif ($value === 'N/A' || $value === 'Not Applicable' || $value === 'n/a') {
                    return 'N/A';
                }
                return 'No';
            };

            if ($currentStep === 'Line Manager') {
                // Check if form was created by line manager for staff (data already exists)
                // Only update if fields are actually provided in the request
                $wasCreatedByLM = false;
                if ($clearanceForm->workflow) {
                    $lmHistory = Clearance_work_flow_history::where('work_flow_id', $clearanceForm->workflow->id)
                        ->where('step_name', 'Line Manager')
                        ->where('status', 1)
                        ->where('forwarded_by', '!=', $clearanceForm->userId)
                        ->first();
                    $wasCreatedByLM = $lmHistory && $lmHistory->forwarded_by != $clearanceForm->userId;
                }
                
                // If form was created by LM for staff and data already exists, only update if new values are provided
                // Otherwise, allow normal updates for regular Line Manager approval
                if (!$wasCreatedByLM || !$clearanceForm->changing_room_keys || $clearanceForm->changing_room_keys === 'N/A') {
                    // String fields - save as "Yes", "No", or "N/A"
                    if ($request->has('handover_report_received')) {
                        $updateData['handover_report_received'] = $normalizeValue($request->handover_report_received);
                    }
                    if ($request->has('work_responsibilities_transferred')) {
                        $updateData['work_responsibilities_transferred'] = $normalizeValue($request->work_responsibilities_transferred);
                    }
                    if ($request->has('projects_tasks_closed')) {
                        $updateData['projects_tasks_closed'] = $normalizeValue($request->projects_tasks_closed);
                    }
                    // Comments - always save if present (can be empty string)
                    if ($request->has('line_manager_comments')) {
                        $updateData['line_manager_comments'] = $request->line_manager_comments ?? '';
                    }
                    // String fields - keep as is (Yes/No/N/A), always save if present
                    if ($request->has('changing_room_keys') && $request->changing_room_keys !== null) {
                        $updateData['changing_room_keys'] = $request->changing_room_keys;
                    }
                    if ($request->has('office_keys') && $request->office_keys !== null) {
                        $updateData['office_keys'] = $request->office_keys;
                    }
                    if ($request->has('mobile_phone') && $request->mobile_phone !== null) {
                        $updateData['mobile_phone'] = $request->mobile_phone;
                    }
                    if ($request->has('camera') && $request->camera !== null) {
                        $updateData['camera'] = $request->camera;
                    }
                    if ($request->has('ccbrt_uniforms') && $request->ccbrt_uniforms !== null) {
                        $updateData['ccbrt_uniforms'] = $request->ccbrt_uniforms;
                    }
                    if ($request->has('office_car_keys') && $request->office_car_keys !== null) {
                        $updateData['office_car_keys'] = $request->office_car_keys;
                    }
                    // Other items - always save if present (can be empty string)
                    if ($request->has('other_items')) {
                        $updateData['other_items'] = $request->other_items ?? '';
                    }
                } else {
                    // Form was created by LM for staff and data already exists - log and preserve existing data
                    \Log::info('Line Manager approval step skipped - data already exists from creation', [
                        'clearance_id' => $clearanceForm->id,
                        'existing_data' => [
                            'changing_room_keys' => $clearanceForm->changing_room_keys,
                            'handover_report_received' => $clearanceForm->handover_report_received,
                        ]
                    ]);
                }
            } elseif ($currentStep === 'Finance Officer') {
                // String fields
                if ($request->has('repaid_salary_advance')) {
                    $updateData['repaid_salary_advance'] = $request->repaid_salary_advance;
                }
                if ($request->has('repaid_salary_advance_details')) {
                    $updateData['repaid_salary_advance_details'] = $request->repaid_salary_advance_details;
                }
                if ($request->has('repaid_salary_advance_amount')) {
                    $rawSalaryAdvanceAmount = $request->repaid_salary_advance_amount;
                    $updateData['repaid_salary_advance_amount'] =
                        ($request->repaid_salary_advance ?? null) === 'Yes' && $rawSalaryAdvanceAmount !== null && $rawSalaryAdvanceAmount !== ''
                            ? (float) $rawSalaryAdvanceAmount
                            : null;
                }
                if ($request->has('repaid_salary_advance_initial_amount')) {
                    $v = $request->repaid_salary_advance_initial_amount;
                    $updateData['repaid_salary_advance_initial_amount'] = ($v !== null && $v !== '') ? (float)$v : null;
                }
                if ($request->has('repaid_salary_advance_balance')) {
                    $v = $request->repaid_salary_advance_balance;
                    $updateData['repaid_salary_advance_balance'] = ($v !== null && $v !== '') ? (float)$v : null;
                }
                if ($request->has('has_bonding_agreement')) {
                    $updateData['has_bonding_agreement'] = $request->has_bonding_agreement;
                }
                if ($request->has('has_bonding_agreement_details')) {
                    $updateData['has_bonding_agreement_details'] = $request->has_bonding_agreement_details;
                }
                $bondingAgreementAnswer = $request->has_bonding_agreement ?? null;
                if ($request->has('bonding_agreement_amount')) {
                    $rawAmount = $request->bonding_agreement_amount;
                    $updateData['bonding_agreement_amount'] = ($rawAmount !== null && $rawAmount !== '')
                        ? (float) $rawAmount
                        : null;
                }
                if ($request->has('bonding_agreement_initial_amount')) {
                    $v = $request->bonding_agreement_initial_amount;
                    $updateData['bonding_agreement_initial_amount'] = ($v !== null && $v !== '') ? (float)$v : null;
                }
                if ($request->has('bonding_agreement_balance')) {
                    $v = $request->bonding_agreement_balance;
                    $updateData['bonding_agreement_balance'] = ($v !== null && $v !== '') ? (float)$v : null;
                }
                if ($request->has('loan_amount_loaned')) {
                    $v = $request->loan_amount_loaned;
                    $updateData['loan_amount_loaned'] = ($v !== null && $v !== '') ? (float)$v : null;
                }
                if ($request->has('loan_amount_paid')) {
                    $v = $request->loan_amount_paid;
                    $updateData['loan_amount_paid'] = ($v !== null && $v !== '') ? (float)$v : null;
                }
                if ($request->has('loan_balance_remaining')) {
                    $v = $request->loan_balance_remaining;
                    $updateData['loan_balance_remaining'] = ($v !== null && $v !== '') ? (float)$v : null;
                }
                if ($request->has('allowances_initial_amount')) {
                    $v = $request->allowances_initial_amount;
                    $updateData['allowances_initial_amount'] = ($v !== null && $v !== '') ? (float)$v : null;
                }
                if ($request->has('allowances_balance')) {
                    $v = $request->allowances_balance;
                    $updateData['allowances_balance'] = ($v !== null && $v !== '') ? (float)$v : null;
                }
                if ($request->has('loan_balances_informed')) {
                    $updateData['loan_balances_informed'] = $request->loan_balances_informed ?? 'N/A';
                }
                if ($request->has('loan_balances_informed_details')) {
                    $updateData['loan_balances_informed_details'] = $request->loan_balances_informed_details;
                }
                // Tri-state string fields (Yes / No / N/A) — stored as varchar after migration
                if ($request->has('all_salary_advances_cleared')) {
                    $updateData['all_salary_advances_cleared'] = $convertToTriState($request->all_salary_advances_cleared);
                }
                if ($request->has('all_salary_advances_cleared_details')) {
                    $updateData['all_salary_advances_cleared_details'] = $request->all_salary_advances_cleared_details;
                }
                if ($request->has('allowances_reconciled')) {
                    $updateData['allowances_reconciled'] = $convertToTriState($request->allowances_reconciled);
                }
                if ($request->has('allowances_reconciled_details')) {
                    $updateData['allowances_reconciled_details'] = $request->allowances_reconciled_details;
                }
                if ($request->has('pending_claims_settled')) {
                    $updateData['pending_claims_settled'] = $convertToTriState($request->pending_claims_settled);
                }
                if ($request->has('pending_claims_settled_details')) {
                    $updateData['pending_claims_settled_details'] = $request->pending_claims_settled_details;
                }
                if ($request->has('outstanding_loans_recovered')) {
                    $updateData['outstanding_loans_recovered'] = $convertToTriState($request->outstanding_loans_recovered);
                }
                if ($request->has('outstanding_loans_recovered_details')) {
                    $updateData['outstanding_loans_recovered_details'] = $request->outstanding_loans_recovered_details;
                }
                if ($request->has('repaid_outstanding_imprest')) {
                    $updateData['repaid_outstanding_imprest'] = $request->repaid_outstanding_imprest ?? 'N/A';
                }
                if ($request->has('repaid_outstanding_imprest_details')) {
                    $updateData['repaid_outstanding_imprest_details'] = $request->repaid_outstanding_imprest_details;
                }
                if ($request->has('cleared_imprest_or_business_advance')) {
                    $updateData['cleared_imprest_or_business_advance'] = $request->cleared_imprest_or_business_advance ?? 'N/A';
                }
                if ($request->has('cleared_imprest_or_business_advance_details')) {
                    $updateData['cleared_imprest_or_business_advance_details'] = $request->cleared_imprest_or_business_advance_details;
                }
                if ($request->has('employee_eligible_for_final_payment')) {
                    $updateData['employee_eligible_for_final_payment'] = $convertToTriState($request->employee_eligible_for_final_payment);
                }
                if ($request->has('employee_eligible_for_final_payment_details')) {
                    $updateData['employee_eligible_for_final_payment_details'] = $request->employee_eligible_for_final_payment_details;
                }
                if ($request->has('finance_comments')) {
                    $updateData['finance_comments'] = $request->finance_comments;
                }
                // Handle finance supporting document upload
                if ($request->hasFile('finance_attachment')) {
                    $file = $request->file('finance_attachment');
                    $fileName = 'finance_clearance_' . time() . '_' . $clearanceForm->id . '.' . $file->getClientOriginalExtension();
                    $filePath = $file->storeAs('clearance_documents', $fileName, 'public');
                    $updateData['finance_attachment_path'] = $filePath;
                }
            } elseif ($currentStep === 'HR Officer') {
                // Boolean fields - only update if value is Yes or No (skip N/A to avoid null errors)
                if ($request->has('resignation_letter_received') && in_array($request->resignation_letter_received, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['resignation_letter_received'] = $convertToBoolean($request->resignation_letter_received);
                }
                if ($request->has('exit_interview_completed') && in_array($request->exit_interview_completed, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['exit_interview_completed'] = $convertToBoolean($request->exit_interview_completed);
                }
                if ($request->has('leave_balance_confirmed') && in_array($request->leave_balance_confirmed, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['leave_balance_confirmed'] = $convertToBoolean($request->leave_balance_confirmed);
                }
                if ($request->has('contract_file_reviewed') && in_array($request->contract_file_reviewed, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['contract_file_reviewed'] = $convertToBoolean($request->contract_file_reviewed);
                }
                
                // Reason for leaving (HR provides during final approval)
                // Only update if a valid enum value is provided
                if ($request->has('reason_for_leaving') && $request->reason_for_leaving !== null && $request->reason_for_leaving !== '') {
                    $validReasons = ['Resignation', 'Termination', 'Contract End', 'Retirement', 'Other'];
                    if (in_array($request->reason_for_leaving, $validReasons)) {
                        $updateData['reason_for_leaving'] = $request->reason_for_leaving;
                    }
                }
                if ($request->has('reason_for_leaving_other') && $request->reason_for_leaving_other !== null && $request->reason_for_leaving_other !== '') {
                    $updateData['reason_for_leaving_other'] = $request->reason_for_leaving_other;
                }
                // Boolean fields - only update if value is Yes or No (skip N/A to avoid null errors)
                if ($request->has('office_keys_returned') && in_array($request->office_keys_returned, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['office_keys_returned'] = $convertToBoolean($request->office_keys_returned);
                }
                if ($request->has('uniform_ppe_returned') && in_array($request->uniform_ppe_returned, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['uniform_ppe_returned'] = $convertToBoolean($request->uniform_ppe_returned);
                }
                if ($request->has('tools_equipment_returned') && in_array($request->tools_equipment_returned, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['tools_equipment_returned'] = $convertToBoolean($request->tools_equipment_returned);
                }
                if ($request->has('vehicle_clearance') && in_array($request->vehicle_clearance, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['vehicle_clearance'] = $convertToBoolean($request->vehicle_clearance);
                }
                // String fields - Items collected by last day of work
                if ($request->has('changing_room_keys')) {
                    $updateData['changing_room_keys'] = $request->changing_room_keys;
                }
                if ($request->has('office_keys')) {
                    $updateData['office_keys'] = $request->office_keys;
                }
                if ($request->has('mobile_phone')) {
                    $updateData['mobile_phone'] = $request->mobile_phone;
                }
                if ($request->has('camera')) {
                    $updateData['camera'] = $request->camera;
                }
                if ($request->has('ccbrt_uniforms')) {
                    $updateData['ccbrt_uniforms'] = $request->ccbrt_uniforms;
                }
                if ($request->has('office_car_keys')) {
                    $updateData['office_car_keys'] = $request->office_car_keys;
                }
                if ($request->has('other_items')) {
                    $updateData['other_items'] = $request->other_items;
                }
                // HR items collected fields
                if ($request->has('ccbrt_id_card')) {
                    $updateData['ccbrt_id_card'] = $request->ccbrt_id_card;
                }
                if ($request->has('ccbrt_name_tag')) {
                    $updateData['ccbrt_name_tag'] = $request->ccbrt_name_tag;
                }
                if ($request->has('nhif_cards')) {
                    $updateData['nhif_cards'] = $request->nhif_cards;
                }
                if ($request->has('work_permit_cancelled')) {
                    $updateData['work_permit_cancelled'] = $request->work_permit_cancelled;
                }
                if ($request->has('residence_permit_cancelled')) {
                    $updateData['residence_permit_cancelled'] = $request->residence_permit_cancelled;
                }
                if ($request->has('hr_comments')) {
                    $updateData['hr_comments'] = $request->hr_comments;
                }
                // HR force-approve override: save reason and flip eligibility to Yes
                if ($request->has('hr_force_approve_reason') && !empty($request->hr_force_approve_reason)) {
                    $updateData['hr_force_approve_reason'] = $request->hr_force_approve_reason;
                    // Override Finance Officer's "Not Eligible" decision
                    $updateData['employee_eligible_for_final_payment'] = 'Yes';
                }
                // Handle exit interview document upload
                if ($request->hasFile('exit_interview_document')) {
                    $file = $request->file('exit_interview_document');
                    $fileName = 'exit_interview_' . time() . '_' . $clearanceForm->id . '.' . $file->getClientOriginalExtension();
                    $filePath = $file->storeAs('clearance_documents', $fileName, 'public');
                    $updateData['exit_interview_document'] = $filePath;
                }
            } elseif ($currentStep === 'IT Officer') {
                // String fields - ICT Checklist
                if ($request->has('laptop_returned')) {
                    $updateData['laptop_returned'] = $request->laptop_returned;
                }
                if ($request->has('access_card_returned')) {
                    $updateData['access_card_returned'] = $request->access_card_returned;
                }
                if ($request->has('domain_account_disabled')) {
                    $updateData['domain_account_disabled'] = $request->domain_account_disabled;
                }
                if ($request->has('email_account_disabled')) {
                    $updateData['email_account_disabled'] = $request->email_account_disabled;
                }
                if ($request->has('telephone_pin_disabled')) {
                    $updateData['telephone_pin_disabled'] = $request->telephone_pin_disabled;
                }
                if ($request->has('health_ai_disabled')) {
                    $updateData['health_ai_disabled'] = $request->health_ai_disabled;
                }
                if ($request->has('openclinic_account_disabled')) {
                    $updateData['openclinic_account_disabled'] = $request->openclinic_account_disabled;
                }
                if ($request->has('sap_account_disabled')) {
                    $updateData['sap_account_disabled'] = $request->sap_account_disabled;
                }
                if ($request->has('aruti_account_disabled')) {
                    $updateData['aruti_account_disabled'] = $request->aruti_account_disabled;
                }
                if ($request->has('keyboard_mouse_returned')) {
                    $updateData['keyboard_mouse_returned'] = $request->keyboard_mouse_returned;
                }
                if ($request->has('phone_returned')) {
                    $updateData['phone_returned'] = $request->phone_returned;
                }
                if ($request->has('id_card_returned')) {
                    $updateData['id_card_returned'] = $request->id_card_returned;
                }
                if ($request->has('it_ticket_no')) {
                    $updateData['it_ticket_no'] = $request->it_ticket_no;
                }
                if ($request->has('it_comments')) {
                    $updateData['it_comments'] = $request->it_comments;
                }
            }
            
            // Update the clearance form with collected data (will be updated again in final approval if needed)
            if (!empty($updateData) && !$isFinalApproval) {
                $clearanceForm->update($updateData);
            }

            // Determine next approver based on workflow: Line Manager → Finance Officer → IT → HR (Final)
            $nextApprover = null;
            $nextStepName = '';
            $workflowStatus = '';

            // Use the actual workflow step that was just approved ($currentStep)
            // instead of role-based detection — a user may hold multiple roles
            // (e.g. finance officer AND line-manager), which would mis-route.
        if ($currentStep === 'Line Manager') {
                // Line Manager approved, next is Finance Officer
                // Find the employee's entity via their department → division relationship
                $employeeDept = $clearanceForm->user ? $clearanceForm->user->department : null;
                $employeeEntityIds = $employeeDept ? $employeeDept->divisions()->pluck('divisions.id')->toArray() : [];

                $nextApprover = null;

                // Require finance officer assigned to the employee's entity (no global fallback).
                if (!empty($employeeEntityIds)) {
                    $nextApprover = User::whereHas('roles', function ($query) {
                        $query->where('name', 'finance officer')
                            ->orWhere('name', 'like', 'finance-officer-%');
                    })
                    ->where(function ($query) use ($employeeEntityIds) {
                        $query->whereIn('assigned_entity_id', $employeeEntityIds)
                            ->orWhereHas('assignedEntities', function ($subQuery) use ($employeeEntityIds) {
                                $subQuery->whereIn('divisions.id', $employeeEntityIds);
                            });
                    })
                    ->first();
                }

                if (!$nextApprover) {
                    DB::rollBack();
                    $entityNames = !empty($employeeEntityIds)
                        ? \App\Models\Division::whereIn('id', $employeeEntityIds)->pluck('name')->filter()->implode(', ')
                        : 'N/A';
                    $errorMsg = 'No Finance Officer is assigned to this employee entity (' . $entityNames . '). Please assign a Finance Officer to that entity in Users.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMsg,
                            'error' => $errorMsg
                        ], 400);
                    }
                    Alert::error('Error', $errorMsg);
                    return redirect()->back()->withInput();
                }
                $nextStepName = 'Finance Officer';
                $workflowStatus = 'Pending Approval - Finance Officer';
        } elseif ($currentStep === 'Finance Officer') {
                // Finance Officer approved, next is IT - create workflow history for all IT users
                $nextApprovers = User::role('it')->get();
                if ($nextApprovers->isEmpty()) {
                    DB::rollBack();
                    $errorMsg = 'No IT Officer found. Please contact the administrator to assign an IT Officer.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMsg,
                            'error' => $errorMsg
                        ], 400);
                    }
                    Alert::error('Error', $errorMsg);
                    return redirect()->back()->withInput();
                }
                $nextStepName = 'IT Officer';
                $workflowStatus = 'Pending Approval - IT Officer';
        } elseif ($currentStep === 'IT Officer') {
                // IT approved, next is HR (Final) - create workflow history for all HR users
                $nextApprovers = User::role('hr')->get();
                if ($nextApprovers->isEmpty()) {
                    DB::rollBack();
                    $errorMsg = 'No HR Officer found for final approval. Please contact the administrator to assign an HR Officer.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMsg,
                            'error' => $errorMsg
                        ], 400);
                    }
                    Alert::error('Error', $errorMsg);
                    return redirect()->back()->withInput();
                }
                $nextStepName = 'HR Officer';
                $workflowStatus = 'Pending Approval - HR Officer';
            } elseif ($currentStep === 'HR Officer') {
                // Final HR approval - complete the workflow and deactivate user
                $isFinalApproval = true;
                // Allow HR to update HR review fields and items collection during final approval
                $updateData = [];
                
                // HR review fields - Convert Yes/No to boolean (1/0), skip N/A for integer columns
                if ($request->has('resignation_letter_received') && in_array($request->resignation_letter_received, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['resignation_letter_received'] = $convertToBoolean($request->resignation_letter_received);
                }
                if ($request->has('exit_interview_completed') && in_array($request->exit_interview_completed, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['exit_interview_completed'] = $convertToBoolean($request->exit_interview_completed);
                }
                if ($request->has('leave_balance_confirmed') && in_array($request->leave_balance_confirmed, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['leave_balance_confirmed'] = $convertToBoolean($request->leave_balance_confirmed);
                }
                if ($request->has('contract_file_reviewed') && in_array($request->contract_file_reviewed, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['contract_file_reviewed'] = $convertToBoolean($request->contract_file_reviewed);
                }
                if ($request->has('office_keys_returned') && in_array($request->office_keys_returned, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['office_keys_returned'] = $convertToBoolean($request->office_keys_returned);
                }
                if ($request->has('uniform_ppe_returned') && in_array($request->uniform_ppe_returned, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['uniform_ppe_returned'] = $convertToBoolean($request->uniform_ppe_returned);
                }
                if ($request->has('tools_equipment_returned') && in_array($request->tools_equipment_returned, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['tools_equipment_returned'] = $convertToBoolean($request->tools_equipment_returned);
                }
                if ($request->has('vehicle_clearance') && in_array($request->vehicle_clearance, ['Yes', 'No', 'yes', 'no', '1', '0'])) {
                    $updateData['vehicle_clearance'] = $convertToBoolean($request->vehicle_clearance);
                }
                if ($request->has('hr_comments')) {
                    $updateData['hr_comments'] = $request->hr_comments;
                }
                // Reason for leaving - only update if a valid enum value is provided
                if ($request->has('reason_for_leaving') && $request->reason_for_leaving !== null && $request->reason_for_leaving !== '') {
                    $validReasons = ['Resignation', 'Termination', 'Contract End', 'Retirement', 'Other'];
                    if (in_array($request->reason_for_leaving, $validReasons)) {
                        $updateData['reason_for_leaving'] = $request->reason_for_leaving;
                    }
                }
                if ($request->has('reason_for_leaving_other') && $request->reason_for_leaving_other !== null && $request->reason_for_leaving_other !== '') {
                    $updateData['reason_for_leaving_other'] = $request->reason_for_leaving_other;
                }
                
                // HR items collected fields - Keep as strings (not boolean fields)
                if ($request->has('ccbrt_id_card')) {
                    $updateData['ccbrt_id_card'] = $request->ccbrt_id_card;
                }
                if ($request->has('ccbrt_name_tag')) {
                    $updateData['ccbrt_name_tag'] = $request->ccbrt_name_tag;
                }
                if ($request->has('nhif_cards')) {
                    $updateData['nhif_cards'] = $request->nhif_cards;
                }
                if ($request->has('work_permit_cancelled')) {
                    $updateData['work_permit_cancelled'] = $request->work_permit_cancelled;
                }
                if ($request->has('residence_permit_cancelled')) {
                    $updateData['residence_permit_cancelled'] = $request->residence_permit_cancelled;
                }
        } else {
                DB::rollBack();
                $errorMsg = 'Invalid approval state. You may not have permission to approve this form at this stage.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                        'error' => $errorMsg
                    ], 400);
                }
                Alert::error('Error', $errorMsg);
                return redirect()->back()->with('error', $errorMsg);
            }

            if ($isFinalApproval) {
                // Final approval - save reason for leaving if provided, then complete workflow and deactivate user
                if (!empty($updateData)) {
                    $clearanceForm->update($updateData);
                }
                
                $workflow->work_flow_status = 'Approved - Completed';
            $workflow->work_flow_completed = 1;
            $workflow->save();

                // Deactivate user account - user cannot access any system
                $employeeUser = User::find($workflow->user_id);
                if ($employeeUser) {
                    $employeeUser->status = 'deactivated';
                    $employeeUser->save();
                    
                    // Also invalidate all active sessions for this user
                    UserSession::where('user_id', $employeeUser->id)->delete();
                    
                    // Invalidate all session files for this user
                    if (config('session.driver') === 'file') {
                        $sessionFiles = glob(storage_path('framework/sessions/*'));
                        foreach ($sessionFiles as $file) {
                            if (is_file($file)) {
                                $fileContent = @file_get_contents($file);
                                if ($fileContent && strpos($fileContent, 'user_id|i:' . $employeeUser->id) !== false) {
                                    @unlink($file);
                                }
                            }
                        }
                    } elseif (config('session.driver') === 'database') {
                        // Delete all sessions for this user from database
                        \DB::table('sessions')->where('user_id', $employeeUser->id)->delete();
                    }
                }

                // Send completion email to submitter
                try {
                    Mail::to($submitter->email)->queue(new \App\Mail\ClearanceFormStatusUpdate(
                        clearanceForm: $clearanceForm,
                        workflow: $workflow,
                        submitter: $submitter,
                        status: 'approved',
                        message: 'Your clearance form has been fully approved and your account has been deactivated. You can no longer access the system.',
                        approver: $user
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send completion email', ['error' => $e->getMessage()]);
                }

                DB::commit();
                $successMsg = 'Clearance form fully approved. Employee account has been deactivated and all sessions have been invalidated.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $successMsg,
                        'redirect' => route('clearance.index')
                    ], 200);
                }
                Alert::success('Approved', $successMsg);
                return redirect()->route('clearance.index')->with('success', $successMsg);
        } else {
                // For IT and HR, create workflow history for all users with that role
                // For Finance Officer, use single approver
                if (isset($nextApprovers) && $nextApprovers->isNotEmpty()) {
                    // Multiple approvers (IT or HR) - create workflow history for all
                    foreach ($nextApprovers as $approver) {
                        $existingNextHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                            ->where('attended_by', $approver->id)
                            ->where('status', 0)
                            ->first();
                        
                        if (!$existingNextHistory) {
                            Clearance_work_flow_history::create([
                                'work_flow_id' => $workflow->id,
                                'forwarded_by' => Auth::user()->id,
                                'attended_by' => $approver->id,
                                'status' => 0,
                                'remark' => "Forwarded for {$nextStepName} approval",
                                'step_name' => $nextStepName,
                                'attend_date' => Carbon::now()->format('Y-m-d'),
                                'parent_id' => $workflowHistory->id
                            ]);
                        }
                        
                        // Send email to each approver
                        try {
                            Mail::to($approver->email)->queue(new \App\Mail\ClearanceFormApprovalRequest(
                                clearanceForm: $clearanceForm,
                                workflow: $workflow,
                                submitter: $submitter,
                                approver: $approver,
                                stepName: $nextStepName
                            ));
                        } catch (\Exception $e) {
                            \Log::error('Failed to send approval email to ' . $approver->email, ['error' => $e->getMessage()]);
                        }
                    }
                } else {
                    // Single approver (Finance Officer) - use existing logic
                    $existingNextHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                        ->where('attended_by', $nextApprover->id)
                        ->where('status', 0)
                        ->first();
                    
                    if (!$existingNextHistory) {
                        Clearance_work_flow_history::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => Auth::user()->id,
                            'attended_by' => $nextApprover->id,
                            'status' => 0,
                            'remark' => "Forwarded for {$nextStepName} approval",
                            'step_name' => $nextStepName,
                            'attend_date' => Carbon::now()->format('Y-m-d'),
                            'parent_id' => $workflowHistory->id
                        ]);
                    } else {
                        $existingNextHistory->update([
                            'step_name' => $nextStepName,
                            'remark' => "Forwarded for {$nextStepName} approval",
                            'forwarded_by' => Auth::user()->id,
                            'attend_date' => Carbon::now()->format('Y-m-d'),
                        ]);
                    }
                    
                    // Send email to next approver
                    try {
                        Mail::to($nextApprover->email)->queue(new \App\Mail\ClearanceFormApprovalRequest(
                            clearanceForm: $clearanceForm,
                            workflow: $workflow,
                            submitter: $submitter,
                            approver: $nextApprover,
                            stepName: $nextStepName
                        ));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send approval email', ['error' => $e->getMessage()]);
                    }
                }

                $workflow->work_flow_status = $workflowStatus;
                $workflow->save();

                // Send status update to submitter
                try {
                    $approverRole = $user->getRoleNames()->first();
                    $approverTitle = match($approverRole) {
                        'line-manager' => 'Line Manager',
                        'finance officer' => 'Finance Officer',
                        'hr' => 'HR Officer',
                        'it' => 'IT Officer',
                        default => $user->fname . ' ' . $user->lname
                    };
                    
                    Mail::to($submitter->email)->queue(new \App\Mail\ClearanceFormStatusUpdate(
                        clearanceForm: $clearanceForm,
                        workflow: $workflow,
                        submitter: $submitter,
                        status: 'pending',
                        message: "Your clearance form has been approved by {$approverTitle} and forwarded to {$nextStepName}.",
                        approver: $user
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send status update email', ['error' => $e->getMessage()]);
                }

                DB::commit();
                $successMsg = "Clearance form approved successfully and forwarded to {$nextStepName}.";
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $successMsg,
                        'nextStep' => $nextStepName,
                        'redirect' => route('clearance.index')
                    ], 200);
                }
                Alert::success('Approved', $successMsg);
                return redirect()->route('clearance.index')->with('success', $successMsg);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error approving clearance form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'access_id' => $request->access_id
            ]);
            $errorMsg = 'An error occurred while approving the clearance form: ' . $e->getMessage();
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

    //    public function approveClearanceForm(Request $request){
    //          $workflow = Clearance_work_flow::where('requested_resource_id', $request->access_id)->first();

    //           if(!$workflow){
    //             return response()->json(['error' => "Clearance Workflow not found for access Id {$request->access_id}"], 404);
    //           }

    //           $workflowHistory = Clearance_work_flow_history::where('work_flow_id', $workflow->id)
    //           ->where('status', 0)->first();
    //              dd( $workflowHistory);
    //           if(!$workflowHistory){
    //             return response()->json(['error' => "Clearance Workflow History not found for Workflow ID {$workflow->id} and status 0 "], 404);
    //           }

    //           $user = Auth::user();
    //          // dd($user);
    //           $roles = $user->getRoleNames()->first();
    //          // dd($roles);
    //             if($roles != 'hr' && $roles != 'it'){
    //                 $workflowHistory->status = 1;
    //                 $workflowHistory->save();
    //             }

    //            $forwadUser = User::join('clearance_work_flows','clearance_work_flows.user_id',
    //            '=','users.id')->where('clearance_work_flows.requested_resource_id',$request->access_id)
    //            ->select('users.username')->first();

    //            switch ($roles){
    //              case 'line-manager':
    //                 $nextApproverRole = 'finance officer';
    //                 break;
    //                 case 'finance officer':
    //                     $workflowHistoryFO = Clearance_work_flow_history::where('work_flow_id',$workflow->id)
    //                     ->join('users', 'users.id', '=', 'clearance_work_flow_histories.attended_by' )
    //                     ->where('clearance_work_flow_histories.status', 0)
    //                     ->whereHas('user.roles', function ($query){
    //                         $query->where('name' , 'finance officer');
    //                     })->select('clearance_work_flow_histories.id')->get();
    //                    //dd($workflowHistoryFO);

    //                     DB::transaction(function () use ($workflowHistoryFO){
    //                         foreach ($workflowHistoryFO as $wfo){
    //                             $wfo->status = 1;
    //                             $result = $wfo->save();
    //                             if(!$result){
    //                                 \Log::error('Failed to save Clearance workflow history for ID ' . $wfo->id);
    //                         } else {
    //                             \Log::info('Successfully updated Clearance workflow history for ID ' . $wfo->id);
    //                         }
    //                         }
    //                     });
    //                     $nextApproverRole = 'it';
    //                     break;

    //                     case 'it':
    //                         $workflowHistoryIT = Clearance_work_flow_history::where('work_flow_id',$workflow->id)
    //                         ->join('users', 'users.id', '=', 'clearance_work_flow_histories.attended_by' )
    //                         ->where('clearance_work_flow_histories.status', 0)
    //                         ->whereHas('user.roles', function ($query){
    //                             $query->where('name' , 'it');
    //                         })->select('clearance_work_flow_histories.id')->get();
    //                        // dd($workflowHistoryIT);
    //                        DB::transaction(function () use ($workflowHistoryIT){
    //                         foreach ($workflowHistoryIT as $wit){
    //                             $wit->status = 1;
    //                             $result = $wit->save();
    //                             if(!$result){
    //                                 \Log::error('Failed to save Clearance workflow history for ID ' . $wit->id);
    //                         } else {
    //                             \Log::info('Successfully updated Clearance workflow history for ID ' . $wit->id);
    //                         }
    //                         }
    //                     });
    //                     $nextApproverRole = 'hr';
    //                     break;

    //                     case 'hr':
    //                         $workflowHistoryHR = Clearance_work_flow_history::where('work_flow_id',$workflow->id)
    //                         ->join('users', 'users.id', '=', 'clearance_work_flow_histories.attended_by' )
    //                         ->where('clearance_work_flow_histories.status', 0)
    //                         ->whereHas('user.roles', function ($query){
    //                             $query->where('name' , 'hr');
    //                         })->select('clearance_work_flow_histories.id')->get();
    //                         DB::transaction(function () use ($workflowHistoryHR, $workflow){
    //                             foreach ($workflowHistoryHR as $whr){
    //                                 $whr->status = 1;
    //                                 if(!$whr->save()){
    //                                     \Log::error('Failed to save clearance workflow history: ' . json_encode($whr->getErrors()));
    //                                 }

    //                             }

    //                             // Update workflow status
    //                             $workflow->work_flow_status = "approved";
    //                             $workflow->work_flow_completed = 1;
    //                             $workflow->save();
    //                         });

    //                         Alert::success('Approval Completed', 'The request has been fully approved.');
    //                         return redirect()->route('request.index')->with('success', 'Clearance form fully approved.');
    //                        default:
    //                         return response()->json(['error' => 'No valid role found for approval.'], 400);


    //       }
    //        $crf = new ClearanceFormController();
    //        if($nextApproverRole == 'hr' || $nextApproverRole == 'it' ){
    //         $approver = User::role($nextApproverRole)->get()->pluck('id');
    //         foreach($approver as $aprId){
    //            $input = [
    //             'work_flow_id' => $workflow->id,
    //             'forwarded_by' => Auth::user()->id,
    //             'attended_by' =>$aprId,
    //             'status' => '0',
    //             'remark' => 'Forwarded for approval',
    //             'attend_date' => Carbon::now()->format('d F Y'),
    //             'parent_id' => $workflowHistory->id
    //         ];
    //            $workflowHistory = $crf->saveClearanceWorkflowHistory($input);
    //         }
    //        }
    //        $approver = User::role($nextApproverRole)->first();
    //        if(!$approver){
    //         return response()->json(['error' => "Next approver with role {$nextApproverRole} not found."], 404);

    //        }

    //        // Send notification to the next approver
    //        $requestDetails = [
    //         'forwarded_by' => $forwadUser->username,
    //         'request' => "Exit Form",
    //         'requestDate' => Carbon::now()->format('d F Y'),
    //     ];

    //     if ($approver) {
    //         $mail = new ApprovalRequestNotification();
    //         $mail->approver = $approver;
    //         $mail->requestDetails = $requestDetails;
    //     //dd($approver);
    //         Mail::to($approver->email)->send($mail);
    //     } else {
    //         // Handle the case where approver is null
    //         throw new \Exception('Approver not found');
    //     }

    //     Alert::success('Approval Submitted', 'The request has been forwarded to the next approver.');
    //     return redirect()->route('request.index')->with('success', 'Exit form forwarded for further approval.');
    //  }



    public function ViewUserItForm(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $ictForm = IctAccessResource::join('users', 'users.id', '=', 'ict_access_resources.userId')
            ->join('workflows', 'workflows.ict_request_resource_id', '=', 'ict_access_resources.id')
            ->join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->join('privilege_levels', 'privilege_levels.id', '=', 'ict_access_resources.privilegeId')
            ->join('nhif_qualifications', 'nhif_qualifications.id', '=', 'ict_access_resources.nhifId')
            ->join('employment_types', 'employment_types.id', '=', 'users.employment_typeId')
            ->join('departments', 'departments.id', '=', 'users.deptId')
            ->join('h_m_i_s_access_levels', 'h_m_i_s_access_levels.id', '=', 'ict_access_resources.hmisId')
            ->where('ict_access_resources.id', $request->id)
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
                'h_m_i_s_access_levels.names',
                'work_flow_histories.forwarded_by'
            ]);
        dd($ictForm);
        return view('employees_details.it_form', compact('ictForm', 'user'));
    }

    public function ictReport(Request $request)
    {
        $user = Auth::user();
        
        // Check if user has permission to view approve summary
        if (!$user->can('view locum reports') && ($user->can_view_approve_summary ?? 0) != 1) {
            abort(403, 'You do not have permission to view this report.');
        }
        
        // Get filter parameters
        $statusFilter = $request->query('status', 'all'); // all, approved, pending, rejected
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $departmentFilter = $request->query('department');
        
        // Base query for all ICT Access Forms
        $query = IctAccessResource::with(['user.department', 'workflow.histories' => function($q) {
                $q->with(['attendedBy', 'forwardedBy'])->orderBy('id', 'desc');
            }])
            ->whereHas('workflow');
        
        // Apply status filter
        if ($statusFilter === 'approved') {
            $query->whereHas('workflow', function($q) {
                $q->where('work_flow_completed', 1)->where('work_flow_status', 1);
            });
        } elseif ($statusFilter === 'pending') {
            $query->whereHas('workflow', function($q) {
                $q->where('work_flow_completed', 0);
            });
        } elseif ($statusFilter === 'rejected') {
            $query->whereHas('workflow', function($q) {
                $q->where(function($wq) {
                    $wq->where('work_flow_completed', 1)->where('work_flow_status', 2)
                       ->orWhereHas('histories', function($hq) {
                           $hq->where('status', 2);
                       });
                });
            });
        }
        
        // Apply date filter
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        // Apply department filter
        if ($departmentFilter) {
            $query->whereHas('user', function($q) use ($departmentFilter) {
                $q->where('deptId', $departmentFilter);
            });
        }
        
        $ictForms = $query->orderBy('created_at', 'desc')->get();
        
        // Calculate statistics
        $totalCount = IctAccessResource::whereHas('workflow')->count();
        $approvedCount = IctAccessResource::whereHas('workflow', function($q) {
            $q->where('work_flow_completed', 1)->where('work_flow_status', 1);
        })->count();
        $pendingCount = IctAccessResource::whereHas('workflow', function($q) {
            $q->where('work_flow_completed', 0);
        })->count();
        $rejectedCount = IctAccessResource::whereHas('workflow', function($q) {
            $q->where(function($wq) {
                $wq->where('work_flow_completed', 1)->where('work_flow_status', 2)
                   ->orWhereHas('histories', function($hq) {
                       $hq->where('status', 2);
                   });
            });
        })->count();
        
        // Get top approvers (staff who approve most)
        $topApprovers = WorkFlowHistory::whereHas('workflow', function($q) {
                $q->whereNotNull('ict_request_resource_id');
            })
            ->where('status', 1) // Approved
            ->select('attended_by', DB::raw('count(*) as approval_count'))
            ->groupBy('attended_by')
            ->orderBy('approval_count', 'desc')
            ->limit(10)
            ->with('attendedBy')
            ->get();
        
        // Get departments for filter
        $departments = Departments::orderBy('dept_name')->get();
        
        // Calculate approval rate
        $approvalRate = $totalCount > 0 ? round(($approvedCount / $totalCount) * 100, 2) : 0;
        
        return view('reports.ict', compact(
            'ictForms', 
            'totalCount', 
            'approvedCount', 
            'pendingCount', 
            'rejectedCount',
            'topApprovers',
            'departments',
            'approvalRate',
            'statusFilter',
            'dateFrom',
            'dateTo',
            'departmentFilter'
        ));
    }

    public function hrReport(Request $request)
    {
        $user = Auth::user();
        
        // Check if user has permission to view approve summary
        if (!$user->can('view locum reports') && ($user->can_view_approve_summary ?? 0) != 1) {
            abort(403, 'You do not have permission to view this report.');
        }
        
        // Get all HR Forms with their workflow status
        $hrForms = Workflow::whereNotNull('hr_form')
            ->where('work_flow_completed', 1)
            ->with(['user', 'histories'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('reports.hr', compact('hrForms'));
    }

    public function bankReport(Request $request)
    {
        $user = Auth::user();
        
        // Check if user has permission to view approve summary
        if (!$user->can('view locum reports') && ($user->can_view_approve_summary ?? 0) != 1) {
            abort(403, 'You do not have permission to view this report.');
        }
        
        // Get all Bank Details Forms with their workflow status
        $bankForms = Workflow::whereNotNull('bank_details_form_id')
            ->where('work_flow_completed', 1)
            ->with(['user', 'histories'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('reports.bank', compact('bankForms'));
    }

    public function heslbReport(Request $request)
    {
        $user = Auth::user();
        
        // Check if user has permission to view approve summary
        if (!$user->can('view locum reports') && ($user->can_view_approve_summary ?? 0) != 1) {
            abort(403, 'You do not have permission to view this report.');
        }
        
        // Get all HESLB Forms with their workflow status
        $heslbForms = Workflow::whereNotNull('heslb_form_id')
            ->where('work_flow_completed', 1)
            ->with(['user', 'histories'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('reports.heslb', compact('heslbForms'));
    }
}
