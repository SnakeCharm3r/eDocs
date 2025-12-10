<?php

namespace App\Http\Controllers;

use App\Models\Clearance_work_flow;
use Illuminate\Http\Request;
use App\Models\Workflow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User; // Import the User model


class RequestApproveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index1()
    {
        // Fetch ICT Access Requests and sort by created_at or updated_at in descending order
        // $pending = Workflow::join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
        //     ->join('users as requesters', 'requesters.id', '=', 'workflows.user_id')
        //     ->where('work_flow_histories.attended_by', Auth::user()->id)
        //     ->where('work_flow_histories.jd_status', null)
        //     ->where('work_flow_histories.locum_agreement_status', null)
        //     ->where('work_flow_histories.requisition_status', null)
        //     ->where('work_flow_histories.locum_request_status', null)
        //     ->where('work_flow_histories.on_call_request_status', null)

        //     ->select('workflows.*', 'work_flow_histories.*', 'requesters.username as requester_name')
        //     ->orderBy('workflows.created_at', 'desc')
        //     ->get();

        $user = Auth::user();
         $pending = Workflow::join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
    ->join('users as requesters', 'requesters.id', '=', 'workflows.user_id')
    ->where('work_flow_histories.attended_by', $user->id)
    // Get only the latest workflow history for each workflow
    ->whereIn('work_flow_histories.id', function ($query) {
        $query->selectRaw('MAX(id)')
            ->from('work_flow_histories')
            ->groupBy('work_flow_id');
    })
    // Show requests where current user needs to take action
    // Exclude requisitions, locum, and oncall requests as they have their own views
    ->where(function ($query) {
        $query->where(function ($q) {
            // For non-requisition forms: status 0 (Pending), 1 (Under Review)
            $q->whereNotNull('workflows.ict_request_resource_id')
              ->orWhereNotNull('workflows.hr_form')
              ->orWhereNotNull('workflows.bank_form')
              ->orWhereNotNull('workflows.heslb_form')
              ->orWhereNotNull('workflows.nhif_form')
              ->orWhereNotNull('workflows.id_form')
              ->orWhereNotNull('workflows.change_request_id');
        })
        ->whereIn('work_flow_histories.status', [0, 1, -1]); // Pending, Under Review, or Rejected
    })
    ->select(
        'workflows.id',
        'workflows.ict_request_resource_id',
        'workflows.hr_form',
        'workflows.bank_form',
        'workflows.heslb_form',
        'workflows.nhif_form',
        'workflows.id_form',
        'workflows.change_request_id',
        'workflows.created_at',
        'workflows.updated_at',
        'work_flow_histories.status',
        'work_flow_histories.remark',
        'work_flow_histories.rejection_reason',
        'work_flow_histories.attend_date',
        'work_flow_histories.decision_date',
        'requesters.username as requester_name'
    )
    ->orderBy('workflows.created_at', 'desc')
    ->get();
    //  dd($pending);
        // $hr_form =

        //  $clear = Workflow::join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
        //     ->join('users as requesters', 'requesters.id', '=', 'workflows.user_id')
        //     ->where('work_flow_histories.attended_by', $user->id)
        //     ->where('work_flow_histories.status', 0)
        //     ->select(
        //         'workflows.id',
        //         'workflows.created_at',
        //         'workflows.updated_at',
        //         'work_flow_histories.status',
        //         'work_flow_histories.remark',
        //         'work_flow_histories.attend_date',
        //         'work_flow_histories.id as requested_resource_id',
        //         'requesters.username as requester_name'
        //     )
        //     ->orderBy('workflows.created_at', 'desc')
        //     ->get();

        // Fetch Clearance Requests
        // For non-HR users: Only show pending approvals (status = 0) that need action
        // For HR users: Show all forms (pending and approved) including completed ones
        $userId = Auth::user()->id;
        $isHR = Auth::user()->hasRole('hr');
        
        $clearQuery = Clearance_work_flow::join('clearance_work_flow_histories', function($join) use ($userId, $isHR) {
                $join->on('clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
                     ->where('clearance_work_flow_histories.attended_by', '=', $userId);
                
                // For non-HR: Only pending approvals (status = 0)
                // For HR: Show all (pending and approved)
                if (!$isHR) {
                    $join->where('clearance_work_flow_histories.status', '=', 0)
                         ->where(function($q) use ($userId) {
                             // Don't show if already approved by this user
                             $q->whereNull('clearance_work_flow_histories.who_approve')
                               ->orWhere('clearance_work_flow_histories.who_approve', '!=', $userId);
                         });
                }
            })
            ->join('users as requesters', 'requesters.id', '=', 'clearance_work_flows.user_id')
            ->join('clearance_forms', 'clearance_forms.id', '=', 'clearance_work_flows.requested_resource_id')
            ->where('clearance_forms.status', '!=', 'rejected'); // Exclude rejected forms
        
        // For non-HR: Exclude completed workflows (forms should move to next level after approval)
        // For HR: Show all including completed
        if (!$isHR) {
            $clearQuery->where(function($query) {
                $query->where('clearance_work_flows.work_flow_completed', '!=', 1)
                      ->orWhereNull('clearance_work_flows.work_flow_completed');
            });
        }
        
        // Get only the current pending step for each workflow to avoid duplicates
        // For HR, get the latest history record (pending or approved)
        $clear = $clearQuery->whereIn('clearance_work_flow_histories.id', function($subquery) use ($userId, $isHR) {
                if ($isHR) {
                    // HR: Get the latest history record (any status) for each workflow
                    $subquery->selectRaw('MAX(clearance_work_flow_histories.id)')
                        ->from('clearance_work_flow_histories')
                        ->where('clearance_work_flow_histories.attended_by', $userId)
                        ->groupBy('clearance_work_flow_histories.work_flow_id');
                } else {
                    // Non-HR: Only get the latest pending record for each workflow
                    $subquery->selectRaw('MAX(clearance_work_flow_histories.id)')
                        ->from('clearance_work_flow_histories')
                        ->where('clearance_work_flow_histories.status', 0)
                        ->where('clearance_work_flow_histories.attended_by', $userId)
                        ->where(function($q) use ($userId) {
                            $q->whereNull('clearance_work_flow_histories.who_approve')
                              ->orWhere('clearance_work_flow_histories.who_approve', '!=', $userId);
                        })
                        ->groupBy('clearance_work_flow_histories.work_flow_id');
                }
            })
            ->select('clearance_work_flows.*', 'clearance_work_flow_histories.*', 'requesters.username as requester_name', 'requesters.fname', 'requesters.lname')
            ->orderBy('clearance_work_flows.created_at', 'desc')
            ->distinct()
            ->get();

        // Count pending clearance requests for badge (not used in this view anymore, but kept for compatibility)
        $pendingClearanceCount = $clear->where('status', 0)->count();

        // Pass both datasets to the view
        // Note: $clear is kept for backward compatibility but clearance forms are now shown separately
        // in the clearance.index view, not in General Requests
        return view("requestapprove.index", compact("pending", "clear", "pendingClearanceCount"));
    }




    public function showICTForm($id)
    {
        $ictRequest = Workflow::findOrFail($id);
        // Return a view with the ICT request details
        return view('ict_resource_form', compact('ictRequest'));
    }

    public function showClearanceForm($id)
    {
        $clearanceRequest = Clearance_work_flow::findOrFail($id);
        // Return a view with the Clearance request details
        return view('requestapprove.show_clearance_form', compact('clearanceRequest'));
    }

    //hii haitumiki ipo for ref
    public function index()
    {
        $user = Auth::user();
        $pending = Workflow::join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->join('users as requesters', 'requesters.id', '=', 'workflows.user_id')
            ->where('work_flow_histories.attended_by', $user->id)
            // Show requests where current user needs to take action
            // Exclude requisitions, locum, and oncall requests as they have their own views
            ->where(function ($query) {
                $query->where(function ($q) {
                    // For non-requisition forms: status 0 (Pending), 1 (Under Review)
                    $q->whereNotNull('workflows.ict_request_resource_id')
                      ->orWhereNotNull('workflows.hr_form')
                      ->orWhereNotNull('workflows.bank_form')
                      ->orWhereNotNull('workflows.heslb_form')
                      ->orWhereNotNull('workflows.nhif_form')
                      ->orWhereNotNull('workflows.id_form')
                      ->orWhereNotNull('workflows.change_request_id');
                })
                ->whereIn('work_flow_histories.status', [0, 1]); // Pending or Under Review
            })
            ->select(
                'workflows.id',
                'workflows.ict_request_resource_id',
                'workflows.hr_form',
                'workflows.bank_form',
                'workflows.heslb_form',
                'workflows.nhif_form',
                'workflows.id_form',
                'workflows.change_request_id',
                'workflows.requisition_id',
                'workflows.created_at',
                'workflows.updated_at',
                'work_flow_histories.id as workflow_history_id',
                'work_flow_histories.status',
                'work_flow_histories.requisition_status',
                'work_flow_histories.remark',
                'work_flow_histories.comments',
                'work_flow_histories.rejection_reason',
                'work_flow_histories.attend_date',
                'work_flow_histories.decision_date',
                'requesters.username as requester_name'
            )
            ->orderBy('workflows.created_at', 'desc')
            ->get();

        $clear = Clearance_work_flow::join('clearance_work_flow_histories', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
            ->join('users as requesters', 'requesters.id', '=', 'clearance_work_flows.user_id')
            ->where('clearance_work_flow_histories.attended_by', Auth::user()->id)
            ->select('clearance_work_flows.*', 'clearance_work_flow_histories.*', 'requesters.username as requester_name')
            ->orderBy('clearance_work_flows.created_at', 'desc') // Sort by created_at date
            ->get();

        return view("requestapprove.index", compact('pending','clear'));
    }

    public function getClearance()
    {
        $clear = Clearance_work_flow::join('clearance_work_flow_histories', 'clearance_work_flow_histories.work_flow_id')
            ->join('users', 'users as requesters', 'requesters.id', '=', 'clearance_work_flows.user_id')
            ->where('clearance_work_flow_histories.attended_by', Auth::user()->id)
            ->select('clearance_work_flows.*', 'clearance_work_flow_histories.*', 'requesters.username as requester_name')
            ->get();
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
