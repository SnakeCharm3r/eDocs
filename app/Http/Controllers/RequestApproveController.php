<?php

namespace App\Http\Controllers;

use App\Models\Clearance_work_flow;
use Illuminate\Http\Request;
use App\Models\Workflow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User; // Import the User model
use Illuminate\Support\Facades\Schema;


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
        $isIT = $user->hasRole('it');
        
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
    ->where(function ($query) use ($isIT) {
        $query->where(function ($q) use ($isIT) {
            // For non-requisition forms: status 0 (Pending), 1 (Under Review)
            $q->whereNotNull('workflows.ict_request_resource_id')
              ->orWhereNotNull('workflows.hr_form')
              ->orWhereNotNull('workflows.bank_form')
              ->orWhereNotNull('workflows.heslb_form')
              ->orWhereNotNull('workflows.nhif_form')
              ->orWhereNotNull('workflows.change_request_id');
            
            // For ID forms: IT users can only see if HR has approved
            if ($isIT) {
                $q->orWhere(function ($idQuery) {
                    $idQuery->whereNotNull('workflows.id_form')
                        // Check if HR has approved this ID form
                        ->whereExists(function ($hrCheck) {
                            $hrCheck->select(DB::raw(1))
                                ->from('work_flow_histories as hr_history')
                                ->join('users as hr_users', 'hr_users.id', '=', 'hr_history.attended_by')
                                ->join('model_has_roles', 'model_has_roles.model_id', '=', 'hr_users.id')
                                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                                ->whereColumn('hr_history.work_flow_id', 'workflows.id')
                                ->where('hr_history.status', 1) // HR approved
                                ->where('hr_history.step_name', 'HR Approval')
                                ->where('roles.name', 'hr');
                        });
                });
            } else {
                // For non-IT users, show ID forms normally
                $q->orWhereNotNull('workflows.id_form');
            }
        })
        ->whereIn('work_flow_histories.status', [0, 1, -1]); // Pending, Under Review, or Rejected
    })
    ->select(
        'workflows.id',
        'requesters.id as workflow_user_id',
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
    public function index(Request $request)
    {
        $user = Auth::user();
        $canViewReport = $user->can('ict_acces_report')
            || $user->hasAnyRole(['super-admin', 'Super-Admin', 'admin', 'Admin']);
        // Force normal table mode for all users (no report-mode UI).
        $viewMode = 'requests';
        
        // Get form type filter for reports mode
        $formTypeFilter = $request->query('form_type', 'all');
        $roleFilter = $request->query('role', 'all');
        $statusFilter = $request->query('status', 'all');

        // Base condition: any valid form type exists
        $hasAnyForm = function ($q) {
            $q->whereNotNull('ict_request_resource_id')
              ->orWhereNotNull('hr_form')
              ->orWhereNotNull('bank_form')
              ->orWhereNotNull('heslb_form')
              ->orWhereNotNull('nhif_form')
              ->orWhereNotNull('id_form')
              ->orWhereNotNull('change_request_id');
        };

        // Dynamic years: distinct years that have at least one request
        $availableYears = DB::table('workflows')
            ->where($hasAnyForm)
            ->selectRaw('YEAR(created_at) as yr')
            ->distinct()
            ->orderBy('yr', 'desc')
            ->pluck('yr');

        // Default year: most recent year with data, or current year
        $yearFilter = $request->query('year', $availableYears->first() ?? date('Y'));

        // Dynamic months: distinct months in the selected year
        $monthNames = [
            1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
            7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December',
        ];
        $availableMonths = DB::table('workflows')
            ->where($hasAnyForm)
            ->whereYear('created_at', $yearFilter)
            ->selectRaw('MONTH(created_at) as mo')
            ->distinct()
            ->orderBy('mo', 'asc')
            ->pluck('mo');

        $monthFilter = $request->query('month', 'all');

        // Dynamic form types: only types that have at least one record
        $formTypeMap = [
            'ict'    => ['col' => 'ict_request_resource_id', 'label' => 'ICT Access Form'],
            'hr'     => ['col' => 'hr_form',                 'label' => 'HR Form'],
            'bank'   => ['col' => 'bank_form',               'label' => 'Bank Details Form'],
            'heslb'  => ['col' => 'heslb_form',              'label' => 'Loan Board Form (HESLB)'],
            'nhif'   => ['col' => 'nhif_form',               'label' => 'NHIF Registration'],
            'id'     => ['col' => 'id_form',                 'label' => 'ID Card Request'],
            'change' => ['col' => 'change_request_id',       'label' => 'Change Request'],
        ];
        $availableFormTypes = [];
        foreach ($formTypeMap as $key => $info) {
            if (DB::table('workflows')->whereNotNull($info['col'])->exists()) {
                $availableFormTypes[$key] = $info['label'];
            }
        }

        if ($viewMode === 'reports' && $canViewReport) {
            // Report mode: Show all forms (approved, pending, rejected) for all users
            $pending = Workflow::join('users as requesters', 'requesters.id', '=', 'workflows.user_id')
                ->leftJoin('work_flow_histories', function($join) {
                    $join->on('work_flow_histories.work_flow_id', '=', 'workflows.id')
                         ->whereRaw('work_flow_histories.id = (SELECT MAX(id) FROM work_flow_histories WHERE work_flow_id = workflows.id)');
                })
                ->where(function ($query) use ($formTypeFilter) {
                    if ($formTypeFilter === 'ict') {
                        $query->whereNotNull('workflows.ict_request_resource_id');
                    } elseif ($formTypeFilter === 'hr') {
                        $query->whereNotNull('workflows.hr_form');
                    } elseif ($formTypeFilter === 'bank') {
                        $query->whereNotNull('workflows.bank_form');
                    } elseif ($formTypeFilter === 'heslb') {
                        $query->whereNotNull('workflows.heslb_form');
                    } elseif ($formTypeFilter === 'nhif') {
                        $query->whereNotNull('workflows.nhif_form');
                    } elseif ($formTypeFilter === 'id') {
                        $query->whereNotNull('workflows.id_form');
                    } elseif ($formTypeFilter === 'change') {
                        $query->whereNotNull('workflows.change_request_id');
                    } else {
                        // All forms
                        $query->where(function($q) {
                            $q->whereNotNull('workflows.ict_request_resource_id')
                              ->orWhereNotNull('workflows.hr_form')
                              ->orWhereNotNull('workflows.bank_form')
                              ->orWhereNotNull('workflows.heslb_form')
                              ->orWhereNotNull('workflows.nhif_form')
                              ->orWhereNotNull('workflows.id_form')
                              ->orWhereNotNull('workflows.change_request_id');
                        });
                    }
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
                    'workflows.work_flow_completed',
                    'workflows.work_flow_status',
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
                    'work_flow_histories.who_approve',
                    'requesters.username as requester_name',
                    'requesters.fname as requester_fname',
                    'requesters.lname as requester_lname'
                )
                ->where(function ($query) use ($yearFilter, $monthFilter) {
                    if ($monthFilter !== 'all') {
                        $query->whereYear('workflows.created_at', $yearFilter)
                              ->whereMonth('workflows.created_at', $monthFilter);
                    } else {
                        $query->whereYear('workflows.created_at', $yearFilter);
                    }
                })
                ->where(function ($query) use ($statusFilter) {
                    if ($statusFilter === 'approved') {
                        $query->where('workflows.work_flow_completed', 1)
                              ->where('workflows.work_flow_status', 1);
                    } elseif ($statusFilter === 'pending') {
                        $query->where('workflows.work_flow_completed', 0);
                    } elseif ($statusFilter === 'rejected') {
                        $query->where(function($q) {
                            $q->where(function($wq) {
                                $wq->where('workflows.work_flow_completed', 1)
                                   ->where('workflows.work_flow_status', 2);
                            })->orWhereHas('histories', function($hq) {
                                $hq->where('status', 2)->orWhere('status', -1);
                            });
                        });
                    }
                    // 'all' shows everything, no filter needed
                })
                ->orderBy('workflows.created_at', 'desc')
                ->get();
            
            // Get approver statistics by role for the selected form type
            $approverStats = DB::table('work_flow_histories')
                ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                ->join('users', 'work_flow_histories.attended_by', '=', 'users.id')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('work_flow_histories.status', 1) // Approved
                ->where('roles.name', '!=', 'requester') // Exclude requester role
                ->where(function ($query) use ($formTypeFilter) {
                    if ($formTypeFilter === 'ict') {
                        $query->whereNotNull('workflows.ict_request_resource_id');
                    } elseif ($formTypeFilter === 'hr') {
                        $query->whereNotNull('workflows.hr_form');
                    } elseif ($formTypeFilter === 'bank') {
                        $query->whereNotNull('workflows.bank_form');
                    } elseif ($formTypeFilter === 'heslb') {
                        $query->whereNotNull('workflows.heslb_form');
                    } elseif ($formTypeFilter === 'nhif') {
                        $query->whereNotNull('workflows.nhif_form');
                    } elseif ($formTypeFilter === 'id') {
                        $query->whereNotNull('workflows.id_form');
                    } elseif ($formTypeFilter === 'change') {
                        $query->whereNotNull('workflows.change_request_id');
                    } else {
                        $query->where(function($q) {
                            $q->whereNotNull('workflows.ict_request_resource_id')
                              ->orWhereNotNull('workflows.hr_form')
                              ->orWhereNotNull('workflows.bank_form')
                              ->orWhereNotNull('workflows.heslb_form')
                              ->orWhereNotNull('workflows.nhif_form')
                              ->orWhereNotNull('workflows.id_form')
                              ->orWhereNotNull('workflows.change_request_id');
                        });
                    }
                })
                ->where(function ($query) use ($yearFilter, $monthFilter) {
                    if ($monthFilter !== 'all') {
                        $query->whereYear('workflows.created_at', $yearFilter)
                              ->whereMonth('workflows.created_at', $monthFilter);
                    } else {
                        $query->whereYear('workflows.created_at', $yearFilter);
                    }
                })
                ->select(
                    'roles.name as role_name',
                    'users.id as user_id',
                    'users.fname',
                    'users.lname',
                    'users.username',
                    DB::raw('COUNT(*) as approval_count')
                )
                ->groupBy('roles.name', 'users.id', 'users.fname', 'users.lname', 'users.username')
                ->orderBy('approval_count', 'desc')
                ->get()
                ->groupBy('role_name');
            
            // Get all available roles that have approved forms matching the current filters
            // First, try to get roles from approverStats (most accurate)
            $availableRoles = $approverStats->keys()->sort()->values();
            
            // If no roles found in approverStats, query directly from database
            if ($availableRoles->isEmpty()) {
                $availableRoles = DB::table('work_flow_histories')
                    ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                    ->join('users', 'work_flow_histories.attended_by', '=', 'users.id')
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('work_flow_histories.status', 1) // Only approved
                    ->where(function ($query) use ($formTypeFilter) {
                        if ($formTypeFilter === 'ict') {
                            $query->whereNotNull('workflows.ict_request_resource_id');
                        } elseif ($formTypeFilter === 'hr') {
                            $query->whereNotNull('workflows.hr_form');
                        } elseif ($formTypeFilter === 'bank') {
                            $query->whereNotNull('workflows.bank_form');
                        } elseif ($formTypeFilter === 'heslb') {
                            $query->whereNotNull('workflows.heslb_form');
                        } elseif ($formTypeFilter === 'nhif') {
                            $query->whereNotNull('workflows.nhif_form');
                        } elseif ($formTypeFilter === 'id') {
                            $query->whereNotNull('workflows.id_form');
                        } elseif ($formTypeFilter === 'change') {
                            $query->whereNotNull('workflows.change_request_id');
                        } else {
                            $query->where(function($q) {
                                $q->whereNotNull('workflows.ict_request_resource_id')
                                  ->orWhereNotNull('workflows.hr_form')
                                  ->orWhereNotNull('workflows.bank_form')
                                  ->orWhereNotNull('workflows.heslb_form')
                                  ->orWhereNotNull('workflows.nhif_form')
                                  ->orWhereNotNull('workflows.id_form')
                                  ->orWhereNotNull('workflows.change_request_id');
                            });
                        }
                    })
                    ->select('roles.name')
                    ->distinct()
                    ->orderBy('roles.name')
                    ->pluck('name');
            }
            
            $clear = collect(); // Empty for reports mode

            // Summary counts for stat cards
            $reportStats = [
                'total'    => $pending->count(),
                'approved' => $pending->filter(fn($r) => ($r->work_flow_completed ?? 0) == 1 && ($r->work_flow_status ?? 0) == 1)->count(),
                'pending'  => $pending->filter(fn($r) => ($r->work_flow_completed ?? 0) != 1)->count(),
                'rejected' => $pending->filter(fn($r) => ($r->work_flow_completed ?? 0) == 1 && ($r->work_flow_status ?? 0) == 2)->count(),
            ];
        } else {
            $reportStats = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
            if ($canViewReport) {
                // Requests mode + ict_acces_report: show all request forms using latest workflow history.
                $pending = Workflow::join('users as requesters', 'requesters.id', '=', 'workflows.user_id')
                    ->leftJoin('work_flow_histories as latest_history', function ($join) {
                        $join->on('latest_history.work_flow_id', '=', 'workflows.id')
                            ->whereRaw('latest_history.id = (SELECT MAX(id) FROM work_flow_histories WHERE work_flow_id = workflows.id)');
                    })
                    ->where(function ($q) {
                        $q->whereNotNull('workflows.ict_request_resource_id')
                            ->orWhereNotNull('workflows.hr_form')
                            ->orWhereNotNull('workflows.bank_form')
                            ->orWhereNotNull('workflows.heslb_form')
                            ->orWhereNotNull('workflows.nhif_form')
                            ->orWhereNotNull('workflows.id_form')
                            ->orWhereNotNull('workflows.change_request_id');
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
                        'workflows.work_flow_completed',
                        'workflows.work_flow_status',
                        'workflows.created_at',
                        'workflows.updated_at',
                        'latest_history.id as workflow_history_id',
                        'latest_history.status',
                        'latest_history.requisition_status',
                        'latest_history.remark',
                        'latest_history.comments',
                        'latest_history.rejection_reason',
                        'latest_history.attend_date',
                        'latest_history.decision_date',
                        'latest_history.who_approve',
                        'requesters.username as requester_name',
                        'requesters.fname as requester_fname',
                        'requesters.lname as requester_lname',
                        DB::raw('(SELECT d.name FROM divisions d INNER JOIN division_department dd ON dd.division_id = d.id WHERE dd.department_id = requesters.deptId LIMIT 1) as entity_name')
                    )
                    ->orderBy('workflows.created_at', 'desc')
                    ->get();
            } else {
                // Requests mode: Show only pending requests for current user
                $pending = Workflow::join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
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
                        ->whereIn('work_flow_histories.status', [0, 1, -1]);
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
                        'workflows.work_flow_completed',
                        'workflows.work_flow_status',
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
                        'work_flow_histories.who_approve',
                        'requesters.username as requester_name',
                        'requesters.fname as requester_fname',
                        'requesters.lname as requester_lname'
                    )
                    ->orderBy('workflows.created_at', 'desc')
                    ->get();
            }

            $clear = Clearance_work_flow::join('clearance_work_flow_histories', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
                ->join('users as requesters', 'requesters.id', '=', 'clearance_work_flows.user_id')
                ->where('clearance_work_flow_histories.attended_by', Auth::user()->id)
                ->select('clearance_work_flows.*', 'clearance_work_flow_histories.*', 'requesters.username as requester_name')
                ->orderBy('clearance_work_flows.created_at', 'desc')
                ->get();
        }

        $departments = \App\Models\Departments::orderBy('dept_name')->get();

        // Get workflow IDs where the current user has a pending action (status=0)
        // Only include IDs that are actually in the $pending collection
        $pendingWorkflowIds = $pending->pluck('id')->unique()->toArray();
        $myPendingWorkflowIds = DB::table('work_flow_histories')
            ->where('attended_by', $user->id)
            ->where('status', 0)
            ->whereIn('work_flow_id', $pendingWorkflowIds)
            ->pluck('work_flow_id')
            ->unique()
            ->toArray();

        if ($viewMode === 'reports' && $canViewReport) {
            return view("requestapprove.index", compact('pending', 'clear', 'viewMode', 'canViewReport', 'formTypeFilter', 'approverStats', 'yearFilter', 'monthFilter', 'roleFilter', 'availableRoles', 'statusFilter', 'reportStats', 'availableYears', 'availableMonths', 'availableFormTypes', 'monthNames', 'departments', 'myPendingWorkflowIds'));
        }

        // Initialize empty approverStats for non-reports mode
        $approverStats = collect();
        $availableRoles = collect();

        return view("requestapprove.index", compact('pending', 'clear', 'viewMode', 'canViewReport', 'formTypeFilter', 'approverStats', 'yearFilter', 'monthFilter', 'roleFilter', 'availableRoles', 'statusFilter', 'reportStats', 'availableYears', 'availableMonths', 'availableFormTypes', 'monthNames', 'departments', 'myPendingWorkflowIds'));
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
        $user = Auth::user();

        // Only admin/HR/IT can delete
        if (!$user->hasAnyRole(['super-admin', 'Super-Admin', 'admin', 'hr', 'it'])) {
            abort(403, 'You do not have permission to delete requests.');
        }

        $workflow = Workflow::findOrFail($id);

        // Check if this is an "unknown" / broken form (no valid form type linked)
        $hasValidForm = $workflow->ict_request_resource_id
            || $workflow->hr_form
            || $workflow->bank_form
            || $workflow->heslb_form
            || $workflow->nhif_form
            || $workflow->id_form
            || $workflow->change_request_id;

        if ($hasValidForm) {
            // For valid forms: super-admin can delete pending forms; nobody can delete completed ones
            if ($workflow->work_flow_completed == 1) {
                return redirect()->back()->with('error', 'Cannot delete a completed workflow.');
            }

            // Only super-admin can delete valid (non-broken) pending forms
            if (!$user->hasAnyRole(['super-admin', 'Super-Admin'])) {
                return redirect()->back()->with('error', 'Only super-admin can delete valid pending requests.');
            }
        }

        // Delete associated workflow histories first
        DB::table('work_flow_histories')->where('work_flow_id', $workflow->id)->delete();

        // Delete the workflow
        $workflow->delete();

        return redirect()->route('requestapprove.index')->with('success', 'Request workflow deleted successfully.');
    }

    /**
     * Export requests report as Excel.
     */
    public function exportExcel(Request $request)
    {
        // ── Filters ──────────────────────────────────────────────────────────
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $formType   = $request->input('form_type', 'all');
        $deptId     = $request->input('department_id', 'all');
        $statusF    = $request->input('export_status', 'all');

        $formTypeCols = [
            'ict'    => ['col' => 'ict_request_resource_id', 'label' => 'ICT Access Form'],
            'hr'     => ['col' => 'hr_form',                 'label' => 'HR Form'],
            'bank'   => ['col' => 'bank_form',               'label' => 'Bank Details Form'],
            'heslb'  => ['col' => 'heslb_form',              'label' => 'Loan Board Form (HESLB)'],
            'nhif'   => ['col' => 'nhif_form',               'label' => 'NHIF Registration'],
            'id'     => ['col' => 'id_form',                 'label' => 'ID Card Request'],
            'change' => ['col' => 'change_request_id',       'label' => 'Change Request'],
        ];

        // ── Build query ───────────────────────────────────────────────────────
        $query = DB::table('workflows')
            ->join('users', 'users.id', '=', 'workflows.user_id')
            ->leftJoin('departments', 'departments.id', '=', 'users.deptId')
            ->leftJoin(DB::raw('(SELECT work_flow_id,
                                        status        AS wf_latest_status,
                                        attend_date   AS wf_attend_date,
                                        decision_date AS wf_decision_date,
                                        who_approve   AS wf_who_approve
                                 FROM work_flow_histories
                                 WHERE id IN (SELECT MAX(id) FROM work_flow_histories GROUP BY work_flow_id)
                                ) AS lwfh'), 'lwfh.work_flow_id', '=', 'workflows.id')
            ->leftJoin('ict_access_resources as iar', 'iar.id', '=', 'workflows.ict_request_resource_id')
            ->leftJoin('change_requests as cr', 'cr.id', '=', 'workflows.change_request_id')
            ->leftJoin('bank_details', 'bank_details.id', '=', 'workflows.bank_form')
            ->where(function ($q) {
                $q->whereNotNull('workflows.ict_request_resource_id')
                  ->orWhereNotNull('workflows.hr_form')
                  ->orWhereNotNull('workflows.bank_form')
                  ->orWhereNotNull('workflows.heslb_form')
                  ->orWhereNotNull('workflows.nhif_form')
                  ->orWhereNotNull('workflows.id_form')
                  ->orWhereNotNull('workflows.change_request_id');
            });

        if ($dateFrom) $query->whereDate('workflows.created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('workflows.created_at', '<=', $dateTo);

        if ($formType !== 'all' && isset($formTypeCols[$formType])) {
            $query->whereNotNull('workflows.' . $formTypeCols[$formType]['col']);
        }

        if ($deptId !== 'all') {
            $query->where('users.deptId', $deptId);
        }

        // Status filter removed from export modal; always export all statuses

        $records = $query->select(
            'workflows.id',
            'workflows.created_at',
            'workflows.work_flow_completed',
            'workflows.work_flow_status',
            'workflows.ict_request_resource_id',
            'workflows.hr_form',
            'workflows.bank_form',
            'workflows.heslb_form',
            'workflows.nhif_form',
            'workflows.id_form',
            'workflows.change_request_id',
            'users.fname',
            'users.lname',
            'users.username',
            'departments.dept_name',
            'lwfh.wf_latest_status',
            'lwfh.wf_attend_date',
            'lwfh.wf_decision_date',
            'iar.action_required as ict_action',
            'cr.change_type as cr_change_type',
            'cr.description_of_change as cr_description',
            'bank_details.bank_name as bd_bank_name',
            'workflows.updated_at as workflow_updated_at',
            DB::raw('(SELECT d.name FROM divisions d INNER JOIN division_department dd ON dd.division_id = d.id WHERE dd.department_id = users.deptId LIMIT 1) as entity_name')
        )->orderBy('workflows.created_at', 'desc')->get();

        // ── Helper closures ───────────────────────────────────────────────────
        $getStatus = function ($r) {
            if (($r->work_flow_completed ?? 0) == 1) {
                $st = $r->work_flow_status ?? '';
                // status may be integer (1=approved,2=rejected) or string (e.g. "HR Form Approved")
                if ($st == 2 || (is_string($st) && stripos($st, 'reject') !== false)) {
                    return 'Rejected';
                }
                return 'Approved';
            }
            return 'Pending';
        };

        $getFormTypeLabel = function ($r) use ($formTypeCols) {
            foreach ($formTypeCols as $info) {
                if (!empty($r->{$info['col']})) return $info['label'];
            }
            return 'Unknown';
        };

        $getRequestDetail = function ($r) {
            if (!empty($r->ict_request_resource_id)) {
                $action = $r->ict_action ? ucwords(str_replace('_', ' ', $r->ict_action)) : 'System/Hardware Access';
                return 'ICT: ' . $action;
            }
            if (!empty($r->change_request_id)) {
                $type = $r->cr_change_type ? ucwords(str_replace(['_', '-'], ' ', $r->cr_change_type)) : 'Change';
                $desc = $r->cr_description ? ' — ' . \Illuminate\Support\Str::limit(strip_tags($r->cr_description), 70) : '';
                return $type . $desc;
            }
            if (!empty($r->bank_form)) {
                return 'Bank Account Registration' . ($r->bd_bank_name ? ' (' . $r->bd_bank_name . ')' : '');
            }
            if (!empty($r->hr_form))    return 'HR Documents Submission';
            if (!empty($r->heslb_form)) return 'HESLB Loan Registration';
            if (!empty($r->nhif_form))  return 'NHIF Registration';
            if (!empty($r->id_form))    return 'ID Card Request';
            return '—';
        };

        // ── Aggregate stats ───────────────────────────────────────────────────
        $total      = $records->count();
        $approved   = $records->filter(fn($r) => $getStatus($r) === 'Approved')->count();
        $pending    = $records->filter(fn($r) => $getStatus($r) === 'Pending')->count();
        $rejected   = $records->filter(fn($r) => $getStatus($r) === 'Rejected')->count();

        $byFormType = [];
        foreach ($formTypeCols as $key => $info) {
            if ($formType !== 'all' && $formType !== $key) continue;
            $sub = $records->filter(fn($r) => !empty($r->{$info['col']}));
            if ($sub->isEmpty()) continue;
            $byFormType[] = [
                'label'    => $info['label'],
                'total'    => $sub->count(),
                'pending'  => $sub->filter(fn($r) => $getStatus($r) === 'Pending')->count(),
                'approved' => $sub->filter(fn($r) => $getStatus($r) === 'Approved')->count(),
                'rejected' => $sub->filter(fn($r) => $getStatus($r) === 'Rejected')->count(),
            ];
        }

        $byDept = [];
        foreach ($records->groupBy('dept_name') as $deptName => $deptRecs) {
            $byDept[] = [
                'dept'     => $deptName ?: 'Unassigned',
                'total'    => $deptRecs->count(),
                'pending'  => $deptRecs->filter(fn($r) => $getStatus($r) === 'Pending')->count(),
                'approved' => $deptRecs->filter(fn($r) => $getStatus($r) === 'Approved')->count(),
                'rejected' => $deptRecs->filter(fn($r) => $getStatus($r) === 'Rejected')->count(),
            ];
        }
        usort($byDept, fn($a, $b) => $b['total'] - $a['total']);

        $byEntity = [];
        foreach ($records->groupBy('entity_name') as $entityName => $entityRecs) {
            $byEntity[] = [
                'entity' => $entityName ?: 'Unassigned',
                'total'  => $entityRecs->count(),
            ];
        }
        usort($byEntity, fn($a, $b) => $b['total'] - $a['total']);

        // ── Build spreadsheet ─────────────────────────────────────────────────
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('CCBRT System')
            ->setTitle('Requests Report')
            ->setSubject('CCBRT Document Requests Report');

        $GRN     = '007A33';
        $GRN_LT  = 'E8F5E9';
        $GRAY    = 'F5F5F5';
        $DGRAY   = '555555';
        $BORDER  = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
        $CENTER  = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
        $LEFT    = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT;
        $MIDDLE  = \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER;
        $SOLID   = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;

        $applyFill = function ($sheet, $range, $rgb) use ($SOLID) {
            $sheet->getStyle($range)->getFill()->setFillType($SOLID)->getStartColor()->setRGB($rgb);
        };
        $applyFont = function ($sheet, $range, $bold = false, $rgb = '000000', $size = 10) {
            $f = $sheet->getStyle($range)->getFont();
            $f->setBold($bold)->setSize($size);
            $f->getColor()->setRGB($rgb);
        };
        $applyBorder = function ($sheet, $range, $color = 'CCCCCC') use ($BORDER) {
            $sheet->getStyle($range)->getBorders()->getAllBorders()
                ->setBorderStyle($BORDER)->getColor()->setRGB($color);
        };
        $align = function ($sheet, $range, $h, $v = null) use ($MIDDLE) {
            $a = $sheet->getStyle($range)->getAlignment()->setHorizontal($h);
            if ($v) $a->setVertical($v);
        };

        // ═══════════════════════════════════════════════
        //  SHEET 1 — Summary
        // ═══════════════════════════════════════════════
        $s1 = $spreadsheet->getActiveSheet()->setTitle('Summary');

        // Title
        $s1->mergeCells('A1:H1');
        $s1->setCellValue('A1', 'CCBRT eDocs Request Report');
        $applyFill($s1, 'A1:H1', $GRN);
        $applyFont($s1, 'A1', true, 'FFFFFF', 15);
        $align($s1, 'A1', $CENTER, $MIDDLE);
        $s1->getRowDimension(1)->setRowHeight(38);

        // Filter info row
        $filterParts = [];
        if ($dateFrom || $dateTo) $filterParts[] = 'Period: ' . ($dateFrom ?: '—') . ' → ' . ($dateTo ?: '—');
        if ($formType !== 'all')  $filterParts[] = 'Form: ' . ($formTypeCols[$formType]['label'] ?? $formType);
        if ($deptId !== 'all') {
            $deptObj = \App\Models\Departments::find($deptId);
            $filterParts[] = 'Dept: ' . ($deptObj?->dept_name ?? $deptId);
        }
        if ($statusF !== 'all')  $filterParts[] = 'Status: ' . ucfirst($statusF);
        $filterParts[] = 'Generated: ' . now()->format('d M Y H:i');
        $s1->mergeCells('A2:H2');
        $s1->setCellValue('A2', implode('   |   ', $filterParts));
        $applyFill($s1, 'A2:H2', $GRAY);
        $applyFont($s1, 'A2', false, $DGRAY, 9);
        $align($s1, 'A2', $CENTER);
        $s1->getRowDimension(2)->setRowHeight(18);
        $s1->getRowDimension(3)->setRowHeight(8);

        // ─ Form type table ─
        $s1->mergeCells('A4:B4');
        $s1->setCellValue('A4', 'BREAKDOWN BY FORM TYPE');
        $applyFont($s1, 'A4', true, $GRN, 10);
        $s1->getRowDimension(4)->setRowHeight(18);

        foreach (['Form Type', 'Total'] as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s1->setCellValue($col . '5', $h);
        }
        $s1->getStyle('A5:B5')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s1->getRowDimension(5)->setRowHeight(20);

        $r = 6;
        $ftDataStart = $r;
        foreach ($byFormType as $idx => $ft) {
            $s1->fromArray([$ft['label'], $ft['total']], null, 'A' . $r);
            $r++;
        }
        if ($r > $ftDataStart) {
            $s1->getStyle("A{$ftDataStart}:B" . ($r - 1))->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER],
            ]);
            $s1->getStyle("A{$ftDataStart}:A" . ($r - 1))->getAlignment()->setHorizontal($LEFT);
        }
        // Totals row
        $s1->fromArray(['TOTAL', $total], null, 'A' . $r);
        $s1->getStyle("A{$r}:B{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s1->getStyle('A' . $r)->getAlignment()->setHorizontal($LEFT);
        $r += 2;

        // ─ Entity table ─
        $s1->mergeCells("A{$r}:B{$r}");
        $s1->setCellValue("A{$r}", 'BREAKDOWN BY ENTITY');
        $applyFont($s1, "A{$r}", true, $GRN, 10);
        $s1->getRowDimension($r)->setRowHeight(18);
        $r++;

        foreach (['Entity', 'Total'] as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s1->setCellValue($col . $r, $h);
        }
        $s1->getStyle("A{$r}:B{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s1->getRowDimension($r)->setRowHeight(20);
        $r++;

        $entDataStart = $r;
        foreach ($byEntity as $idx => $ent) {
            $s1->fromArray([$ent['entity'], $ent['total']], null, 'A' . $r);
            $r++;
        }
        if ($r > $entDataStart) {
            $s1->getStyle("A{$entDataStart}:B" . ($r - 1))->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER],
            ]);
            $s1->getStyle("A{$entDataStart}:A" . ($r - 1))->getAlignment()->setHorizontal($LEFT);
        }
        $r++; // spacer row

        // ─ Department table ─
        $s1->mergeCells("A{$r}:B{$r}");
        $s1->setCellValue("A{$r}", 'BREAKDOWN BY DEPARTMENT (Top Submitters)');
        $applyFont($s1, "A{$r}", true, $GRN, 10);
        $s1->getRowDimension($r)->setRowHeight(18);
        $r++;

        foreach (['Department', 'Total'] as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s1->setCellValue($col . $r, $h);
        }
        $s1->getStyle("A{$r}:B{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s1->getRowDimension($r)->setRowHeight(20);
        $r++;

        $deptDataStart = $r;
        foreach ($byDept as $idx => $dept) {
            $s1->fromArray([$dept['dept'], $dept['total']], null, 'A' . $r);
            $r++;
        }
        if ($r > $deptDataStart) {
            $s1->getStyle("A{$deptDataStart}:B" . ($r - 1))->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER],
            ]);
            $s1->getStyle("A{$deptDataStart}:A" . ($r - 1))->getAlignment()->setHorizontal($LEFT);
        }

        // Column widths — summary sheet
        foreach (['A' => 36, 'B' => 12] as $col => $w) {
            $s1->getColumnDimension($col)->setWidth($w);
        }
        $s1->freezePane('A3');

        // ═══════════════════════════════════════════════
        //  SHEET 2 — Detailed Records
        // ═══════════════════════════════════════════════
        $s2 = $spreadsheet->createSheet()->setTitle('Detailed Records');

        $s2->mergeCells('A1:F1');
        $s2->setCellValue('A1', 'CCBRT eDocs Request Report — Detailed Records');
        $applyFill($s2, 'A1:F1', $GRN);
        $applyFont($s2, 'A1', true, 'FFFFFF', 13);
        $align($s2, 'A1', $CENTER, $MIDDLE);
        $s2->getRowDimension(1)->setRowHeight(30);

        $s2->mergeCells('A2:F2');
        $s2->setCellValue('A2', implode('   |   ', $filterParts));
        $applyFill($s2, 'A2:F2', $GRAY);
        $applyFont($s2, 'A2', false, $DGRAY, 9);
        $align($s2, 'A2', $CENTER);
        $s2->getRowDimension(2)->setRowHeight(16);

        $cols2 = ['#', 'Requester Name', 'Department', 'Form Type', 'What Was Requested', 'Submitted Date'];
        foreach ($cols2 as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s2->setCellValue($col . '3', $h);
        }
        $s2->getStyle('A3:F3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s2->getRowDimension(3)->setRowHeight(20);

        $dr = 4;
        foreach ($records as $i => $rec) {
            $name   = trim(($rec->fname ?? '') . ' ' . ($rec->lname ?? '')) ?: ($rec->username ?? '—');
            $dept   = $rec->dept_name ?: 'Unassigned';
            $fType  = $getFormTypeLabel($rec);
            $detail = $getRequestDetail($rec);
            $subDt  = $rec->created_at ? \Carbon\Carbon::parse($rec->created_at)->format('d M Y') : '—';

            $s2->fromArray([$i + 1, $name, $dept, $fType, $detail, $subDt], null, 'A' . $dr);
            $dr++;
        }
        // Apply bulk styling once after the loop (much faster than per-row calls)
        $lastDr = $dr - 1;
        if ($lastDr >= 4) {
            $s2->getStyle("A4:F{$lastDr}")->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER, 'vertical' => $MIDDLE],
            ]);
            $s2->getStyle("B4:E{$lastDr}")->getAlignment()->setHorizontal($LEFT);
            $s2->getStyle("E4:E{$lastDr}")->getAlignment()->setWrapText(true);
        }

        foreach (['A' => 5, 'B' => 24, 'C' => 24, 'D' => 20, 'E' => 38, 'F' => 16] as $col => $w) {
            $s2->getColumnDimension($col)->setWidth($w);
        }
        $s2->freezePane('A4');
        $s2->setAutoFilter('A3:F3');

        // ── Output ────────────────────────────────────────────────────────────
        $spreadsheet->setActiveSheetIndex(0);
        $filename = 'CCBRT_Requests_Report_' . now()->format('Ymd_His') . '.xlsx';

        while (ob_get_level()) ob_end_clean();
        // Signal the browser that the download is ready (JS polls for this cookie)
        $token = $request->input('download_token', '');
        if ($token) {
            setcookie('download_token', $token, time() + 60, '/');
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}
