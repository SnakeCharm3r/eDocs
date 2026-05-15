<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\IDCards;
use App\Models\Workflow;
use App\Models\BankDetail;
use Illuminate\Http\Request;
use App\Models\ChangeRequest;
use App\Models\LoanDeclaration;
use App\Models\WorkFlowHistory;
use App\Models\Departments;
use App\Models\Hec;
use App\Models\NhifRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Notifications\HrFormApproved;
use App\Notifications\HrFormRejected;
use Illuminate\Support\Facades\Storage;
use App\Mail\ApprovalRequestNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Request as FacadesRequest;

class SignatureController extends Controller
{
    public function index(Request $request)
    {
        session(['current_step' => 7]);
        $user = auth()->user();

        $users = User::all();

        return view('signature.index', compact('user', 'users'));
    }

    public function showUsersWithSignatures()
    {
        $users = User::all();

        // Return the view with the list of users and their signatures
        return view('signature.users', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        $signature = $request->input('signature');

        if (empty($signature) || !str_contains($signature, 'data:image')) {
            return redirect()->back()->with('error', 'Invalid signature data. Please try again.');
        }

        try {
            // Decode the base64 data
            $signatureData = explode(',', $signature)[1];

            // Get the authenticated user
            $user = Auth::user();

            if (!$user) {
                return redirect()->route('login')->with('error', 'Please log in first.');
            }

            // Save the signature to the user's signature column
            $user->signature = $signatureData;

            // Use the date the user signed as their joining date (starting_date) if not already set
            if (empty($user->starting_date)) {
                $user->starting_date = now()->format('Y-m-d');
            }

            $user->save();

            return redirect()->route('profile.confirm')->with('success', 'Signature saved successfully!');
        } catch (\Exception $e) {
            Log::error('Error saving signature: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while saving your signature. Please try again.');
        }
    }

    // public function submitRequest()
    // {
    //     $user = Auth::user();

    //     if ($user->status === 'inactive') {
    //         $user->status = 'pending';
    //         $user->save();
    //     }

    //     // Create a new workflow entry
    //     $workflow = new Workflow;
    //     $workflow->user_id = $user->id;
    //     $workflow->work_flow_status = 'Pending for approval';
    //     $workflow->work_flow_completed = 0;
    //     $workflow->hr_form = $user->id;
    //     $workflow->save();

    //     // Fetch all HR users
    //     $hr_to_approve = User::role('hr')->get();
    //     //   dd($hr_to_approve);
    //     // Save workflow history for each HR user
    //     foreach ($hr_to_approve as $hr) {
    //         $this->saveWorkflowHistory([
    //             'work_flow_id' => $workflow->id,
    //             'forwarded_by' => $user->id,
    //             'attended_by' => $hr->id,
    //             'status' => '0',
    //             'remark' => 'HR form',
    //             'attend_date' => Carbon::now()->format('d F Y'),
    //             'parent_id' => null,
    //         ]);

    //         // Prepare request details
    //         $requestDetails = [
    //             'forwarded_by' => $user->fname,
    //             'request' => "HR Form",
    //             'requestDate' => Carbon::now()->format('d F Y'),
    //         ];

    //         // Send email to each HR user (for pending request)
    //         foreach ($hr_to_approve as $hr) {
    //             $mail = new ApprovalRequestNotification($hr, $requestDetails);
    //             $mail->approver = $hr;
    //             $mail->requestDetails = $requestDetails;

    //             Mail::to($hr->email)->send($mail);
    //         }
    //     }

    //     return redirect()->route('review-dashboard')->with('success', 'Signature saved successfully!');
    // }
    public function submitRequest()
    {
        $user = Auth::user();

        // Update user status if inactive
        if ($user->status === 'inactive') {
            $user->status = 'pending';
            $user->save();
        }

        // Check if user already has a pending HR form
        $existingWorkflow = Workflow::where('hr_form', $user->id)
            ->where('work_flow_completed', 0)
            ->first();

        if ($existingWorkflow) {
            return redirect()->route('profile.confirm')->with('error', 'You already have a pending HR form submission. Please wait for approval.');
        }

        // Fetch all HR users
        $hr_to_approve = User::role('hr')->get();

        if ($hr_to_approve->isEmpty()) {
            return redirect()->route('profile.confirm')->with('error', 'No HR approvers found. Please contact system administrator.');
        }

        try {
            DB::beginTransaction();

            // Create new workflow entry
            $workflow = new Workflow;
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
                    'forwarded_by' => $user->id,
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
                'forwarded_by' => $user->fname . ' ' . $user->lname,
                'request' => "HR Form",
                'requestDate' => $nowFormatted,
            ];

            // Send queued emails to each HR user (after transaction commit)
            foreach ($hr_to_approve as $hr) {
                Mail::to($hr->email)->queue(new ApprovalRequestNotification($hr, $requestDetails));
            }

            return redirect()->route('review-dashboard')->with('success', 'HR form submitted successfully! All HR members have been notified.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting HR form: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e->getTraceAsString()
            ]);
            return redirect()->route('profile.confirm')->with('error', 'Failed to submit HR form. Please try again or contact support.');
        }
    }

    public function saveWorkflowHistory($input)
    {
        return WorkFlowHistory::create($input);
    }
    public function edit($id)
    {
        $signature = User::findOrFail($id); // Fetch the signature by ID
        return view('signatures.edit', compact('signature')); // Pass the signature to the view
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'signature_data' => 'required|string', // Adjust validation rules as needed
        ]);

        $signature = User::findOrFail($id);

        $signature->update([
            'signature_data' => $validatedData['signature_data'],
        ]);

        return response()->json(['message' => 'Signature updated successfully']);
    }


    public function destroy(User $user)
    {
        // Logic to delete the user's signature
        // Assuming you have a relationship or method to handle this
        $user->signature()->delete();

        return redirect()->route('some.route')->with('status', 'Signature deleted!');
    }


    public function showHrForm($id)
    {
        $user = User::with(['jobTitle', 'department'])->where('users.id', $id)->first();

        if (!$user) {
            return redirect()->route('requestapprove.index')->with('error', 'User not found.');
        }

        $familyDetails = DB::table('user_family_details')->where('userId', $id)->get();
        $healthDetails = DB::table('health_details')->where('userId', $id)->first();
        $languageKnowledge = DB::table('language_knowledge')->where('userId', $id)->get();

        $relations = DB::table('ccbrt_relations')
            ->join('departments', 'departments.id', 'ccbrt_relations.department')
            ->where('userId', $id)->get();

        // Check if user's job title is clinical
        $isClinicalDepartment = $user->jobTitle && $user->jobTitle->clinical_or_non_clinical === 'Clinical';

        // Get workflow and current HR's workflow history for verification status
        $workflow = Workflow::where('hr_form', $id)->orderBy('id', 'desc')->first();
        $currentHrHistory = null;
        $isApproved = false;
        $isRejected = false;
        $approver = null;
        $approvalDate = null;

        if ($workflow) {
            // Check if workflow is completed (approved)
            $isApproved = $workflow->work_flow_completed == 1;

            // Check if current HR has rejected it
            $rejectedHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', Auth::id())
                ->where('status', 2) // Rejected
                ->first();
            $isRejected = $rejectedHistory !== null;

            // Get current HR's workflow history (pending or approved)
            $currentHrHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', Auth::id())
                ->first();

            // Get approver information if approved
            if ($isApproved) {
                $approvedHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->where('status', 1) // Approved
                    ->whereNotNull('who_approve')
                    ->with(['approver.jobTitle', 'approver.roles', 'attendedBy.jobTitle', 'attendedBy.roles'])
                    ->orderBy('updated_at', 'desc')
                    ->first();

                if ($approvedHistory) {
                    // Prefer who_approve (HR who clicked approve); fall back to attended_by
                    // Also skip if who_approve is the same person as the form owner (self-approval anomaly)
                    $hrActor = $approvedHistory->approver;
                    if (!$hrActor || $hrActor->id == $id) {
                        $hrActor = $approvedHistory->attendedBy;
                    }
                    if ($hrActor && $hrActor->id != $id) {
                        $approver = $hrActor;
                    } elseif ($approvedHistory->approver) {
                        $approver = $approvedHistory->approver; // keep even if same as user
                    }
                    $approvalDate = $approvedHistory->updated_at;
                }
            }
        }

        return view('profile.hr_confirm', compact(
            'user',
            'familyDetails',
            'healthDetails',
            'languageKnowledge',
            'relations',
            'isClinicalDepartment',
            'currentHrHistory',
            'workflow',
            'isApproved',
            'isRejected',
            'approver',
            'approvalDate'
        ));
    }
    // public function approveHrForm($id)
    // {
    //     // Find the HR user who is approving the request
    //     $approvingUser = User::find($id);

    //     // Ensure the user exists before continuing
    //     if (!$approvingUser) {
    //         return redirect()->route('requestapprove.index')->with('error', 'User not found.');
    //     }

    //     // Update the user's status to 'active'
    //     $approvingUser->status = 'active';
    //     if ($approvingUser->save()) {
    //         // Find the latest workflow associated with this request
    //         $workflow = Workflow::where('hr_form', $id)->orderBy('id', 'desc')->first();

    //         if (!$workflow) {
    //             return redirect()->route('requestapprove.index')->with('error', 'Workflow not found for the HR form.');
    //         }

    //         // Update the workflow status to 'HR Form Approved' and mark it as completed
    //         $workflow->work_flow_status = "HR Form Approved";
    //         $workflow->work_flow_completed = 1;
    //         $workflow->save();

    //         // Update the workflow history status for all HR users involved
    //         $workflowHistories = WorkflowHistory::where('work_flow_id', $workflow->id)
    //             ->where('status', '0') // Pending status (0)
    //             ->get();

    //         // Ensure all pending HR statuses are updated to approved (1)
    //         foreach ($workflowHistories as $workflowHistory) {
    //             $workflowHistory->status = 1;
    //             $workflowHistory->who_approve = $approvingUser->id;
    //             $workflowHistory->save();
    //         }

    //         // Send notification to the user who made the request
    //         if ($workflow->user) {
    //             Notification::route('mail', $workflow->user->email)
    //                 ->notify(new HrFormApproved($workflow->user, $workflow));
    //         } else {
    //             \Log::error('No user found for workflow ID: ' . $workflow->id);
    //         }

    //         // Return success message via session for toast notification
    //         return redirect()->route('requestapprove.index')->with('success', 'User status updated to active successfully.');
    //     } else {
    //         // Return error message via session for toast notification
    //         return redirect()->route('requestapprove.index')->with('error', 'User status not updated to active successfully.');
    //     }
    // }

    public function approveHrForm(Request $request, $id)
    {
        $actor = Auth::user();

        // Check if user has permission to approve
        if (!$actor->hasRole('hr') && !$actor->hasPermissionTo('approve forms')) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to approve HR forms.'
                ], 403);
            }
            return redirect()->route('requestapprove.index')->with('error', 'You do not have permission to approve HR forms.');
        }

        // Find the workflow - $id is the user_id (hr_form field stores user_id)
        $workflow = Workflow::with('user')->where('hr_form', $id)->orderBy('id', 'desc')->first();

        if (!$workflow) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workflow not found for the HR form.'
                ], 404);
            }
            return redirect()->route('requestapprove.index')->with('error', 'Workflow not found for the HR form.');
        }

        // Get the user whose form is being approved
        $userToApprove = User::with('jobTitle')->find($id);
        if (!$userToApprove) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'
                ], 404);
            }
            return redirect()->route('requestapprove.index')->with('error', 'User not found.');
        }

        // Check if clinical job title requires professional registration verification
        $isClinicalDepartment = $userToApprove->jobTitle &&
            $userToApprove->jobTitle->clinical_or_non_clinical === 'Clinical' &&
            !empty($userToApprove->professional_reg_number);

        if ($isClinicalDepartment) {
            // Get current HR's workflow history - refresh to get latest data
            $currentHrHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', $actor->id)
                ->where('status', 0)
                ->first();

            if (!$currentHrHistory) {
                $errorMsg = 'Workflow history not found. Please refresh the page and try again.';
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg
                    ], 404);
                }
                return redirect()->back()->with('error', $errorMsg);
            }

            // Refresh the model to get latest data from database
            $currentHrHistory->refresh();

            // Check if verified - use truthy check to handle boolean, integer 1, string '1', etc.
            // The model cast should convert it to boolean, but we'll handle all cases
            $verifiedValue = $currentHrHistory->professional_reg_verified;
            $isVerified = (bool) $verifiedValue; // Convert to boolean for comparison

            if (!$isVerified) {
                Log::warning('Professional registration verification check failed', [
                    'workflow_id' => $workflow->id,
                    'user_id' => $id,
                    'actor_id' => $actor->id,
                    'verified_value' => $verifiedValue,
                    'verified_type' => gettype($verifiedValue),
                    'verified_bool' => (bool) $verifiedValue
                ]);

                $errorMsg = 'Professional registration number must be verified before approval for clinical departments. Please save the verification first by clicking "Save Verification" button.';
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg
                    ], 400);
                }
                return redirect()->back()->with('error', $errorMsg);
            }
        }

        try {
            DB::beginTransaction();

            // Update user status to active
            $userToApprove->status = 'active';
            $userToApprove->save();

            // Update workflow status
            $workflow->work_flow_status = "HR Form Approved";
            $workflow->work_flow_completed = 1;
            $workflow->save();

            // Team approval: Mark ALL pending HR entries as approved (not just the current approver's)
            // This ensures all HR members see the form as approved once one approves
            WorkflowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0) // All pending entries
                ->update([
                    'status' => 1, // Approved
                    'who_approve' => $actor->id, // Who approved (the actor)
                    'comments' => 'Approved by ' . $actor->fname . ' ' . $actor->lname,
                    'updated_at' => now(),
                ]);

            DB::commit();

            // Send queued notification to the requester
            if ($workflow->user && $workflow->user->email) {
                Notification::route('mail', $workflow->user->email)
                    ->notify(new HrFormApproved($workflow->user, $workflow));
            } else {
                Log::error('No user found for workflow ID: ' . $workflow->id);
            }

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'HR form approved successfully. User status updated to active.'
                ]);
            }

            return redirect()->route('requestapprove.index')->with('success', 'HR form approved successfully. User status updated to active.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving HR form: ' . $e->getMessage(), [
                'workflow_id' => $workflow->id ?? null,
                'user_id' => $id,
                'actor_id' => $actor->id,
                'exception' => $e->getTraceAsString()
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Approval failed: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('requestapprove.index')->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    public function verifyProfessionalRegistration(Request $request)
    {
        $actor = Auth::user();

        // Check if user has permission
        if (!$actor->hasRole('hr') && !$actor->hasPermissionTo('approve forms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to verify professional registration.'
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'verified' => 'required|boolean',
            'verification_notes' => 'nullable|string|max:1000',
            'license_valid_until' => 'required_if:verified,1|nullable|date',
            'license_provider' => 'nullable|string|max:255',
        ], [
            'license_valid_until.required_if' => 'License valid until date is required when verifying the professional registration.',
        ]);

        try {
            // Get workflow
            $workflow = Workflow::where('hr_form', $request->user_id)->orderBy('id', 'desc')->first();

            if (!$workflow) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workflow not found.'
                ], 404);
            }

            // Get current HR's workflow history
            $currentHrHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', $actor->id)
                ->where('status', 0)
                ->first();

            if (!$currentHrHistory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workflow history not found.'
                ], 404);
            }

            // Auto-fill license provider from professional registration number if not provided
            $licenseProvider = $request->license_provider;
            if (!$licenseProvider) {
                $user = \App\Models\User::find($request->user_id);
                if ($user && $user->professional_reg_number) {
                    // Parse professional registration number to extract type
                    if (preg_match('/^([A-Z]+):\s*(.+)$/', $user->professional_reg_number, $matches)) {
                        $regType = $matches[1];
                        $licenseProviders = [
                            'MCT' => 'Medical Council of Tanzania',
                            'TNMC' => 'Tanzania Nursing and Midwifery Council',
                            'TPB' => 'Tanzania Pharmacy Board',
                            'TPC' => 'Tanzania Physiotherapy Council',
                            'TMDC' => 'Tanzania Medical and Dental Council',
                            'Other' => 'Other'
                        ];
                        $licenseProvider = $licenseProviders[$regType] ?? $regType;
                    }
                }
            }

            // Update verification status (convert to boolean - ensure it's saved as 1 or 0 for MySQL)
            $verifiedBool = (bool) $request->verified;
            $currentHrHistory->professional_reg_verified = $verifiedBool ? 1 : 0;
            $currentHrHistory->professional_reg_verification_notes = $request->verification_notes ?? null;
            $currentHrHistory->license_valid_until = $request->license_valid_until;
            $currentHrHistory->license_provider = $licenseProvider;
            $currentHrHistory->save();

            // Refresh to confirm it was saved
            $currentHrHistory->refresh();

            Log::info('Professional registration verification saved', [
                'workflow_id' => $workflow->id,
                'user_id' => $request->user_id,
                'actor_id' => $actor->id,
                'verified' => $currentHrHistory->professional_reg_verified,
                'verified_type' => gettype($currentHrHistory->professional_reg_verified)
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->verified ? 'Professional registration verified successfully.' : 'Verification status updated.',
                'verified' => (bool) $currentHrHistory->professional_reg_verified
            ]);
        } catch (\Exception $e) {
            Log::error('Error verifying professional registration: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update verification status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function hrformReject(Request $request)
    {
        $actor = Auth::user();

        // Check if user has permission to reject
        if (!$actor->hasRole('hr') && !$actor->hasPermissionTo('approve forms')) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to reject HR forms.'
                ], 403);
            }
            return redirect()->route('requestapprove.index')->with('error', 'You do not have permission to reject HR forms.');
        }

        // Validate the request
        $request->validate([
            'id' => 'required|exists:workflows,hr_form',
            'comment' => 'required|string|min:10|max:1000',
        ], [
            'comment.required' => 'Please provide a rejection reason.',
            'comment.min' => 'Rejection reason must be at least 10 characters.',
            'comment.max' => 'Rejection reason must not exceed 1000 characters.',
        ]);

        // Find the workflow with user relationship
        $workflow = Workflow::with('user')->where('hr_form', $request->id)->first();

        if (!$workflow) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workflow not found.'
                ], 404);
            }
            return redirect()->route('requestapprove.index')->with('error', 'Workflow not found.');
        }

        try {
            DB::beginTransaction();

            // Update workflow status
            $workflow->work_flow_completed = 2;
            $workflow->work_flow_status = "HR Form Rejected";
            $workflow->save();

            // Team rejection: Mark ALL pending HR entries as rejected
            // This ensures all HR members see the form as rejected once one rejects
            $updated = WorkflowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0) // All pending entries
                ->update([
                    'status' => -1, // Rejected (using -1 for consistency with other forms)
                    'rejection_reason' => $request->comment,
                    'comments' => 'Rejected by ' . $actor->fname . ' ' . $actor->lname . ': ' . $request->comment,
                    'who_approve' => $actor->id,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                throw new \Exception('No pending workflow history found to reject.');
            }

            DB::commit();

            // Send queued rejection notification to the requester
            if ($workflow->user && $workflow->user->email) {
                Notification::route('mail', $workflow->user->email)
                    ->notify(new HrFormRejected($workflow->user, $workflow, $request->comment));
            } else {
                Log::error('User or user email not found for notification.', [
                    'workflow_id' => $workflow->id,
                    'user_id' => $request->id
                ]);
            }

            // Return success message
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'HR form rejected successfully and notification sent.'
                ]);
            }

            return redirect()->route('requestapprove.index')->with('success', 'HR form rejected successfully and notification sent.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            Log::error('Error rejecting HR form: ' . $e->getMessage(), [
                'workflow_id' => $workflow->id ?? null,
                'user_id' => $request->id,
                'actor_id' => $actor->id,
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject the form: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('requestapprove.index')->with('error', 'Failed to reject the form: ' . $e->getMessage());
        }
    }


    public function showBankForm(Request $request)
    {
        $user = Auth::user();
        $isReportViewer = $user->can('ict_acces_report');
        
        // Get the workflow first
        $workflow = Workflow::where('bank_form', $request->id)->first();
        
        if (!$workflow) {
            return redirect()->route('requestapprove.index')->with('error', 'Bank form not found.');
        }

        // Get bank form details with user info
        $bankForm = BankDetail::join('users', 'users.id', '=', 'bank_details.userId')
            ->where('bank_details.id', $request->id)
            ->first([
                'bank_details.*',
                'users.*',
                'bank_details.id as access_id'
            ]);

        if (!$bankForm) {
            return redirect()->route('requestapprove.index')->with('error', 'Bank form not found.');
        }

        // Check if workflow is completed (approved)
        $isApproved = $workflow->work_flow_completed == 1;

        // Get the HR who approved it (if approved)
        $HrToApprove = null;
        if ($isApproved) {
            $HrToApprove = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'hr');
                })
                ->where('work_flow_histories.work_flow_id', $workflow->id)
                ->where('work_flow_histories.status', 1)
                ->orderBy('work_flow_histories.updated_at', 'desc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();
        }

        // Check if current user is HR and can view this form
        $isHR = $user->hasRole('hr');
        if (!$isHR && !$isReportViewer) {
            return redirect()->route('requestapprove.index')->with('error', 'You do not have permission to view this form.');
        }

        // Check if current user has a pending workflow history for this form
        $currentUserWorkflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->where('attended_by', $user->id)
            ->first();

        // If not approved and current user doesn't have a workflow history, create one for viewing
        if (!$isApproved && !$currentUserWorkflowHistory && !$isReportViewer) {
            // Check if there are any pending workflow histories
            $hasPendingHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->exists();
            
            // If no pending history exists, it means it might have been rejected or something else
            if (!$hasPendingHistory) {
                return redirect()->route('requestapprove.index')->with('error', 'This form is no longer available for approval.');
            }
        }

        return view('profile.bank_confirm', compact('user', 'bankForm', 'HrToApprove', 'isApproved', 'workflow'));
    }

    public function approveBankForm(Request $request)
    {
        // Retrieve the workflow based on the access ID
        $workflow = Workflow::where('bank_form', $request->access_id)->first();
        if (!$workflow) {
            return response()->json(['error' => "Workflow not found for access ID {$request->access_id}"], 404);
        }

        $workflowHistory = WorkflowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->first();

        if (!$workflowHistory) {
            return response()->json([
                'error' => "WorkflowHistory not found for Workflow ID {$workflow->id} with pending status."
            ], 404);
        }

        $workflow->work_flow_status = 'Bank details Confirmed';
        $workflow->work_flow_completed = 1;
        $workflow->save();

        $workflowHistories = WorkflowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->get();

        foreach ($workflowHistories as $workflowHistory) {
            $workflowHistory->status = 1;
            $workflowHistory->who_approve = Auth::id();
            $workflowHistory->save();
        }

        return response()->json([
            'message' => 'Bank Details confirmed successfully for all HRs.',
            'workflow' => $workflow,
        ]);
    }

    public function rejectBankForm(Request $request)
    {
        $workflow = Workflow::where('bank_form', $request->access_id)->first();
        if (!$workflow) {
            return response()->json(['error' => "Workflow not found for access ID {$request->access_id}"], 404);
        }

        $pendingCount = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->count();

        if ($pendingCount > 0) {
            // Update ALL pending history rows to rejected
            WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->update([
                    'status' => -1,
                    'attended_by' => Auth::user()->id,
                    'rejection_reason' => $request->reason,
                    'decision_date' => now()->format('Y-m-d'),
                ]);

            $workflow->work_flow_status = 'Bank Form Rejected';
            $workflow->save();

            return response()->json(['message' => 'Bank Form rejected successfully.', 'workflow' => $workflow]);
        } else {
            return response()->json(['error' => "No pending approval found for Workflow ID {$workflow->id}"], 404);
        }
    }


    public function showHeslbkForm(Request $request)
    {
        $user = Auth::user();
        $isReportViewer = $user->can('ict_acces_report');
        
        // Get the workflow first
        $workflow = Workflow::where('heslb_form', $request->id)->first();
        
        if (!$workflow) {
            return redirect()->route('requestapprove.index')->with('error', 'HESLB form not found.');
        }

        // Get HESLB form details with user info
        $heslbForm = LoanDeclaration::join('users', 'users.id', '=', 'loan_declarations.userId')
            ->where('loan_declarations.id', $request->id)
            ->first([
                'loan_declarations.*',
                'users.*',
                'loan_declarations.id as access_id'
            ]);

        if (!$heslbForm) {
            return redirect()->route('requestapprove.index')->with('error', 'HESLB form not found.');
        }

        // Check if workflow is completed (approved)
        $isApproved = $workflow->work_flow_completed == 1;

        // Check if form is rejected
        $isRejected = false;
        $rejectedBy = null;
        $rejectionReason = null;
        $rejectedAt = null;

        // Check for rejection in workflow history
        $rejectedHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->where('status', -1)
            ->first();

        if ($rejectedHistory) {
            $isRejected = true;
            $rejectionReason = $rejectedHistory->rejection_reason;
            $rejectedAt = $rejectedHistory->updated_at;
            
            // Get who rejected it
            if ($rejectedHistory->attended_by) {
                $rejectedBy = User::find($rejectedHistory->attended_by);
            }
        }

        // Get the HR who approved it, or the assigned HR officer if still pending
        $HrToApprove = null;
        if ($isApproved) {
            $HrToApprove = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'hr');
                })
                ->where('work_flow_histories.work_flow_id', $workflow->id)
                ->where('work_flow_histories.status', 1)
                ->orderBy('work_flow_histories.updated_at', 'desc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();
        } else {
            // Show the assigned HR officer even if not yet approved
            $hrHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->whereHas('attendedBy', function ($q) {
                    $q->role('hr');
                })
                ->first();
            if ($hrHistory && $hrHistory->attended_by) {
                $HrToApprove = User::find($hrHistory->attended_by);
            }
        }

        // Check if current user is HR and can view this form
        $isHR = $user->hasRole('hr');
        if (!$isHR && !$isReportViewer) {
            return redirect()->route('requestapprove.index')->with('error', 'You do not have permission to view this form.');
        }

        // Check if current user has a pending workflow history for this form
        $currentUserWorkflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->where('attended_by', $user->id)
            ->first();

        // If not approved, not rejected, and current user doesn't have a workflow history
        if (!$isApproved && !$isRejected && !$currentUserWorkflowHistory && !$isReportViewer) {
            // Check if there are any pending workflow histories
            $hasPendingHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->exists();
            
            // If no pending history exists, it means it might have been rejected or something else
            if (!$hasPendingHistory) {
                return redirect()->route('requestapprove.index')->with('error', 'This form is no longer available for approval.');
            }
        }

        return view('profile.heslb_confirm', compact('user', 'heslbForm', 'HrToApprove', 'isApproved', 'isRejected', 'rejectedBy', 'rejectionReason', 'rejectedAt', 'workflow'));
    }

    public function approveHeslbForm(Request $request)
    {
        // Retrieve the workflow based on the access ID
        $workflow = Workflow::where('heslb_form', $request->access_id)->first();
        if (!$workflow) {
            return response()->json(['error' => "Workflow not found for access ID {$request->access_id}"], 404);
        }

        $workflowHistory = WorkflowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->first();

        if (!$workflowHistory) {
            return response()->json([
                'error' => "WorkflowHistory not found for Workflow ID {$workflow->id} with pending status."
            ], 404);
        }

        $workflow->work_flow_status = 'Heslb Form Confirmed';
        $workflow->work_flow_completed = 1;
        $workflow->save();

        $workflowHistories = WorkflowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->get();

        foreach ($workflowHistories as $workflowHistory) {
            $workflowHistory->status = 1;
            $workflowHistory->who_approve = Auth::id();
            $workflowHistory->save();
        }

        return response()->json([
            'message' => 'HESLB Form confirmed successfully for all HRs.',
            'workflow' => $workflow,
        ]);
    }

    public function rejectHeslbForm(Request $request)
    {
        $workflow = Workflow::where('heslb_form', $request->access_id)->first();
        
        if (!$workflow) {
            return response()->json([
                'error' => "Workflow not found for access ID {$request->access_id}"
            ], 404);
        }

        $pendingCount = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->count();

        if ($pendingCount > 0) {
            // Update ALL pending history rows to rejected
            WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->update([
                    'status' => -1,
                    'attended_by' => Auth::user()->id,
                    'rejection_reason' => $request->comment ?? $request->reason,
                    'decision_date' => now()->format('Y-m-d'),
                ]);

            // Update workflow status
            $workflow->work_flow_status = 'HESLB Form Rejected';
            $workflow->save();

            return response()->json([
                'message' => 'HESLB Form rejected successfully.',
                'workflow' => $workflow,
            ]);
        } else {
            return response()->json([
                'error' => "WorkflowHistory not found for Workflow ID {$workflow->id} with pending status."
            ], 404);
        }
    }

    public function showNhifForm(Request $request)
    {
        $user = Auth::user();
        $isReportViewer = $user->can('ict_acces_report');
        
        // Get the workflow first
        $workflow = Workflow::where('nhif_form', $request->id)->first();
        
        if (!$workflow) {
            return redirect()->route('requestapprove.index')->with('error', 'NHIF form not found.');
        }

        // Get NHIF form details with user info
        $nhifForm = NhifRegistration::join('users', 'users.id', '=', 'nhif_registrations.userId')
            ->where('nhif_registrations.id', $request->id)
            ->first([
                'nhif_registrations.*',
                'users.*',
                'nhif_registrations.id as access_id'
            ]);

        if (!$nhifForm) {
            return redirect()->route('requestapprove.index')->with('error', 'NHIF form not found.');
        }

        // Check if workflow is completed (approved)
        $isApproved = $workflow->work_flow_completed == 1;

        // Get the HR who approved it (if approved)
        $HrToApprove = null;
        if ($isApproved) {
            $HrToApprove = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'hr');
                })
                ->where('work_flow_histories.work_flow_id', $workflow->id)
                ->where('work_flow_histories.status', 1)
                ->orderBy('work_flow_histories.updated_at', 'desc')
                ->select('users.*', 'work_flow_histories.updated_at')
                ->first();
        }

        // Check if current user is HR and can view this form
        $isHR = $user->hasRole('hr');
        if (!$isHR && !$isReportViewer) {
            return redirect()->route('requestapprove.index')->with('error', 'You do not have permission to view this form.');
        }

        // Check if current user has a pending workflow history for this form
        $currentUserWorkflowHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->where('attended_by', $user->id)
            ->first();

        // If not approved and current user doesn't have a workflow history, create one for viewing
        if (!$isApproved && !$currentUserWorkflowHistory && !$isReportViewer) {
            // Check if there are any pending workflow histories
            $hasPendingHistory = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->exists();
            
            // If no pending history exists, it means it might have been rejected or something else
            if (!$hasPendingHistory) {
                return redirect()->route('requestapprove.index')->with('error', 'This form is no longer available for approval.');
            }
        }

        return view('profile.nhif_confirm', compact('user', 'nhifForm', 'HrToApprove', 'isApproved', 'workflow'));
    }

    public function approveNhifForm(Request $request)
    {
        // Retrieve the workflow based on the access ID
        $workflow = Workflow::where('nhif_form', $request->access_id)->first();
        if (!$workflow) {
            return response()->json(['error' => "Workflow not found for access ID {$request->access_id}"], 404);
        }

        $workflowHistory = WorkflowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->first();

        if (!$workflowHistory) {
            return response()->json([
                'error' => "WorkflowHistory not found for Workflow ID {$workflow->id} with pending status."
            ], 404);
        }

        $workflow->work_flow_status = 'NHIF Form Confirmed';
        $workflow->work_flow_completed = 1;
        $workflow->save();

        $workflowHistories = WorkflowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->get();

        foreach ($workflowHistories as $workflowHistory) {
            $workflowHistory->status = 1;
            $workflowHistory->who_approve = Auth::id();
            $workflowHistory->save();
        }

        return response()->json([
            'message' => 'NHIF Form confirmed successfully .',
            'workflow' => $workflow,
        ]);
    }

    public function rejectNhifForm(Request $request)
    {
        $workflow = Workflow::where('nhif_form', $request->access_id)->first();
        if (!$workflow) {
            return response()->json(['error' => "Workflow not found for access ID {$request->access_id}"], 404);
        }

        $pendingCount = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->count();

        if ($pendingCount > 0) {
            // Update ALL pending history rows to rejected
            WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->update([
                    'status' => -1,
                    'attended_by' => Auth::user()->id,
                    'rejection_reason' => $request->reason,
                    'decision_date' => now()->format('Y-m-d'),
                ]);

            $workflow->work_flow_status = 'NHIF Form Rejected';
            $workflow->save();

            return response()->json(['message' => 'NHIF Form rejected successfully.', 'workflow' => $workflow]);
        } else {
            return response()->json(['error' => "No pending approval found for Workflow ID {$workflow->id}"], 404);
        }
    }

    public function showIdform(Request $request)
    {
        $user = Auth::user();
        $isReportViewer = $user->can('ict_acces_report');

        // Get the workflow first
        $workflow = Workflow::where('id_form', $request->id)->first();

        if (!$workflow) {
            return redirect()->route('requestapprove.index')->with('error', 'ID card form not found.');
        }

        // Get ID form details with user info (no workflow history join)
        $idForm = IDCards::join('users', 'users.id', '=', 'id_card_requests.user_id')
            ->where('id_card_requests.id', $request->id)
            ->first([
                'id_card_requests.*',
                'users.*',
                'id_card_requests.id as access_id'
            ]);

        if (!$idForm) {
            return redirect()->route('requestapprove.index')->with('error', 'ID card form not found.');
        }

        // Get the HR who approved it
        $HrToApprove = User::join('work_flow_histories', 'work_flow_histories.who_approve', '=', 'users.id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })
            ->where('work_flow_histories.work_flow_id', $workflow->id)
            ->where('work_flow_histories.status', 1)
            ->orderBy('work_flow_histories.updated_at', 'desc')
            ->select('users.*', 'work_flow_histories.updated_at')
            ->first();

        // ID form only requires HR approval — if HR approved, it's done
        // Also fix old workflows stuck at "Pending IT Approval" from the old flow
        $isApproved = $workflow->work_flow_completed == 1;
        if (!$isApproved && $HrToApprove) {
            $workflow->work_flow_completed = 1;
            $workflow->work_flow_status = 'ID Form Confirmed';
            $workflow->save();

            // Mark ALL remaining pending workflow histories (HR and IT) as completed
            WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->update(['status' => 1, 'decision_date' => now()->format('Y-m-d')]);

            $isApproved = true;
        }

        // Check permission: must be HR or report viewer
        $isHR = $user->hasRole('hr');
        if (!$isHR && !$isReportViewer) {
            return redirect()->route('requestapprove.index')->with('error', 'You do not have permission to view this form.');
        }

        return view('profile.IDCard_confirm', compact('user', 'idForm', 'HrToApprove', 'isApproved', 'workflow'));
    }


    public function approveIdForm(Request $request)
    {
        // Retrieve the workflow based on the access ID
        $workflow = Workflow::where('id_form', $request->access_id)->first();
        if (!$workflow) {
            return response()->json(['error' => "Workflow not found for access ID {$request->access_id}"], 404);
        }

        $user = Auth::user();

        // Check if user has HR role
        if (!$user->hasRole('hr')) {
            return response()->json(['error' => 'Unauthorized: User does not have HR role'], 403);
        }

        // Check if there's a pending approval for HR
        $hrUserIds = User::role('hr')->pluck('id')->toArray();
        
        $pendingApprovals = WorkFlowHistory::where('work_flow_id', $workflow->id)
            ->where('status', 0)
            ->whereIn('attended_by', $hrUserIds)
            ->get();

        if ($pendingApprovals->isEmpty()) {
            $hasApproved = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 1)
                ->whereIn('attended_by', $hrUserIds)
                ->exists();

            if ($hasApproved) {
                return response()->json([
                    'message' => 'ID Form has already been approved',
                    'workflow' => $workflow,
                    'current_status' => $workflow->work_flow_status
                ]);
            }

            return response()->json([
                'error' => 'No pending approval found for HR'
            ], 404);
        }

        // Get the forwarded user (user who initiated the workflow)
        $forwardUser = User::join('workflows', 'workflows.user_id', '=', 'users.id')
            ->where('workflows.id_form', $request->access_id)
            ->select('users.username')
            ->first();

        if (!$forwardUser) {
            Log::error('Forward user not found for workflow ID ' . $workflow->id);
            return response()->json(['error' => 'Forward user not found'], 404);
        }

        DB::transaction(function () use ($pendingApprovals, $workflow, $user, $forwardUser, $hrUserIds) {
            // Mark ALL pending HR approvals as approved
            WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->whereIn('attended_by', $hrUserIds)
                ->update([
                    'status' => 1,
                    'who_approve' => $user->id,
                    'decision_date' => now()->format('Y-m-d'),
                ]);

            // HR approval is the final step — mark workflow as completed
            $workflow->work_flow_status = 'ID Form Confirmed';
            $workflow->work_flow_completed = 1;
            $workflow->save();
        });

        return response()->json([
            'message' => 'ID Form approved successfully',
            'workflow' => $workflow,
            'current_status' => $workflow->work_flow_status
        ]);
    }


    public function rejectIdForm(Request $request)
    {
        $workflow = Workflow::where('id_form', $request->access_id)->first();
        if (!$workflow) {
            return response()->json(['error' => "Workflow not found for access ID {$request->access_id}"], 404);
        }

        $user = Auth::user();

        // Check if user has HR role
        if (!$user->hasRole('hr')) {
            return response()->json(['error' => 'Unauthorized: User does not have HR role'], 403);
        }

        $hrUserIds = User::role('hr')->pluck('id')->toArray();

        DB::transaction(function () use ($workflow, $user, $hrUserIds, $request) {
            WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('status', 0)
                ->whereIn('attended_by', $hrUserIds)
                ->update([
                    'status' => -1,
                    'who_approve' => $user->id,
                    'rejection_reason' => $request->reason ?? 'Rejected by HR',
                    'decision_date' => now()->format('Y-m-d'),
                ]);

            $workflow->work_flow_status = 'Rejected by HR';
            $workflow->work_flow_completed = 0;
            $workflow->save();
        });

        return response()->json([
            'message' => 'ID Form rejected successfully by HR',
            'workflow' => $workflow,
            'current_status' => $workflow->work_flow_status
        ]);
    }

    //change request
    public function show($id)
    {
        $user = Auth::user();

        // Check if user has permission to view change requests
        // Allow: the requester themselves, users with 'view change management' permission, price_committee role, or HEC members
        $changeRequest = ChangeRequest::with([
            'user' => function ($query) {
                $query->with(['jobTitle', 'department']);
            },
            'workflow.histories.forwardedBy',
            'workflow.histories.attendedBy',
            'workflow.histories.approver'
        ])->findOrFail($id);

        $isOwner = $changeRequest->user_id == $user->id;
        if (
            !$isOwner
            && !$user->hasPermissionTo('view change management')
            && !$user->hasRole('price_committee')
            && !$user->hasAnyRole(['cms', 'cfo', 'coo', 'it', 'super-admin'])
        ) {
            abort(403, 'Access denied. You do not have permission to view this change request.');
        }

        $hecMembers = User::role(['coo', 'cfo', 'cms'])->get();

        // dd($hecMembers);

        return view('profile.changerequest', compact('changeRequest', 'hecMembers'));
    }


    //change requst - Updated to handle both price and non-price workflows
    public function approveAndForwardToPriceCommittee($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $changeRequest = ChangeRequest::with('workflow')->findOrFail($id);
            $workflow = $changeRequest->workflow;
            $changeType = $changeRequest->change_type ?? 'non_price';

            // Get current pending step for this user
            $currentStep = WorkFlowHistory::where('work_flow_id', $workflow->id)
                ->where('attended_by', auth()->id())
                ->where('status', 0)
                ->latest()
                ->first();

            if (!$currentStep) {
                return redirect()->back()->with('error', 'No pending approval step found.');
            }

            // Handle rejection
            if ($request->has('reject')) {
                $currentStep->update([
                    'status' => 2, // Rejected
                    'rejection_reason' => $request->input('rejection_reason'),
                    'comments' => $request->input('comments'),
                    'who_approve' => auth()->id(),
                    'attend_date' => Carbon::now()->format('Y-m-d'),
                    'remark' => 'Rejected by ' . $currentStep->step_name,
                ]);

                $workflow->update([
                    'work_flow_status' => 'Rejected',
                ]);

                DB::commit();
                return redirect('/requestapprove')->with('error', 'Request rejected: ' . $request->input('rejection_reason'));
            }

            // Approve current step with comments
            $currentStep->update([
                'status' => 1, // Approved
                'comments' => $request->input('comments'),
                'who_approve' => auth()->id(),
                'attend_date' => Carbon::now()->format('Y-m-d'),
                'remark' => 'Approved by ' . $currentStep->step_name,
            ]);

            // Determine next step based on change type and current step
            $nextStep = null;
            $nextStatus = null;
            $nextUsers = collect();

            if ($changeType === 'price') {
                // Price workflow: Line Manager → Price Committee → CMS (HEC) → Price Committee (implementation)
                switch ($currentStep->step_name) {
                    case 'Line Manager':
                        // Forward to all Price Committee members
                        $nextUsers = User::role('price_committee')->get();
                        $nextStep = 'Price Committee';
                        $nextStatus = 'Pending at Price Committee';
                        break;

                    case 'Price Committee':
                        // Check if this is first approval or implementation
                        $priceCommitteeApprovals = WorkFlowHistory::where('work_flow_id', $workflow->id)
                            ->where('step_name', 'Price Committee')
                            ->where('status', 1)
                            ->count();

                        if ($priceCommitteeApprovals == 1) {
                            // First approval - forward to CMS (HEC member)
                            $user = User::find($workflow->user_id);
                            $department = Departments::find($user->deptId);

                            if ($department && $department->hec_id) {
                                $hec = Hec::find($department->hec_id);
                                if ($hec) {
                                    $hecLevelName = strtoupper(trim($hec->hec_level_name));
                                    $roleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CCDRO' => 'ccdro'];
                                    $roleSlug = $roleMap[$hecLevelName] ?? 'cms';

                                    $hecMember = User::role($roleSlug)->first();
                                    if ($hecMember) {
                                        $nextUsers = collect([$hecMember]);
                                        $nextStep = 'HEC Member';
                                        $nextStatus = 'Pending at HEC Member';
                                    }
                                }
                            }
                        } else {
                            // Implementation - forward back to Price Committee
                            $nextUsers = User::role('price_committee')->get();
                            $nextStep = 'Price Committee';
                            $nextStatus = 'Pending at Price Committee for Implementation';
                        }
                        break;

                    case 'HEC Member':
                        // After HEC approval, forward back to Price Committee for implementation
                        $nextUsers = User::role('price_committee')->get();
                        $nextStep = 'Price Committee';
                        $nextStatus = 'Pending at Price Committee for Implementation';
                        break;
                }
            } else {
                // Non-price workflow: Line Manager → HEC → IT
                switch ($currentStep->step_name) {
                    case 'Line Manager':
                        // Forward to HEC member of the department
                        $user = User::find($workflow->user_id);
                        $department = Departments::find($user->deptId);

                        if ($department && $department->hec_id) {
                            $hec = Hec::find($department->hec_id);
                            if ($hec) {
                                $hecLevelName = strtoupper(trim($hec->hec_level_name));
                                $roleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CCDRO' => 'ccdro'];
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

                        if ($itUsers->isEmpty()) {
                            DB::rollBack();
                            Log::warning('No IT users found for change request implementation', [
                                'change_request_id' => $changeRequest->id,
                                'workflow_id' => $workflow->id
                            ]);
                            return redirect()->back()->with('error', 'No IT users found for implementation. Please contact system administrator.');
                        }

                        $nextUsers = $itUsers;
                        $nextStep = 'IT';
                        $nextStatus = 'Pending at IT';
                        break;

                    case 'IT':
                        // Final approval - mark as completed
                        $workflow->update([
                            'work_flow_status' => 'Fully Approved',
                            'work_flow_completed' => 1,
                        ]);
                        DB::commit();
                        return redirect('/requestapprove')->with('success', 'Change request fully approved and ready for implementation.');
                }
            }

            // Create workflow history for next step
            if ($nextStep && $nextUsers->isNotEmpty()) {
                foreach ($nextUsers as $user) {
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'step_name' => $nextStep,
                        'forwarded_by' => auth()->id(),
                        'attended_by' => $user->id,
                        'status' => 0,
                        'remark' => 'Awaiting approval from ' . $nextStep,
                        'attend_date' => Carbon::now()->format('Y-m-d'),
                        'parent_id' => $currentStep->id,
                    ]);
                }

                $workflow->update([
                    'work_flow_status' => $nextStatus,
                ]);
            } elseif ($nextStep && $nextUsers->isEmpty()) {
                // Handle case where next step is set but no users found
                DB::rollBack();
                Log::error('Next step defined but no users found', [
                    'change_request_id' => $changeRequest->id,
                    'workflow_id' => $workflow->id,
                    'next_step' => $nextStep,
                    'change_type' => $changeType
                ]);
                return redirect()->back()->with('error', 'Unable to forward request: No users found for ' . $nextStep . '. Please contact system administrator.');
            } elseif ($nextStep === 'Price Committee' && $changeType === 'price') {
                // Special case: Price Committee implementation - check if all approvals done
                $priceCommitteeImplementationApprovals = WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->where('step_name', 'Price Committee')
                    ->where('status', 1)
                    ->count();

                if ($priceCommitteeImplementationApprovals >= 2) {
                    // Both Price Committee approvals done - mark as completed
                    $workflow->update([
                        'work_flow_status' => 'Fully Approved',
                        'work_flow_completed' => 1,
                    ]);
                    DB::commit();
                    return redirect('/requestapprove')->with('success', 'Change request fully approved and ready for implementation.');
                }
            }

            DB::commit();
            return redirect('/requestapprove')->with('success', 'Approved and forwarded to ' . $nextStep . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving change request: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while processing the approval.');
        }
    }
    public function sendToHec(Request $request)
    {
        $changeRequest = ChangeRequest::with([
            'workflow',

        ])->findOrFail($request->changeRequestId);

        $this->saveWorkflowHistory([
            'work_flow_id' => $changeRequest->workflow->id,
            'step_name' => 'Hec Member',
            'forwarded_by' => auth()->id(),
            'attended_by' => $request->hec_member,
            'status' => 0,
            'remark' => 'Awaiting approval from Hec Member',
            'attend_date' => Carbon::now()->format('d F Y'),
            // 'parent_id' => $currentStep->id,
        ]);
        // dd($changeRequest->workflow);

        return redirect('/requestapprove')->with('success', 'Approved and forwarded to  Hec Member.');
    }
}
