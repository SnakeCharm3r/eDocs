<?php

namespace App\Http\Controllers;
use App\Models\User;

use App\Models\Policy;
use Illuminate\Http\Request;
use App\Models\HealthDetails;
use App\Models\LanguageKnowledge;
use App\Models\Announcement;
use App\Models\JobTitle;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    //
    public function index()
    {

        // Logic to fetch and pass data based on roles and permissions
        $policies = Policy::all();
        $announcements = Announcement::with('user')->latest()->get();
        $user = Auth::user()->load('jobTitle');
        $totalUsers = \App\Models\User::count();
        $healthDetails = HealthDetails::join('users', 'health_details.userId', '=', 'users.id')
        ->where('health_details.userId', Auth::user()->id)
        ->select('health_details.*', 'users.*')
        ->get();

        $languageData = LanguageKnowledge::join('users', 'language_knowledge.userId', '=', 'users.id')
        ->where('language_knowledge.userId', Auth::user()->id)
        ->select('language_knowledge.*', 'users.*')
        ->get();

        // Check license status for clinical users
        $licenseStatus = null;
        $isClinicalDepartment = $user->jobTitle && $user->jobTitle->clinical_or_non_clinical === 'Clinical';
        
        if ($isClinicalDepartment) {
            // Get HR workflow for this user
            $hrWorkflow = \App\Models\Workflow::where('hr_form', $user->id)->orderBy('id', 'desc')->first();
            
            if ($hrWorkflow) {
                // Get the most recent HR workflow history with license info
                $hrWorkflowHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $hrWorkflow->id)
                    ->whereNotNull('license_valid_until')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($hrWorkflowHistory && $hrWorkflowHistory->license_valid_until) {
                    $expiryDate = Carbon::parse($hrWorkflowHistory->license_valid_until)->startOfDay();
                    $today = Carbon::now()->startOfDay();
                    $daysUntilExpiry = $expiryDate->diffInDays($today, false);
                    
                    // License is expired only if expiry date is before today (not including today)
                    $isExpired = $expiryDate->lt($today);
                    
                    // Expiring soon: not expired and expires within 20 days (daysUntilExpiry is negative for future dates)
                    $expiringSoon = !$isExpired && $daysUntilExpiry <= 0 && $daysUntilExpiry >= -20;
                    
                    $licenseStatus = [
                        'has_license' => true,
                        'is_expired' => $isExpired,
                        'days_until_expiry' => $daysUntilExpiry,
                        'expiry_date' => $hrWorkflowHistory->license_valid_until,
                        'expiring_soon' => $expiringSoon,
                    ];
                } else {
                    // No license found in workflow history
                    $licenseStatus = [
                        'has_license' => false,
                        'is_expired' => false,
                        'days_until_expiry' => null,
                        'expiry_date' => null,
                        'expiring_soon' => false,
                    ];
                }
            } else {
                // No HR workflow found
                $licenseStatus = [
                    'has_license' => false,
                    'is_expired' => false,
                    'days_until_expiry' => null,
                    'expiry_date' => null,
                    'expiring_soon' => false,
                ];
            }
        }

        $data = [];

        if ($user->hasRole('requester')) {
            $data['requester_content'] = 'Content for requesters';
        }

        if ($user->hasRole('head of hr')) {
            $data['hr_content'] = 'Content for HR heads';
        }

        if ($user->hasRole('head of it')) {
            $data['it_content'] = 'Content for IT heads';
        }

        if ($user->hasRole('head of department')) {
            $data['hod_content'] = 'Content for heads of department';
        }

        if ($user->hasRole('acting hod')) {
            $data['acting_hod_content'] = 'Content for acting heads of department';
        }

        if ($user->hasRole('super admin')) {
            $data['admin_content'] = 'Content for super admin';
        }

        // Get pending recruitment requisitions for HR
        $pendingRecruitmentRequisitions = collect();
        
        if ($user->hasRole('hr')) {
            $pendingRecruitmentRequisitions = \App\Models\RecruitmentRequisition::with(['department', 'hod', 'ceoReviewer', 'hecReviewer'])
                ->whereIn('status', ['hec_no_objection_in_budget', 'ceo_approved', 'ready_for_hr_processing'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('dashboard', compact('data','policies','user','healthDetails','languageData','totalUsers','announcements','licenseStatus','isClinicalDepartment','pendingRecruitmentRequisitions'));
    }

    public function dashboard()
    {
        $showAlert = false;

        if (auth()->check()) {
            $user = auth()->user();

            // Check if the session variable for first login is set
            if (!session()->has('first_login_shown')) {
                // Set session variable
                session(['first_login_shown' => true]);
                $showAlert = true;
            }
        }

        // Get notifications for forms and workflows
        $notifications = $this->getFormNotifications();

        // // Check if the signature is empty
        // $signature = !empty($user->signature);

        // Debugging statement
        \Log::info('Show Alert: ' . ($showAlert ? 'true' : 'false'));

        return view('dashboard', compact('showAlert','hasSignature', 'notifications'));
    }

    /**
     * Get notifications for pending forms and workflows
     */
    private function getFormNotifications()
    {
        if (!Auth::check()) {
            return collect([]);
        }

        $user = Auth::user();
        $notifications = collect([]);

        // Get pending workflow requests
        $pendingWorkflows = \App\Models\Workflow::join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->join('users as requesters', 'requesters.id', '=', 'workflows.user_id')
            ->where('work_flow_histories.attended_by', $user->id)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('workflows.ict_request_resource_id')
                      ->orWhereNotNull('workflows.hr_form')
                      ->orWhereNotNull('workflows.bank_form')
                      ->orWhereNotNull('workflows.heslb_form')
                      ->orWhereNotNull('workflows.nhif_form')
                      ->orWhereNotNull('workflows.id_form')
                      ->orWhereNotNull('workflows.change_request_id');
                })
                ->whereIn('work_flow_histories.status', [0, 1]);
            })
            ->orWhere(function ($query) {
                $query->whereNotNull('workflows.requisition_id')
                      ->whereIn('work_flow_histories.requisition_status', [0, 1]);
            })
            ->select('workflows.*', 'work_flow_histories.*', 'requesters.username as requester_name', 'requesters.fname', 'requesters.lname')
            ->orderBy('workflows.created_at', 'desc')
            ->get();

        foreach ($pendingWorkflows as $workflow) {
            $formType = $this->getFormType($workflow);
            $notifications->push([
                'type' => 'workflow',
                'form_type' => $formType,
                'title' => "Pending {$formType} Approval",
                'message' => "{$workflow->fname} {$workflow->lname} ({$workflow->requester_name}) submitted a {$formType}",
                'url' => $this->getFormUrl($workflow),
                'created_at' => $workflow->created_at,
                'id' => $workflow->id,
            ]);
        }

        // Get pending clearance forms
        $pendingClearance = \App\Models\Clearance_work_flow::join('clearance_work_flow_histories', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
            ->join('users as requesters', 'requesters.id', '=', 'clearance_work_flows.user_id')
            ->join('clearance_forms', 'clearance_forms.id', '=', 'clearance_work_flows.requested_resource_id')
            ->where('clearance_work_flow_histories.attended_by', $user->id)
            ->where('clearance_work_flow_histories.status', 0)
            ->where('clearance_forms.status', '!=', 'rejected')
            ->select('clearance_work_flows.*', 'clearance_work_flow_histories.*', 'requesters.username as requester_name', 'requesters.fname', 'requesters.lname', 'clearance_work_flows.requested_resource_id')
            ->orderBy('clearance_work_flows.created_at', 'desc')
            ->get();

        foreach ($pendingClearance as $clearance) {
            $stepName = $clearance->step_name ?? 'Clearance Form';
            $notifications->push([
                'type' => 'clearance',
                'form_type' => 'Clearance Form',
                'title' => "Pending Clearance Form - {$stepName}",
                'message' => "{$clearance->fname} {$clearance->lname} submitted a clearance form requiring your {$stepName} approval",
                'url' => route('exit_forms.show', ['id' => $clearance->requested_resource_id]),
                'created_at' => $clearance->created_at,
                'id' => $clearance->requested_resource_id,
            ]);
        }

        return $notifications->sortByDesc('created_at')->take(10);
    }

    private function getFormType($workflow)
    {
        if ($workflow->ict_request_resource_id) return 'ICT Access Form';
        if ($workflow->hr_form) return 'HR Form';
        if ($workflow->bank_form) return 'Bank Details Form';
        if ($workflow->heslb_form) return 'Loan Board Form';
        if ($workflow->nhif_form) return 'NHIF Form';
        if ($workflow->id_form) return 'ID Form';
        if ($workflow->change_request_id) return 'Change Request';
        if ($workflow->requisition_id) return 'RRF Request';
        return 'Form';
    }

    private function getFormUrl($workflow)
    {
        if ($workflow->ict_request_resource_id) return route('show_form', ['id' => $workflow->ict_request_resource_id]);
        if ($workflow->hr_form) return route('hr_form', ['id' => $workflow->hr_form]);
        if ($workflow->bank_form) return route('bank_form', ['id' => $workflow->bank_form]);
        if ($workflow->heslb_form) return route('heslb_form', ['id' => $workflow->heslb_form]);
        if ($workflow->nhif_form) return route('nhif_form', ['id' => $workflow->nhif_form]);
        if ($workflow->id_form) return route('id_form', ['id' => $workflow->id_form]);
        if ($workflow->change_request_id) return route('change_request.show', ['id' => $workflow->change_request_id]);
        if ($workflow->requisition_id) return route('requisitions.show', ['id' => $workflow->requisition_id]);
        return '#';
    }



    public function reviewDashboard()
    {
        // Get the authenticated user
        $user = Auth::user();

        $data = DB::table('work_flow_histories')
            ->join('workflows', 'workflows.id', 'work_flow_histories.work_flow_id')
            ->where('hr_form', $user->id)
            ->orderBy('workflows.created_at', 'desc')
            ->first();

        // Check if data exists
        if (!$data) {
            return redirect()->route('profile.confirm')->with('error', 'No HR form submission found. Please submit your form first.');
        }

        // Determine status based on workflow history
        if ($data->status == 0) {
            $decision_date = null;
            $status = 'Pending';
        } elseif ($data->status == 1) {
            $decision_date = Carbon::parse($data->updated_at)->format('d F Y');
            $status = 'Accepted';
        } else {
            $status = 'Rejected';
            $decision_date = Carbon::parse($data->updated_at)->format('d F Y');
        }

        $formFeedback = [
            'requester_name' => $user->fname . ' ' . $user->lname,
            'request_date' => $data->attend_date ?? Carbon::now()->format('d F Y'),
            'status' => $status,
            'decision_date' => $decision_date,
            'feedback' => $data->rejection_reason ?? null,
            'url' => route('profile.confirm'),
        ];

        // Redirect to the view with form details
        return view('review_dashboard', compact('formFeedback'));
    }


}
