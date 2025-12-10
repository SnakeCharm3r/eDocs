<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Http\Request;
use App\Models\ChangeRequest;
use App\Models\WorkFlowHistory;
use App\Models\Departments;
use App\Models\Hec;
use App\Models\TariffCategory;
use App\Models\ServiceCategory;
use App\Models\PaymentType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use Carbon\Carbon;


class ChangeRequestController extends Controller
{
    public function index()
    {
        // Only allow IT, super admin, and HEC members (CMS, CFO, COO)
        $user = Auth::user();
        if (!$user->hasAnyRole(['it', 'super-admin', 'coo', 'cfo', 'cms'])) {
            Alert::error('Access Denied', 'You do not have permission to view change request reports. Only IT, Super Admin, and HEC members can access this page.');
            return redirect()->route('dashboard');
        }

        $changeRequests = ChangeRequest::with([
            'user.department',
            'workflow.histories.attendedBy',
            'workflow.histories.approver',
            'tariffCategory',
            'serviceCategory'
        ])
        ->orderBy('created_at', 'desc')
        ->get(); // Get all records for DataTable

        return view('change_request.index', compact('changeRequests'));
    }

    public function create()
    {
        // Prevent HEC members from creating change requests
        $user = auth()->user();
        if ($user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo'])) {
            Alert::error('Access Denied', 'HEC members cannot create change requests. Please contact your line manager or department head.');
            return redirect()->route('request.index');
        }
        
        $tariffCategories = TariffCategory::where('is_active', true)->orderBy('name')->get();
        $serviceCategories = ServiceCategory::where('is_active', true)->orderBy('name')->get();
        $paymentTypes = PaymentType::where('is_active', true)->orderBy('name')->get();
        
        return view('change_request.create', compact('tariffCategories', 'serviceCategories', 'paymentTypes'));
    }




    public function store(Request $request)
    {
        // Prevent HEC members from creating change requests
        $user = auth()->user();
        if ($user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo'])) {
            Alert::error('Access Denied', 'HEC members cannot create change requests. Please contact your line manager or department head.');
            return redirect()->route('request.index');
        }

        $filePath = null;
        if ($request->hasFile('supporting_document')) {
            $file = $request->file('supporting_document');
            $filePath = $file->store('supporting_documents', 'public');
        }

        // Prepare data for change request
        $changeType = $request->change_type ?? 'non_price';
        
        // Determine change_category based on price_change_type if it's a price change
        $changeCategory = $changeType;
        if ($changeType === 'price' && $request->price_change_type) {
            if ($request->price_change_type === 'tariff' && $request->tariff_category_id) {
                $category = TariffCategory::find($request->tariff_category_id);
                $changeCategory = $category ? $category->name : 'Tariff';
            } elseif ($request->price_change_type === 'service' && $request->service_category_id) {
                $category = ServiceCategory::find($request->service_category_id);
                $changeCategory = $category ? $category->name : 'Service';
            } elseif ($request->price_change_type === 'price_change') {
                $changeCategory = 'Price Change';
            }
        }
        
        $changeRequestData = [
            'userId' => $user->id,
            'change_type' => $changeType,
            'change_category' => $changeCategory,
            'description_of_change' => $request->description_of_change,
            'reason_for_change' => $request->reason_for_change,
            'priority' => $request->priority,
            'supporting_document' => $filePath,
            'implementation_notes' => $request->implementation_notes ?? null,
        ];

        // Handle price change specific fields
        if (($request->change_type ?? 'non_price') === 'price') {
            $priceChangeType = $request->price_change_type;
            
            if ($priceChangeType === 'tariff') {
                // Tariff fields
                $changeRequestData['tariff_type'] = $request->tariff_type;
                $changeRequestData['tariff_category_id'] = $request->tariff_category_id;
                
                if ($request->tariff_type === 'new_tariff') {
                    $changeRequestData['tariff_name'] = $request->tariff_name;
                } else if ($request->tariff_type === 'edit_tariff') {
                    $changeRequestData['current_tariff_name'] = $request->current_tariff_name;
                    $changeRequestData['tariff_name'] = $request->tariff_name; // New name
                }
            } else if ($priceChangeType === 'service') {
                // Service fields
                $changeRequestData['service_action_type'] = $request->service_action_type;
                $changeRequestData['service_category_id'] = $request->service_category_id;
                
                if ($request->service_action_type === 'new_service') {
                    $changeRequestData['service_name'] = $request->service_name;
                    // Parse service prices JSON
                    $servicePrices = json_decode($request->service_prices_json, true) ?: [];
                    $changeRequestData['service_prices'] = json_encode($servicePrices);
                } else if ($request->service_action_type === 'edit_service') {
                    $changeRequestData['current_service_name'] = $request->current_service_name;
                    // For edit_service, store as JSON with a single 'price' key
                    if ($request->current_price) {
                        $changeRequestData['current_price'] = ['price' => $request->current_price];
                    }
                    if ($request->new_price) {
                        $changeRequestData['new_price'] = ['price' => $request->new_price];
                    }
                }
            } else if ($priceChangeType === 'price_change') {
                // Price change fields
                $changeRequestData['price_item_name'] = $request->price_item_name;
                $changeRequestData['price_change_reason'] = $request->price_change_reason;
                
                // Store current and new prices as arrays (model will handle JSON encoding via casts)
                $currentPrices = $request->price_current ?? [];
                $newPrices = $request->price_new ?? [];
                // Filter out empty values
                $changeRequestData['current_price'] = array_filter($currentPrices, function($value) {
                    return $value !== null && $value !== '';
                });
                $changeRequestData['new_price'] = array_filter($newPrices, function($value) {
                    return $value !== null && $value !== '';
                });
            }
            
            // Set legacy fields to null for price changes
            $changeRequestData['service_type'] = null;
            $changeRequestData['insurer_tariff_name'] = null;
            $changeRequestData['service_description'] = null;
        } else {
            // For non-price changes, set all price-related fields to null
            $changeRequestData['service_type'] = null;
            $changeRequestData['insurer_tariff_name'] = null;
            $changeRequestData['service_description'] = null;
            $changeRequestData['tariff_type'] = null;
            $changeRequestData['tariff_name'] = null;
            $changeRequestData['tariff_category_id'] = null;
            $changeRequestData['current_tariff_name'] = null;
            $changeRequestData['service_action_type'] = null;
            $changeRequestData['service_name'] = null;
            $changeRequestData['service_category_id'] = null;
            $changeRequestData['current_service_name'] = null;
            $changeRequestData['service_prices'] = null;
            $changeRequestData['price_item_name'] = null;
            $changeRequestData['current_price'] = null;
            $changeRequestData['new_price'] = null;
            $changeRequestData['price_change_reason'] = null;
        }

        // Start database transaction for rollback if approver not found
        DB::beginTransaction();
        
        try {
            // Create the main change request record
            $changeRequest = ChangeRequest::create($changeRequestData);

            // Create a new workflow linked to this change request
            $workflow = new Workflow();
            $workflow->user_id = $user->id;
            $workflow->change_request_id = $changeRequest->id;
            $workflow->work_flow_completed = 0;
            $workflow->work_flow_status = 'Pending Approval - Line Manager'; // Set default status
            $workflow->save();

            // Check if requester is a line manager - if so, skip line manager approval
            $isLineManager = $user->hasRole('line-manager') && !$user->hasRole('hr');
            
            if ($isLineManager) {
                // Line manager submitting - skip to next step based on change type
                if ($changeType === 'price') {
                    // Price: Skip to Price Committee
                    $priceCommitteeMembers = User::role('price_committee')->get();
                    if ($priceCommitteeMembers->isEmpty()) {
                        DB::rollBack();
                        Alert::error('Error', 'No Price Committee members found. Please contact the administrator to set up Price Committee members before submitting change requests.');
                        return redirect()->back()->withInput();
                    } else {
                    $workflow->work_flow_status = 'Pending at Price Committee';
                    $workflow->save();
                    
                    foreach ($priceCommitteeMembers as $member) {
                        WorkFlowHistory::create([
                            'work_flow_id' => $workflow->id,
                            'forwarded_by' => $user->id,
                            'attended_by' => $member->id,
                            'who_approve' => null,
                            'status' => '0',
                            'remark' => 'Awaiting Price Committee approval (Line Manager request)',
                            'step_name' => 'Price Committee',
                            'action_taken' => null,
                            'attend_date' => now()->format('Y-m-d'),
                            'rejection_reason' => null,
                        ]);
                    }
                    
                    // Send email to first Price Committee member
                    try {
                        Mail::to($priceCommitteeMembers->first()->email)->queue(new \App\Mail\ChangeRequestApprovalRequest(
                            changeRequest: $changeRequest,
                            workflow: $workflow,
                            submitter: $user,
                            approver: $priceCommitteeMembers->first()
                        ));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send email to Price Committee', ['error' => $e->getMessage()]);
                    }
                }
            } else {
                // Non-Price: Skip to HEC Member
                $department = Departments::find($user->deptId);
                
                if ($department && $department->hec_id) {
                    $hec = Hec::find($department->hec_id);
                    if ($hec) {
                        $hecLevelName = strtoupper(trim($hec->hec_level_name));
                        $roleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CRHDO' => 'crhdo'];
                        $roleSlug = $roleMap[$hecLevelName] ?? 'cms';
                        
                        $hecMember = User::role($roleSlug)->first();
                        if ($hecMember) {
                            $workflow->work_flow_status = 'Pending at HEC Member';
                            $workflow->save();
                            
                            WorkFlowHistory::create([
                                'work_flow_id' => $workflow->id,
                                'forwarded_by' => $user->id,
                                'attended_by' => $hecMember->id,
                                'who_approve' => null,
                                'status' => '0',
                                'remark' => 'Awaiting HEC Member approval (Line Manager request)',
                                'step_name' => 'HEC Member',
                                'action_taken' => null,
                                'attend_date' => now()->format('Y-m-d'),
                                'rejection_reason' => null,
                            ]);
                            
                            // Send email to HEC Member
                            try {
                                Mail::to($hecMember->email)->queue(new \App\Mail\ChangeRequestApprovalRequest(
                                    changeRequest: $changeRequest,
                                    workflow: $workflow,
                                    submitter: $user,
                                    approver: $hecMember
                                ));
                            } catch (\Exception $e) {
                                \Log::error('Failed to send email to HEC Member', ['error' => $e->getMessage()]);
                            }
                        } else {
                            DB::rollBack();
                            Alert::error('Error', 'No HEC member found for your department. Please contact the administrator to set up HEC members before submitting change requests.');
                            return redirect()->back()->withInput();
                        }
                    } else {
                        DB::rollBack();
                        Alert::error('Error', 'No HEC mapping found for your department. Please contact the administrator to configure HEC mapping before submitting change requests.');
                        return redirect()->back()->withInput();
                    }
                } else {
                    DB::rollBack();
                    Alert::error('Error', 'No department or HEC mapping found. Please contact the administrator to configure your department settings before submitting change requests.');
                    return redirect()->back()->withInput();
                }
            }
        } else {
            // Regular requester - route to Line Manager first
            $workflow->work_flow_status = 'Pending Approval - Line Manager';
            $workflow->save();
            
            // Get Line Manager(s) for the user's department
            $lineManagers = User::role('line-manager')->where('deptId', $user->deptId)->get();

            if ($lineManagers->isEmpty()) {
                DB::rollBack();
                Alert::error('Error', 'No line manager found for your department. Please contact the administrator to assign a line manager to your department before submitting change requests.');
                return redirect()->back()->withInput();
            } else {
                // Create workflow history entry for each Line Manager
                foreach ($lineManagers as $lineManager) {
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => $user->id,
                        'attended_by' => $lineManager->id,
                        'who_approve' => null,
                        'status' => '0',
                        'remark' => 'Awaiting Line Manager approval',
                        'step_name' => 'Line Manager',
                        'action_taken' => null,
                        'attend_date' => now()->format('Y-m-d'),
                        'rejection_reason' => null,
                    ]);
                }
                
                // Send email to first Line Manager
                try {
                    Mail::to($lineManagers->first()->email)->queue(new \App\Mail\ChangeRequestApprovalRequest(
                        changeRequest: $changeRequest,
                        workflow: $workflow,
                        submitter: $user,
                        approver: $lineManagers->first()
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send email to Line Manager', ['error' => $e->getMessage()]);
                }
            }
        }
        
        // Commit transaction if all approvers are found
        DB::commit();
        
        Alert::success('submitted.', 'Change request submitted successfully');
        return redirect()->route('request.index')->with('success', 'Change request submitted successfully and sent for approval.');
        
        } catch (\Exception $e) {
            // Rollback on any exception
            DB::rollBack();
            \Log::error('Error submitting change request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Alert::error('Error', 'An error occurred while submitting the change request. Please try again or contact support.');
            return redirect()->back()->withInput();
        }
    }

    public function approve(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $changeRequest = ChangeRequest::with('workflow', 'user')->findOrFail($id);
            $workflow = $changeRequest->workflow;
            $changeType = $changeRequest->change_type ?? 'non_price';
            $actor = Auth::user();

            // Check if user has permission to approve (either has approve forms permission or is price_committee)
            if (!$actor->hasPermissionTo('approve forms') && !$actor->hasRole('price_committee')) {
                DB::rollBack();
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'icon' => 'error',
                        'title' => 'Access Denied',
                        'text' => 'You do not have permission to approve change requests.'
                    ], 403);
                }
                Alert::error('Access Denied', 'You do not have permission to approve change requests.');
                return redirect()->back();
            }

            // Find the current pending workflow history for this user
            $currentStep = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', $actor->id)
                ->where('status', 0)
                ->latest()
                ->first();

            if (!$currentStep) {
                DB::rollBack();
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'icon' => 'error',
                        'title' => 'Error',
                        'text' => 'No pending approval found for this request.'
                    ], 400);
                }
                Alert::error('Error', 'No pending approval found for this request.');
                return redirect()->back();
            }

            // Check approval count BEFORE updating (for Price Committee logic)
            $priceCommitteeApprovalCount = 0;
            if ($currentStep->step_name === 'Price Committee' && $changeType === 'price') {
                $priceCommitteeApprovalCount = WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->where('step_name', 'Price Committee')
                    ->where('status', 1)
                    ->count();
            }

            // Update current step as approved
            $currentStep->update([
                'status' => 1,
                'action_taken' => 'Approved',
                'who_approve' => $actor->id,
                'attend_date' => now()->format('Y-m-d'),
                'comments' => $request->comments ?? null,
            ]);

            // For Price Committee and IT: Mark all other pending entries for this step as approved
            // This ensures all team members see it as approved when one member approves
            if (in_array($currentStep->step_name, ['Price Committee', 'IT'])) {
                WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->where('step_name', $currentStep->step_name)
                    ->where('status', 0)
                    ->where('id', '!=', $currentStep->id)
                    ->update([
                        'status' => 1,
                        'action_taken' => 'Approved',
                        'who_approve' => $actor->id,
                        'attend_date' => now()->format('Y-m-d'),
                        'comments' => 'Approved by ' . $actor->fname . ' ' . $actor->lname . ' (Team approval)',
                    ]);
            }

            // Determine next step based on change type and current step
            $nextStep = null;
            $nextStatus = null;
            $nextUsers = collect();

            if ($changeType === 'price') {
                // Price workflow: Line Manager → Price Committee → CMS (HEC) → IT
                switch ($currentStep->step_name) {
                    case 'Line Manager':
                        // Forward to all Price Committee members
                        $nextUsers = User::role('price_committee')->get();
                        $nextStep = 'Price Committee';
                        $nextStatus = 'Pending at Price Committee';
                        break;

                    case 'Price Committee':
                        // Check if this is first approval (before we marked all as approved)
                        // If count was 0, this is the first approval, forward to CMS
                        if ($priceCommitteeApprovalCount == 0) {
                            // First approval - forward to CMS (HEC member)
                            $department = Departments::find($changeRequest->user->deptId);
                            
                            if ($department && $department->hec_id) {
                                $hec = Hec::find($department->hec_id);
                                if ($hec) {
                                    $hecLevelName = strtoupper(trim($hec->hec_level_name));
                                    // For price changes, always use CMS
                                    $cmsMember = User::role('cms')->first();
                                    if ($cmsMember) {
                                        $nextUsers = collect([$cmsMember]);
                                        $nextStep = 'HEC Member';
                                        $nextStatus = 'Pending at HEC Member (CMS)';
                                    }
                                }
                            }
                        }
                        // Note: After CMS approval, it goes to IT (handled in HEC Member case)
                        break;

                    case 'HEC Member':
                        // After CMS approval, forward to IT for implementation
                        $itUsers = User::role('it')->get();
                        if ($itUsers->isNotEmpty()) {
                            $nextUsers = $itUsers;
                            $nextStep = 'IT';
                            $nextStatus = 'Pending at IT';
                        }
                        break;

                    case 'IT':
                        // Final approval - mark as completed
                        $workflow->update([
                            'work_flow_status' => 'Fully Approved',
                            'work_flow_completed' => 1,
                        ]);
                        
                        DB::commit();
                        
                        // Send email after commit to ensure approval succeeds even if emails fail
                        try {
                            Mail::to($changeRequest->user->email)->queue(new \App\Mail\ChangeRequestStatusUpdate(
                                changeRequest: $changeRequest,
                                status: 'approved',
                                approver: $actor,
                                reason: null,
                                approvalStep: 'IT'
                            ));
                        } catch (\Exception $e) {
                            \Log::error('Failed to send final approval email', ['error' => $e->getMessage()]);
                            // Continue even if email fails
                        }
                        
                        if ($request->expectsJson() || $request->wantsJson()) {
                            return response()->json([
                                'icon' => 'success',
                                'title' => 'Success',
                                'text' => 'Change request fully approved and ready for implementation.'
                            ]);
                        }
                        Alert::success('Approved', 'Change request fully approved and ready for implementation.');
                        return redirect()->route('requestapprove.index');
                }
            } else {
                // Non-price workflow: Line Manager → HEC Member → IT
                switch ($currentStep->step_name) {
                    case 'Line Manager':
                        // Forward to HEC member of the department
                        $department = Departments::find($changeRequest->user->deptId);
                        
                        if ($department && $department->hec_id) {
                            $hec = Hec::find($department->hec_id);
                            if ($hec) {
                                $hecLevelName = strtoupper(trim($hec->hec_level_name));
                                $roleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CRHDO' => 'crhdo'];
                                $roleSlug = $roleMap[$hecLevelName] ?? 'cms';
                                
                                $hecMember = User::role($roleSlug)->first();
                                if ($hecMember) {
                                    $nextUsers = collect([$hecMember]);
                                    $nextStep = 'HEC Member';
                                    $nextStatus = 'Pending at HEC Member';
                                }
                            }
                        }
                        break;

                    case 'HEC Member':
                        // Forward to IT for implementation
                        $itUsers = User::role('it')->get();
                        if ($itUsers->isNotEmpty()) {
                            $nextUsers = $itUsers;
                            $nextStep = 'IT';
                            $nextStatus = 'Pending at IT';
                        }
                        break;

                    case 'IT':
                        // Final approval - mark as completed
                        $workflow->update([
                            'work_flow_status' => 'Fully Approved',
                            'work_flow_completed' => 1,
                        ]);
                        
                        DB::commit();
                        
                        // Send email after commit to ensure approval succeeds even if emails fail
                        try {
                            Mail::to($changeRequest->user->email)->queue(new \App\Mail\ChangeRequestStatusUpdate(
                                changeRequest: $changeRequest,
                                status: 'approved',
                                approver: $actor,
                                reason: null,
                                approvalStep: 'IT'
                            ));
                        } catch (\Exception $e) {
                            \Log::error('Failed to send final approval email', ['error' => $e->getMessage()]);
                            // Continue even if email fails
                        }
                        
                        if ($request->expectsJson() || $request->wantsJson()) {
                            return response()->json([
                                'icon' => 'success',
                                'title' => 'Success',
                                'text' => 'Change request fully approved and ready for implementation.'
                            ]);
                        }
                        Alert::success('Approved', 'Change request fully approved and ready for implementation.');
                        return redirect()->route('requestapprove.index');
                }
            }

            // Create workflow history for next step
            if ($nextStep && $nextUsers->isNotEmpty()) {
                foreach ($nextUsers as $user) {
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'step_name' => $nextStep,
                        'forwarded_by' => $actor->id,
                        'attended_by' => $user->id,
                        'status' => 0,
                        'remark' => 'Awaiting approval from ' . $nextStep,
                        'attend_date' => now()->format('Y-m-d'),
                    ]);
                }

                $workflow->update([
                    'work_flow_status' => $nextStatus,
                ]);

            DB::commit();
            
            // Send emails after commit to ensure approval succeeds even if emails fail
            // Send email to next approvers
            foreach ($nextUsers as $nextApprover) {
                try {
                    Mail::to($nextApprover->email)->queue(new \App\Mail\ChangeRequestApprovalRequest(
                        changeRequest: $changeRequest,
                        workflow: $workflow,
                        submitter: $changeRequest->user,
                        approver: $nextApprover
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send email to next approver', [
                        'error' => $e->getMessage(),
                        'approver_id' => $nextApprover->id
                    ]);
                    // Continue even if email fails
                }
            }

            // Notify requester of approval at this step
            try {
                Mail::to($changeRequest->user->email)->queue(new \App\Mail\ChangeRequestStatusUpdate(
                    changeRequest: $changeRequest,
                    status: 'approved',
                    approver: $actor,
                    reason: null,
                    approvalStep: $currentStep->step_name
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to send approval notification email', ['error' => $e->getMessage()]);
                // Continue even if email fails
            }
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'icon' => 'success',
                    'title' => 'Success',
                    'text' => 'Change request approved and forwarded to ' . $nextStep . '.'
                ]);
            }
            Alert::success('Approved', 'Change request approved and forwarded to ' . $nextStep . '.');
        } else {
            DB::rollBack();
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'Unable to forward request: No users found for next step.'
                ], 400);
            }
            Alert::error('Error', 'Unable to forward request: No users found for next step.');
        }

        return redirect()->route('requestapprove.index');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error approving change request', [
                'error' => $e->getMessage(),
                'change_request_id' => $id
            ]);
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'An error occurred while approving the request: ' . $e->getMessage()
                ], 500);
            }
            Alert::error('Error', 'An error occurred while approving the request.');
            return redirect()->back();
        }
    }

    public function rejectChangeRequest(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'rejection_reason' => 'required|string',
            ]);
            
            // Handle validation errors for JSON requests
            if ($request->expectsJson() || $request->wantsJson()) {
                // Validation will automatically return JSON if validation fails
            }

            $changeRequest = ChangeRequest::with('workflow', 'user')->findOrFail($id);
            $workflow = $changeRequest->workflow;
            $actor = Auth::user();

            // Check if user has permission to reject (either has reject forms permission or is price_committee)
            if (!$actor->hasPermissionTo('reject forms') && !$actor->hasRole('price_committee')) {
                DB::rollBack();
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'icon' => 'error',
                        'title' => 'Access Denied',
                        'text' => 'You do not have permission to reject change requests.'
                    ], 403);
                }
                Alert::error('Access Denied', 'You do not have permission to reject change requests.');
                return redirect()->back();
            }

            // Find the current pending workflow history for this user
            $workflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', $actor->id)
                ->where('status', 0)
                ->first();

            if (!$workflowHistory) {
                DB::rollBack();
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'icon' => 'error',
                        'title' => 'Error',
                        'text' => 'No pending approval found for this request.'
                    ], 400);
                }
                Alert::error('Error', 'No pending approval found for this request.');
                return redirect()->back();
            }

            // Update workflow history with rejection
            $workflowHistory->status = 2; // Rejected
            $workflowHistory->rejection_reason = $request->rejection_reason;
            $workflowHistory->comments = null; // No comments field for rejection
            $workflowHistory->who_approve = $actor->id;
            $workflowHistory->attend_date = now()->format('Y-m-d');
            $workflowHistory->remark = 'Rejected by ' . $workflowHistory->step_name;
            $workflowHistory->save();

            // Update workflow status
            $workflow->work_flow_status = 'Rejected';
            $workflow->save();

            // Send rejection email to requester
            try {
                Mail::to($changeRequest->user->email)->queue(new \App\Mail\ChangeRequestStatusUpdate(
                    changeRequest: $changeRequest,
                    status: 'rejected',
                    approver: $actor,
                    reason: $request->rejection_reason,
                    approvalStep: $workflowHistory->step_name
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to send rejection email', ['error' => $e->getMessage()]);
            }

            DB::commit();
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'icon' => 'success',
                    'title' => 'Rejected',
                    'text' => 'Change request has been rejected.'
                ]);
            }
            Alert::error('Rejected', 'Change request has been rejected.');
            return redirect()->route('requestapprove.index')->with('error', 'Change request has been rejected.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error rejecting change request', [
                'error' => $e->getMessage(),
                'change_request_id' => $id
            ]);
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'An error occurred while rejecting the request: ' . $e->getMessage()
                ], 500);
            }
            Alert::error('Error', 'An error occurred while rejecting the request.');
            return redirect()->back();
        }
    }
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'description_of_change' => 'required|string',
    //         'service_type' => 'required|string',
    //         'insurer_tariff_name' => 'nullable|string',
    //         'reason_for_change' => 'required|string',
    //         'service_description' => 'required|string',
    //         'priority' => 'required|string',
    //         'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
    //     ]);

    //     $filePath = null;
    //     if ($request->hasFile('supporting_document')) {
    //         $file = $request->file('supporting_document');
    //         $filePath = $file->store('supporting_documents', 'public');
    //     }

    //     $data = $request->all();
    //     $data['userId'] = auth()->id();
    //     $data['supporting_document'] = $filePath;

    //     // Create change request
    //     $changeRequest = ChangeRequest::create($data);

    //     $user = auth()->user();

    //     // Create a new workflow linked to this change request
    //     $workflow = new Workflow();
    //     $workflow->user_id = $user->id;
    //     $workflow->change_request_id = $changeRequest->id;
    //     $workflow->work_flow_status = 'Pending Approval - Line Manager';
    //     $workflow->work_flow_completed = 0;
    //     $workflow->save();

    //     // Get Line Manager(s)
    //     $lineManagers = User::role('line-manager')->where('deptId',  $user->deptId)->get();
    //     // dd($lineManagers,  $user);

    //     // Create workflow history entry for each Line Manager
    //     foreach ($lineManagers as $lineManager) {
    //         WorkFlowHistory::create([
    //             'work_flow_id' => $workflow->id,
    //             'forwarded_by' => $user->id,
    //             'attended_by' => $lineManager->id,
    //             'who_approve' => null,
    //             'status' => '0',
    //             'remark' => 'Awaiting Line Manager approval',
    //             'step_name' => 'Line Manager',
    //             'action_taken' => null,
    //             'attend_date' => now()->format('Y-m-d'),
    //             'rejection_reason' => null,
    //         ]);
    //     }
    //     Alert::success('submitted.', 'Change request submitted successfully');
    //     return redirect()->route('request.index')->with('success', 'Change request submitted successfully and sent for approval.');
    // }



}
