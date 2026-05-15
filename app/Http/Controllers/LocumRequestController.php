<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Hec;
use App\Models\Unit;
use App\Models\User;
use App\Models\Platform;
use App\Models\Workflow;
use App\Models\Departments;
use Illuminate\Support\Str;
use App\Models\LocumRequest;
use App\Models\ShiftSetting;
use Illuminate\Http\Request;
use App\Models\LocumAgreement;
use App\Models\LocumCarryover;
use App\Mail\LocumStatusUpdate;
use App\Models\WorkFlowHistory;
use Illuminate\Validation\Rule;
use App\Mail\LocumApprovalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exports\LocumRequestsExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Schema;
use RealRashid\SweetAlert\Facades\Alert;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\ApprovedLocumRequestsExport;
use Illuminate\Validation\ValidationException;


class       LocumRequestController extends Controller
{

    public function index(\Illuminate\Http\Request $request)
    {
        $user = \Auth::user();

        // Agreement (for header gates)
        // Get the most recent HR-approved agreement (status = 2)
        // Order by updated_at DESC to get the most recently approved agreement by HR
        // If there's a newer agreement that's not HR-approved yet, use the previous HR-approved one
        $today = \Carbon\Carbon::today();
        $agreement = \App\Models\LocumAgreement::where('user_id', $user->id)
            ->where('status', 2) // Only currently active HR-approved agreements
            ->where(function($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('updated_at') // Most recently approved by HR
            ->orderByDesc('created_at') // Fallback to creation date
            ->first();

        // If no valid HR-approved agreement, get the most recent approved or expired one for display
        if (!$agreement) {
            $agreement = \App\Models\LocumAgreement::where('user_id', $user->id)
                ->whereIn('status', [2, 5]) // Approved or Expired
                ->orderByDesc('updated_at') // Most recently approved by HR
                ->orderByDesc('created_at') // Fallback to creation date
                ->first();
        }

        // If still no agreement, check for pending agreements (status 0 = pending LM, status 1 = pending HR)
        // so the view doesn't show "No active locum agreement" when one is in progress
        if (!$agreement) {
            $agreement = \App\Models\LocumAgreement::where('user_id', $user->id)
                ->whereIn('status', [0, 1]) // Pending agreements
                ->whereNull('rejection_status')
                ->orderByDesc('created_at')
                ->first();
        }

        // User's own requests with workflow + histories + agreement (for applicable year)
        $requests = \App\Models\LocumRequest::query()
            ->where('user_id', $user->id)
            ->with([
                'user:id,username,fname,lname,deptId',
                'user.department:id,dept_name',
                'locumAgreement:id,start_date,end_date,created_at',
                'workflow:id,user_id,locum_request_id,work_flow_completed,work_flow_status',
                'workflow.histories' => function ($q) {
                    $q->orderByDesc('id');
                },
            ])
            ->orderByDesc('created_at')
            ->get();

        // Lookup maps for worked-days rendering
        $shiftMap    = \App\Models\ShiftSetting::pluck('name', 'id')->toArray();
        $platformMap = \App\Models\Platform::pluck('name', 'id')->toArray();
        $unitMap     = \App\Models\Unit::pluck('name', 'id')->toArray();

        // Flatten rows for the view + pill counts
        $rows = [];
        $pillCounts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];

        foreach ($requests as $r) {
            $monthLabel = ($r->locum_month ?: '-') . ' / ' . ($r->locum_year ?: \Carbon\Carbon::parse($r->created_at)->format('Y'));

            $wf   = $r->workflow;
            $hist = $wf ? $wf->histories : collect();
            $last = $hist->first();

            $label = 'Pending';
            $class = 'bg-warning';
            $tip   = null;

            // If workflow is completed and marked as rejected, always show Rejected
            if ($wf && (int) $wf->work_flow_completed === 1 && (int) $wf->work_flow_status === 2) {
                // Prefer an explicit rejected history row if available
                $rejectedHistory = $hist->firstWhere('status', 2) ?: $last;
                $label = 'Rejected';
                $class = 'bg-danger';
                if ($rejectedHistory) {
                    $tip = trim((string) ($rejectedHistory->rejection_reason ?? '')) ?: null;
                }
            }
            // Otherwise, check if there's a pending step FIRST (after resubmission,
            // there may be pending steps even if old rejected entries exist)
            elseif ($pendingHistory = $hist->firstWhere('status', 0)) {
                // Determine pending label based on step name
                $stepName = $pendingHistory->step_name ?? '';
                $label = match ($stepName) {
                    'Incharge Approval' => 'Pending In-Charge Approval',
                    'Platform Manager Approval' => 'Pending Platform Manager Approval',
                    'Line Manager Approval' => 'Pending Line Manager Approval',
                    'HEC Approval' => 'Pending HEC Approval',
                    'HR Approval' => 'Pending HR Approval',
                    default => 'Pending',
                };
                $class = 'bg-warning';
            } elseif ($wf && (int)$wf->work_flow_completed === 1 && (int)$wf->work_flow_status === 1) {
                // Workflow is completed AND approved (status === 1 means approved)
                $label = 'Approved';
                $class = 'bg-success';
            } elseif ($last && (string)$last->status === '1') {
                // Last step was approved but workflow not completed - still pending
                $stepName = $last->step_name ?? '';
                $label = match ($stepName) {
                    'HR Approval' => 'Pending HR Approval',
                    default => 'Pending',
                };
                $class = 'bg-warning';
            } else {
                // Check for rejection (only if no pending steps and not already handled above)
                if ($last) {
                    $code = (int) ($last->locum_request_status ?? 0);
                    if ((string) $last->status === '2' || $code === 5) {
                        $label = 'Rejected';
                        $class = 'bg-danger';
                        $tip   = trim((string) ($last->rejection_reason ?? '')) ?: null;
                    }
                }
            }

            $workedDays = is_string($r->worked_days)
                ? (json_decode($r->worked_days, true) ?: [])
                : (is_array($r->worked_days) ? $r->worked_days : []);

            // Basic employee/department
            $employee = 'N/A';
            $dept     = 'N/A';
            if ($r->user) {
                $employee = $r->user->username ?: trim(($r->user->fname ?? '') . ' ' . ($r->user->lname ?? '')) ?: 'N/A';
                $dept     = $r->user->department ? ($r->user->department->dept_name ?: 'N/A') : 'N/A';
            }

            // Determine a clean rejection timestamp (for edit window)
            $rejectedAt = null;
            if ($label === 'Rejected') {
                // Prefer explicit rejected history row
                $rejectedHistory = $hist->firstWhere('status', 2);
                if ($rejectedHistory && ($rejectedHistory->attend_date ?: $rejectedHistory->updated_at ?: $rejectedHistory->created_at)) {
                    $dt = $rejectedHistory->attend_date ?: $rejectedHistory->updated_at ?: $rejectedHistory->created_at;
                    $rejectedAt = \Carbon\Carbon::parse($dt)->format('Y-m-d H:i:s');
                } elseif ($last && ($last->attend_date ?: $last->updated_at ?: $last->created_at)) {
                    $dt = $last->attend_date ?: $last->updated_at ?: $last->created_at;
                    $rejectedAt = \Carbon\Carbon::parse($dt)->format('Y-m-d H:i:s');
                }
            }

            // Applicable year and period from the linked locum agreement
            $applicableYear = null;
            $agreement_period = '—';
            if ($r->locumAgreement) {
                $ag = $r->locumAgreement;
                $start = $ag->start_date ? \Carbon\Carbon::parse($ag->start_date) : \Carbon\Carbon::parse($ag->created_at);
                $applicableYear = (int) $start->format('Y');
                $end = $ag->end_date ? \Carbon\Carbon::parse($ag->end_date) : $start->copy()->endOfYear();
                $agreement_period = $start->format('d M Y') . ' – ' . $end->format('d M Y');
            }

            $rows[] = [
                'id'              => $r->id,
                'month_label'     => $monthLabel,
                'applicable_year' => $applicableYear,
                'agreement_period' => $agreement_period,
                'days'            => (int)$r->number_of_days,
                'amount_fmt'      => number_format((float)$r->total_amount_payable, 2),
                'status_label'    => $label,
                'status_class'    => $class,
                'status_tip'      => $tip,
                'created_at'      => \Carbon\Carbon::parse($r->created_at)->format('d M Y H:i'),
                'created_iso'     => \Carbon\Carbon::parse($r->created_at)->format('Y-m-d H:i:s'),
                'worked_days'     => $workedDays,
                // rejection modal bits
                'employee'        => $employee,
                'dept'            => $dept,
                'month_text'      => $r->locum_month ? ($r->locum_month . ' ' . ($r->locum_year ?: \Carbon\Carbon::parse($r->created_at)->format('Y'))) : \Carbon\Carbon::parse($r->created_at)->format('F Y'),
                'last_step_name'  => $last ? ($last->step_name ?: '—') : '—',
                'rejection_reason' => $last ? ($last->rejection_reason ?: '') : '',
                'acted_by'        => ($last && $last->user)
                    ? ($last->user->username ?: trim(($last->user->fname ?? '') . ' ' . ($last->user->lname ?? '')))
                    : '—',
                'acted_at'        => ($last && ($last->updated_at ?: $last->created_at))
                    ? \Carbon\Carbon::parse($last->updated_at ?: $last->created_at)->format('d M Y H:i')
                    : '—',
                'rejected_at'     => $rejectedAt,
            ];

            $pillCounts[$label] = ($pillCounts[$label] ?? 0) + 1;
        }

        // Approver queue (if user has approver role)
        $pendingGroups = collect();
        if ($user->hasAnyRole(['incharge', 'in-charge', 'line-manager', 'hr'])) {
            $approverItems = \App\Models\LocumRequest::query()
                ->whereHas('workflow', function ($q) use ($user) {
                    $q->where('work_flow_completed', 0)
                        ->whereHas('histories', function ($h) use ($user) {
                            $h->where('attended_by', $user->id)->where('status', 0);
                        });
                })
                ->with([
                    'user:id,username,fname,lname,deptId',
                    'user.department:id,dept_name',
                    'workflow:id,locum_request_id,work_flow_completed',
                    'workflow.histories' => function ($h) {
                        $h->orderByDesc('id');
                    },
                ])
                ->get();

            $flat = [];
            foreach ($approverItems as $r) {
                $wf   = $r->workflow;
                $hist = $wf ? $wf->histories : collect();
                $last = $hist->first();
                $label = 'Pending';
                $badge = 'bg-warning';
                if ($last) {
                    $code = (int)($last->locum_request_status ?? 0);
                    if ((string)$last->status === '2' || $code === 5) {
                        $label = 'Rejected';
                        $badge = 'bg-danger';
                    }
                }
                if ($wf && (int)$wf->work_flow_completed === 1) {
                    $label = 'Approved';
                    $badge = 'bg-success';
                }

                $employee = $r->user ? ($r->user->username ?: trim(($r->user->fname ?? '') . ' ' . ($r->user->lname ?? ''))) : 'N/A';
                $dept     = $r->user && $r->user->department ? ($r->user->department->dept_name ?: 'N/A') : 'N/A';

                $flat[] = [
                    'id'        => $r->id,
                    'month'     => ($r->locum_month ?: '-') . ' ' . ($r->locum_year ?: \Carbon\Carbon::parse($r->created_at)->year),
                    'employee'  => $employee,
                    'dept'      => $dept,
                    'days'      => (int)$r->number_of_days,
                    'amount_fmt' => number_format((float)$r->total_amount_payable, 2),
                    'status'    => $label,
                    'badge'     => $badge,
                ];
            }

            $pendingGroups = collect($flat)->groupBy('month')->sortKeys();
        }

        // Get year filter from request (default: 'all' to show all years)
        $filterYear = $request->query('hr_year', 'all');

        // First, get ALL HR-approved requests to determine available years
        $allHrApprovedQuery = \App\Models\LocumRequest::query()
            ->where('user_id', $user->id)
            ->whereHas('workflow', function ($q) {
                $q->where('work_flow_completed', 1)
                  ->where('work_flow_status', 1);
            })
            ->whereHas('workflow.histories', function ($q) {
                $q->where('step_name', 'HR Approval')
                  ->where('status', 1);
            });

        $allHrApprovedRequests = $allHrApprovedQuery->get();

        // Get all available years from ALL HR-approved requests
        $availableYears = [];
        foreach ($allHrApprovedRequests as $r) {
            $year = null;
            if (\Schema::hasColumn('locum_requests', 'locum_year') && $r->locum_year) {
                $year = (int)$r->locum_year;
            } else {
                $year = (int)\Carbon\Carbon::parse($r->created_at)->year;
            }
            if ($year && !in_array($year, $availableYears)) {
                $availableYears[] = $year;
            }
        }
        rsort($availableYears); // Sort descending (newest first)

        // Now fetch filtered HR-approved requests for display
        $hrApprovedRequestsQuery = \App\Models\LocumRequest::query()
            ->where('user_id', $user->id)
            ->whereHas('workflow', function ($q) {
                $q->where('work_flow_completed', 1)
                  ->where('work_flow_status', 1);
            })
            ->whereHas('workflow.histories', function ($q) {
                $q->where('step_name', 'HR Approval')
                  ->where('status', 1);
            })
            ->with([
                'user:id,username,fname,lname,deptId',
                'user.department:id,dept_name',
                'workflow:id,user_id,locum_request_id,work_flow_completed,work_flow_status',
                'workflow.histories' => function ($q) {
                    $q->where('step_name', 'HR Approval')
                      ->where('status', 1)
                      ->orderByDesc('id')
                      ->limit(1);
                },
            ]);

        // Filter by locum year if provided and not 'all'
        if ($filterYear && $filterYear !== 'all' && is_numeric($filterYear)) {
            $hrApprovedRequestsQuery->where(function ($q) use ($filterYear) {
                // Check if locum_year column exists and filter by it
                if (\Schema::hasColumn('locum_requests', 'locum_year')) {
                    $q->where('locum_year', (int)$filterYear);
                } else {
                    // Fallback: filter by created_at year if locum_year doesn't exist
                    $q->whereYear('created_at', (int)$filterYear);
                }
            });
        }

        $hrApprovedRequests = $hrApprovedRequestsQuery->orderByDesc('created_at')->get();

        // Format HR-approved requests for display
        $hrApprovedRows = [];
        foreach ($hrApprovedRequests as $r) {
            $monthLabel = ($r->locum_month ?: '-') . ' / ' . ($r->locum_year ?: \Carbon\Carbon::parse($r->created_at)->format('Y'));

            $workedDays = is_string($r->worked_days)
                ? (json_decode($r->worked_days, true) ?: [])
                : (is_array($r->worked_days) ? $r->worked_days : []);

            // Basic employee/department
            $employee = 'N/A';
            $dept     = 'N/A';
            if ($r->user) {
                $employee = $r->user->username ?: trim(($r->user->fname ?? '') . ' ' . ($r->user->lname ?? '')) ?: 'N/A';
                $dept     = $r->user->department ? ($r->user->department->dept_name ?: 'N/A') : 'N/A';
            }

            // Get HR approval date
            $hrApprovedAt = '—';
            $wf = $r->workflow;
            $hist = $wf ? $wf->histories : collect();
            $hrHistory = $hist->first();
            if ($hrHistory && ($hrHistory->attend_date ?: $hrHistory->updated_at ?: $hrHistory->created_at)) {
                $hrApprovedAt = \Carbon\Carbon::parse($hrHistory->attend_date ?: $hrHistory->updated_at ?: $hrHistory->created_at)->format('d M Y H:i');
            }

            $hrApprovedRows[] = [
                'id'              => $r->id,
                'month_label'     => $monthLabel,
                'days'            => (int)$r->number_of_days,
                'amount_fmt'      => number_format((float)$r->total_amount_payable, 2),
                'status_label'    => 'HR Approved',
                'status_class'    => 'bg-success',
                'created_at'      => \Carbon\Carbon::parse($r->created_at)->format('d M Y H:i'),
                'created_iso'     => \Carbon\Carbon::parse($r->created_at)->format('Y-m-d H:i:s'),
                'worked_days'     => $workedDays,
                'hr_approved_at'  => $hrApprovedAt,
                'employee'        => $employee,
                'dept'            => $dept,
            ];
        }

        // Gate to allow "Claim Locum"
        // User must have at least ONE valid (non-expired) approved agreement (status = 2)
        // OR: expired but within admin-set "use until" grace date
        $today = \Carbon\Carbon::today();
        $hasValidApprovedAgreement = \App\Models\LocumAgreement::where('user_id', $user->id)
            ->where('status', 2) // Approved
            ->where(function($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->exists();

        // Admin setting: date until when users may continue using expired agreement for new claims (same as locum-rates "Valid for claiming (until ...)")
        $expiredAgreementUseUntilRaw = trim((string) (\App\Http\Controllers\SettingsController::getSetting('locum_expired_agreement_use_until', '') ?? ''));
        $expiredAgreementUseUntilDate = null;
        $expiredButUseUntil = false;
        $validForClaimingUntilFormatted = null; // display as "Valid for claiming (until [date])" like on locum-rates
        if ($expiredAgreementUseUntilRaw !== '') {
            try {
                $useUntil = \Carbon\Carbon::parse($expiredAgreementUseUntilRaw)->endOfDay();
                if ($today->lte($useUntil)) {
                    $validForClaimingUntilFormatted = \Carbon\Carbon::parse($expiredAgreementUseUntilRaw)->format('d M Y');
                    if ($agreement && in_array((int)($agreement->status ?? 0), [2, 5])) {
                        $agreementEnd = $agreement->end_date
                            ? \Carbon\Carbon::parse($agreement->end_date)->endOfDay()
                            : null;
                        if ($agreementEnd && $today->gt($agreementEnd)) {
                            $expiredButUseUntil = true;
                            $expiredAgreementUseUntilDate = $validForClaimingUntilFormatted;
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore invalid date
            }
        }
        // Fallback: if no setting, use max end_date from HR-approved agreements (e.g. after "Expire All" on another server)
        if ($validForClaimingUntilFormatted === null) {
            $maxEnd = \App\Models\LocumAgreement::where('has_contract', true)
                ->whereNotNull('end_date')
                ->where('end_date', '>=', $today)
                ->max('end_date');
            if ($maxEnd) {
                try {
                    $d = \Carbon\Carbon::parse($maxEnd)->endOfDay();
                    if ($today->lte($d)) {
                        $validForClaimingUntilFormatted = $d->format('d M Y');
                        if ($agreement && in_array((int)($agreement->status ?? 0), [2, 5])) {
                            $agreementEnd = $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->endOfDay() : null;
                            if ($agreementEnd && $today->gt($agreementEnd)) {
                                $expiredButUseUntil = true;
                                $expiredAgreementUseUntilDate = $validForClaimingUntilFormatted;
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
        }

        // User status check: allow if active, empty, null, or not explicitly set to inactive
        $userStatus = strtolower(trim((string)($user->status ?? '')));
        $isUserActive = empty($userStatus)
            || $userStatus === 'active'
            || $userStatus === null
            || !in_array($userStatus, ['inactive', 'suspended', 'disabled', 'deactivated']);

        $canCreate = ($hasValidApprovedAgreement || $expiredButUseUntil) && $isUserActive;

        // Check if all approved agreements are expired (to show message about creating new agreement)
        $allApprovedExpired = false;
        if (!$hasValidApprovedAgreement && !$expiredButUseUntil) {
            $approvedCount = \App\Models\LocumAgreement::where('user_id', $user->id)
                ->whereIn('status', [2, 5])
                ->count();
            if ($approvedCount > 0) {
                // User has approved agreements but all are expired
                $allApprovedExpired = true;
            }
        }

        // Near-expiry: valid agreement with end_date within 10 days (user can keep using current rate until expiry)
        $agreementNearExpiry = false;
        $agreementExpiresInDays = null;
        $agreementEndDateFormatted = null;
        if ($hasValidApprovedAgreement && $agreement && $agreement->end_date) {
            $endDate = \Carbon\Carbon::parse($agreement->end_date)->startOfDay();
            $todayStart = \Carbon\Carbon::today()->startOfDay();
            if ($endDate->gte($todayStart)) {
                $daysRemaining = (int) abs($todayStart->diffInDays($endDate, false));
                if ($daysRemaining <= 10 && $daysRemaining >= 0) {
                    $agreementNearExpiry = true;
                    $agreementExpiresInDays = $daysRemaining;
                    $agreementEndDateFormatted = $endDate->format('d M Y');
                }
            }
        }

        // Locum agreement status label for header (Agreement button). When admin set "valid until" date, use that instead of "Expiring soon" / "Active until [end_date]"
        $locumAgreementStatus = null;
        if ($agreement) {
            $todayStart = \Carbon\Carbon::today()->startOfDay();
            $isApproved = in_array((int) ($agreement->status ?? 0), [2, 5]);
            $endDate = $agreement->end_date
                ? \Carbon\Carbon::parse($agreement->end_date)->startOfDay()
                : \Carbon\Carbon::parse($agreement->created_at)->addYear()->startOfDay();
            
            // Calculate days until expiration
            $daysUntilExpiration = $todayStart->diffInDays($endDate, false);
            $isNearExpiration = $daysUntilExpiration <= 60 && $daysUntilExpiration > 0;
            
            if (!$isApproved) {
                $locumAgreementStatus = 'Pending approval';
            } elseif ($endDate->lt($todayStart)) {
                // Expired agreements should only show "Expired" - no validity information
                $locumAgreementStatus = 'Expired';
            } elseif ($isApproved && $validForClaimingUntilFormatted && $isNearExpiration) {
                // Only show "Valid for claiming" when within 60 days of expiration and not expired
                $locumAgreementStatus = 'Valid for claiming (until ' . $validForClaimingUntilFormatted . ')';
            } elseif ($isNearExpiration) {
                $locumAgreementStatus = 'Expiring soon';
            } else {
                $locumAgreementStatus = 'Active until ' . $endDate->format('d M Y');
            }
        }

        // Use a plain URL template to avoid route() with placeholders
        // Ensure you have: Route::get('/locum-requests/{id}/status', ...)
        $detailsUrlTemplate = url('/locum-requests/__ID__/status');

        return view('locum_requests.index_compact', [
            'agreement'                  => $agreement,
            'canCreate'                  => $canCreate,
            'rows'                      => $rows,
            'pillCounts'                => $pillCounts,
            'pendingGroups'             => $pendingGroups,
            'shiftMap'                  => $shiftMap,
            'platformMap'               => $platformMap,
            'unitMap'                   => $unitMap,
            'detailsUrlTemplate'        => $detailsUrlTemplate,
            'hrApprovedRows'            => $hrApprovedRows,
            'filterYear'                => $filterYear,
            'availableYears'            => $availableYears,
            'agreementNearExpiry'       => $agreementNearExpiry,
            'agreementExpiresInDays'    => $agreementExpiresInDays,
            'agreementEndDateFormatted' => $agreementEndDateFormatted,
            'locumAgreementStatus'      => $locumAgreementStatus,
            'allApprovedExpired'        => $allApprovedExpired,
            'expiredButUseUntil'        => $expiredButUseUntil,
            'expiredAgreementUseUntilDate' => $expiredAgreementUseUntilDate,
            'validForClaimingUntilFormatted' => $validForClaimingUntilFormatted,
        ]);
    }

    public function view(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {
            $query = LocumRequest::whereHas('workflow', function ($query) use ($user) {
                $query->where('work_flow_completed', 0)
                    ->whereHas('histories', function ($q) use ($user) {
                        $q->where('attended_by', $user->id)
                            ->where('status', 0)
                            ->whereNotNull('locum_request_status');
                    });
            })
                ->with([
                    'user' => function ($query) {
                        $query->with('department');
                    },
                    'locumAgreement',
                    'workflow' => function ($q) {
                        $q->select('id', 'user_id', 'locum_request_id', 'work_flow_completed', 'work_flow_status');
                    },
                    // ⬇️ include sender/assignee/approver on each history row
                    'workflow.histories' => function ($q) {
                        $q->whereNotNull('locum_request_status')
                            ->with(['forwardedBy', 'attendedBy', 'approver'])
                            ->latest();
                    }
                ]);

            // Restrict line managers to their department
            if ($user->hasRole('line-manager') && !$user->hasRole('hr')) {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('deptId', $user->deptId);
                });
            }

            // Filters (DataTables)
            if ($request->has('month') && $request->month) {
                $query->where('locum_month', $request->month);
            }

            if ($request->has('department') && $user->hasRole('hr') && $request->department) {
                $query->whereHas('user.department', function ($q) use ($request) {
                    $q->where('dept_name', $request->department);
                });
            }

            // Search
            if ($request->has('search') && $request->search['value']) {
                $search = $request->search['value'];
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('ccbrt_code', 'like', '%' . $search . '%')
                            ->orWhere('fname', 'like', '%' . $search . '%')
                            ->orWhere('mname', 'like', '%' . $search . '%')
                            ->orWhere('lname', 'like', '%' . $search . '%');
                    });
                });
            }

            return DataTables::of($query)
                ->addColumn('checkbox', function ($request) {
                    return '<input type="checkbox" name="request_ids[]" value="' . $request->workflow->id . '">';
                })
                ->addColumn('full_name', function ($request) {
                    return trim(($request->user->fname ?? '') . ' ' . ($request->user->mname ?? '') . ' ' . ($request->user->lname ?? ''));
                })
                ->addColumn('department', fn($r) => $r->user->department->dept_name ?? 'N/A')
                ->addColumn('education_level', fn($r) => $r->locumAgreement->education_level ?? 'N/A')
                ->addColumn('locum_rate', fn($r) => number_format($r->locumAgreement->locum_rate ?? 0, 2))
                ->addColumn('locum_month', function ($r) {
                    return $r->locum_month . ' <span class="text-muted" style="font-size: 0.9em;">(' . \Carbon\Carbon::parse($r->created_at)->year . ')</span>';
                })
                ->addColumn('total_hours', fn($r) => number_format($r->total_hours, 2))
                ->addColumn('total_amount', fn($r) => number_format($r->total_amount_payable, 2))
                ->addColumn('approval_status', function ($r) {
                    $wf = $r->workflow;
                    $histories = $wf ? $wf->histories : collect();
                    $lastHistory = $histories->sortByDesc('updated_at')->first();

                    $pendingLabel = fn(?string $step) => match ($step) {
                        'Incharge Approval' => 'Pending In-Charge Approval',
                        'Platform Manager Approval' => 'Pending Platform Manager Approval',
                        'Line Manager Approval' => 'Pending Line Manager Approval',
                        'HEC Approval'          => 'Pending HEC Approval',
                        'HR Approval'           => 'Pending HR Approval',
                        default                 => 'Pending',
                    };

                    $label = 'Pending';
                    $class = 'bg-warning';
                    $tooltip = null;

                    // Check if there's a pending step FIRST (after resubmission, there may be pending steps even if old rejected entries exist)
                    $pendingHistory = $histories->firstWhere('status', 0);
                    if ($pendingHistory) {
                        // Determine pending label based on step name
                        $stepName = $pendingHistory->step_name ?? '';
                        $label = $pendingLabel($stepName);
                        $class = 'bg-warning';
                    } elseif ($wf && (int) $wf->work_flow_completed === 1 && (int) $wf->work_flow_status === 1) {
                        // Workflow is completed AND approved (status === 1 means approved)
                        $label = $lastHistory && $lastHistory->step_name === 'HR Approval' && (string) $lastHistory->status === '1'
                            ? 'HR Approved' : 'Approved';
                        $class = 'bg-success';
                    } elseif ($lastHistory && (string) $lastHistory->status === '1') {
                        // Last step was approved but workflow not completed - still pending
                        $stepName = $lastHistory->step_name ?? '';
                        $label = match ($stepName) {
                            'HR Approval' => 'Pending HR Approval',
                            default => 'Pending',
                        };
                        $class = 'bg-warning';
                    } else {
                        // Check for rejection (only if no pending steps)
                        if ($lastHistory) {
                            if ((string) $lastHistory->status === '2' || (int) ($lastHistory->locum_request_status ?? 0) === 5) {
                                $label = 'Rejected';
                                $class = 'bg-danger';
                                if (!empty($lastHistory->rejection_reason)) $tooltip = $lastHistory->rejection_reason;
                            } elseif ($wf && (int) $wf->work_flow_status === 2) {
                                // Workflow marked as rejected
                                $label = 'Rejected';
                                $class = 'bg-danger';
                                if ($lastHistory && (string) $lastHistory->status === '2') {
                                    $tooltip = $lastHistory->rejection_reason ?? null;
                                }
                            }
                        }
                    }

                    return '<span class="badge ' . $class . '" ' . ($tooltip ? 'title="' . e($tooltip) . '"' : '') . '>' . e($label) . '</span>';
                })
                ->addColumn('worked_days', function ($r) {
                    return '
                  <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                      data-bs-toggle="modal"
                      data-bs-target="#workedDaysModal' . $r->id . '"
                      data-load-url="' . route('locum-requests.worked-days', $r->id) . '"
                      data-request-id="' . $r->id . '">
                    <i class="fas fa-calendar-day"></i> <span>View</span>
                  </button>';
                })
                ->rawColumns(['checkbox', 'locum_month', 'approval_status', 'worked_days'])
                ->make(true);
        }

        // Non-AJAX page load
        $query = LocumRequest::whereHas('workflow', function ($query) use ($user) {
            $query->where('work_flow_completed', 0)
                ->whereHas('histories', function ($q) use ($user) {
                    $q->where('attended_by', $user->id)
                        ->where('status', 0)
                        ->whereNotNull('locum_request_status');
                });
        })
            ->with([
                'user' => function ($query) {
                    $query->with('department');
                },
                'locumAgreement',
                'workflow' => function ($q) {
                    $q->select('id', 'user_id', 'locum_request_id', 'work_flow_completed', 'work_flow_status');
                },
                // ⬇️ include sender/assignee/approver on each history row
                'workflow.histories' => function ($q) {
                    $q->whereNotNull('locum_request_status')
                        ->with(['forwardedBy', 'attendedBy', 'approver'])
                        ->latest();
                }
            ]);

        if ($user->hasRole('line-manager') && !$user->hasRole('hr')) {
            $query->whereHas('user', fn($q) => $q->where('deptId', $user->deptId));
        }

        // Get all requests for DataTables (no pagination needed)
        $requests = $query->orderBy('created_at', 'desc')->get();

        // Count pending locum agreements assigned to this approver
        $pendingLocumAgreementCount = \DB::table('work_flow_histories')
            ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->where('work_flow_histories.attended_by', $user->id)
            ->where('work_flow_histories.status', 0)
            ->whereNotNull('workflows.locum_agreement_id')
            ->count();

        // Count pending night shift claims assigned to this approver
        $pendingNightShiftCount = \DB::table('work_flow_histories')
            ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->where('work_flow_histories.attended_by', $user->id)
            ->where('work_flow_histories.status', 0)
            ->whereNotNull('workflows.night_shift_claim_id')
            ->count();

        return view('locum_requests.view', compact('requests', 'pendingLocumAgreementCount', 'pendingNightShiftCount'));
    }

    public function export(Request $request)
    {
        $query = LocumRequest::whereHas('workflow', function ($query) {
            $query->where('work_flow_completed', 0)
                ->whereHas('histories', function ($q) {
                    $q->where('step_name', 'HR Approval')
                        ->where('status', 0)
                        ->whereHas('attendedBy', function ($q) {
                            $q->whereHas('roles', function ($q) {
                                $q->where('name', 'hr');
                            });
                        });
                });
        })
            ->with([
                'user' => function ($query) {
                    $query->with('department');
                },
                'locumAgreement',
                'workflow.histories' => function ($q) {
                    $q->where('step_name', 'HR Approval')->where('status', 0)->latest();
                }
            ]);

        // Apply month filter
        if ($request->has('month') && $request->month) {
            $query->where('locum_month', $request->month);
        }

        // Apply department filter for HR users
        $department = null;
        if ($request->has('department') && $request->department && Auth::user()->hasRole('hr')) {
            $query->whereHas('user.department', function ($q) use ($request) {
                $q->where('dept_name', $request->department);
            });
            $department = $request->department;
        }

        // Fetch requests
        $requests = $query->orderBy('created_at', 'desc')->get();

        // Generate dynamic filename
        $month = $request->month ?? 'All_Months';
        $departmentName = $department ?? 'All_Departments';
        $fileName = "Locum_Requests_HR_Approval_{$month}_{$departmentName}_" . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new LocumRequestsExport($requests, $department), $fileName);
    }

    public function reportSelection()
    {
        $user = Auth::user();

        // Check if user has permission to view approve summary
        if (!$user->can('view locum reports') && ($user->can_view_approve_summary ?? 0) != 1) {
            abort(403, 'You do not have permission to view reports.');
        }

        return view('reports.selection');
    }

    public function report(Request $request)
    {
        $user = Auth::user();

        // Check if user has permission to view approve summary
        if (!$user->can('view locum reports') && ($user->can_view_approve_summary ?? 0) != 1) {
            abort(403, 'You do not have permission to view this report.');
        }

        // ⬇️ Summary filters (independent of the rest of the page)
        $summaryYear  = $request->query('summary_year');   // e.g. 2025
        $summaryMonth = $request->query('summary_month');  // 1..12

        // Check if user is HR - HR users can view all requests including those approved many months ago
        $isHR = $user->hasRole('hr');

        // Get all requests
        // For HR users: Include ALL requests (including those fully approved by HR) to view historical data
        // For non-HR users: Exclude requests fully approved by HR (to show pending requests that need attention)
        $actionedRequestsQuery = LocumRequest::whereHas('workflow')
            ->with([
                'user.department',
                'locumAgreement',
                'workflow.histories' => function ($q) {
                    $q->with(['attendedBy', 'forwardedBy', 'approver'])
                        ->orderBy('id', 'asc'); // Order by ID to show chronological order
                },
            ]);

        // Only exclude HR-approved requests if user is NOT HR
        if (!$isHR) {
            $actionedRequestsQuery->whereDoesntHave('workflow.histories', function ($query) {
                // Exclude requests where HR has approved (status = 1 and step_name = 'HR Approval')
                $query->where('step_name', 'HR Approval')
                    ->where('status', 1);
            });
        }

        $actionedRequests = $actionedRequestsQuery
            ->orderBy('created_at', 'desc')
            ->get();

        // Build options from existing data
        $summaryYearOptions = $actionedRequests
            ->filter(fn($r) => !is_null($r->created_at))
            ->map(fn($r) => \Carbon\Carbon::parse($r->created_at)->year)
            ->unique()->sortDesc()->values();

        $summaryMonthOptions = collect([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);

        // Helper: resolve numeric month for each request (prefer locum_month if set, else created_at)
        $resolveMonth = function ($r) {
            try {
                if (!empty($r->locum_month)) {
                    // locum_month like "January 2025" or "January"
                    return \Carbon\Carbon::parse('1 ' . $r->locum_month)->month;
                }
            } catch (\Throwable $e) {
                // fallback below
            }
            return \Carbon\Carbon::parse($r->created_at)->month;
        };

        // Apply summary-only filters on a derived collection
        $summarySource = $actionedRequests
            ->when($summaryYear, function ($col) use ($summaryYear) {
                return $col->filter(fn($r) => (int)\Carbon\Carbon::parse($r->created_at)->year === (int)$summaryYear);
            })
            ->when($summaryMonth, function ($col) use ($summaryMonth, $resolveMonth) {
                return $col->filter(fn($r) => (int)$resolveMonth($r) === (int)$summaryMonth);
            })
            ->values();

        // Department summary (from filtered summarySource)
        $deptSummary = $summarySource
            ->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
            ->map(function ($rows) {
                $approved = $rows->filter(function ($r) {
                    $main = ($r->status ?? null) === 'approved';
                    $code = optional($r->workflow?->histories->first())->locum_request_status;
                    return $main || (int)$code === 4; // 4 = Approved
                });

                $rejected = $rows->filter(function ($r) {
                    $main = ($r->status ?? null) === 'rejected';
                    $code = optional($r->workflow?->histories->first())->locum_request_status;
                    return $main || (int)$code === 5; // 5 = Rejected
                });

                return [
                    'requests'  => $rows->count(),
                    'employees' => $rows->pluck('user_id')->unique()->count(),
                    'approved'  => $approved->count(),
                    'rejected'  => $rejected->count(),
                    'hours'     => round((float)$rows->sum('total_hours'), 2),
                    'amount'    => (float)$rows->sum('total_amount_payable'),
                ];
            })
            ->sortKeys();

        // Grand totals — ONLY Approved (status=1) per your rule
        $approvedOnly = $summarySource->filter(function ($r) {
            $main = ($r->status ?? null) === 'approved';
            $code = optional($r->workflow?->histories->first())->locum_request_status;
            return $main || (int)$code === 4;
        });

        $grandTotals = [
            'requests'  => $approvedOnly->count(),
            'employees' => $approvedOnly->pluck('user_id')->unique()->count(),
            'approved'  => $approvedOnly->count(),
            'hours'     => round((float)$approvedOnly->sum('total_hours'), 2),
            'amount'    => (float)$approvedOnly->sum('total_amount_payable'),
        ];

        // === Payment Report: Claims approved by HR in current month ===
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $paymentReport = LocumRequest::whereHas('workflow.histories', function ($q) use ($currentYear, $currentMonth) {
            $q->where('step_name', 'HR Approval')
                ->where('status', 1) // Approved
                ->whereYear('updated_at', $currentYear)
                ->whereMonth('updated_at', $currentMonth);
        })
            ->whereHas('workflow', fn($q) => $q->where('work_flow_completed', 1))
            ->with([
                'user.department',
                'locumAgreement',
                'workflow.histories' => function ($q) {
                    $q->where('step_name', 'HR Approval')
                        ->where('status', 1)
                        ->latest();
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $paymentReportSummary = [
            'total_requests' => $paymentReport->count(),
            'total_employees' => $paymentReport->pluck('user_id')->unique()->count(),
            'total_hours' => round((float)$paymentReport->sum('total_hours'), 2),
            'total_amount' => (float)$paymentReport->sum('total_amount_payable'),
            'by_department' => $paymentReport->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
                ->map(function ($rows) {
                    return [
                        'count' => $rows->count(),
                        'employees' => $rows->pluck('user_id')->unique()->count(),
                        'hours' => round((float)$rows->sum('total_hours'), 2),
                        'amount' => (float)$rows->sum('total_amount_payable'),
                    ];
                })
                ->sortKeys(),
        ];

        // === Analytics/Trends: Department-wise statistics ===
        // Get filter parameters
        $analyticsDept = $request->query('analytics_dept');
        $fromYear = $request->query('from_year');
        $fromMonth = $request->query('from_month');
        $toYear = $request->query('to_year');
        $toMonth = $request->query('to_month');
        $quickFilter = $request->query('quick_filter');

        // Handle quick filter presets
        if ($quickFilter) {
            $now = Carbon::now();
            switch ($quickFilter) {
                case 'this_month':
                    $fromYear = $toYear = $now->year;
                    $fromMonth = $toMonth = $now->month;
                    break;
                case 'last_month':
                    $lastMonth = $now->copy()->subMonth();
                    $fromYear = $toYear = $lastMonth->year;
                    $fromMonth = $toMonth = $lastMonth->month;
                    break;
                case 'last_3_months':
                    $threeMonthsAgo = $now->copy()->subMonths(2);
                    $fromYear = $threeMonthsAgo->year;
                    $fromMonth = $threeMonthsAgo->month;
                    $toYear = $now->year;
                    $toMonth = $now->month;
                    break;
                case 'this_year':
                    $fromYear = $toYear = $now->year;
                    $fromMonth = 1;
                    $toMonth = $now->month;
                    break;
            }
        }

        // Determine date range first so we can scope the query
        $startDate = null;
        $endDate   = null;

        if ($fromYear && $fromMonth) {
            $startDate = Carbon::create($fromYear, $fromMonth, 1)->startOfMonth();
        }
        if ($toYear && $toMonth) {
            $endDate = Carbon::create($toYear, $toMonth, 1)->endOfMonth();
        }

        // Default to current year (Jan → current month)
        if (!$startDate || !$endDate) {
            $endDate   = now()->endOfMonth();
            $startDate = Carbon::create(now()->year, 1, 1)->startOfMonth();
        }

        // Get approved requests scoped to the selected year range
        // Only include claims that have a payable amount (actually paid/processed)
        $allApproved = LocumRequest::whereHas('workflow.histories', function ($q) use ($startDate, $endDate) {
            $q->where('step_name', 'HR Approval')
                ->where('status', 1)
                ->whereBetween('updated_at', [$startDate, $endDate]);
        })
            ->whereHas('workflow', fn($q) => $q->where('work_flow_completed', 1))
            ->where('total_amount_payable', '>', 0)
            ->with(['user.department', 'workflow.histories' => function ($q) {
                $q->where('step_name', 'HR Approval')
                    ->where('status', 1)
                    ->latest();
            }])
            ->get();

        // Apply department filter if specified
        if ($analyticsDept && $analyticsDept !== '_all') {
            $allApproved = $allApproved->filter(function ($req) use ($analyticsDept) {
                return ($req->user?->department?->dept_name ?? 'N/A') === $analyticsDept;
            });
        }

        // Get all departments for filter dropdown
        $allDepartments = Departments::orderBy('dept_name', 'asc')->get();

        // Get top departments by total amount for the chart (max 8 to keep it readable)
        $topDepts = $allApproved->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
            ->map(function ($rows) {
                return (float)$rows->sum('total_amount_payable');
            })
            ->sortByDesc(function ($amount) {
                return $amount;
            })
            ->take(8)
            ->keys()
            ->values()
            ->all();

        $monthlyTrends = [];
        $currentDate = $startDate->copy();

        // Generate monthly data for the date range
        while ($currentDate->lte($endDate)) {
            $monthYear = $currentDate->format('Y-m');
            $monthName = $currentDate->format('M Y'); // Month and year for chart

            $monthRequests = $allApproved->filter(function ($req) use ($currentDate) {
                $hrApproval = $req->workflow?->histories?->first();
                if (!$hrApproval) return false;
                $approvedAt = $hrApproval->updated_at ?? $hrApproval->created_at;
                if (!$approvedAt) return false;
                $approved = Carbon::parse($approvedAt);
                return $approved->year == $currentDate->year && $approved->month == $currentDate->month;
            });

            // Group by department for this month
            $byDept = $monthRequests->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
                ->map(function ($rows) {
                    return (float)$rows->sum('total_amount_payable') / 1000; // Convert to thousands
                });

            // Ensure all top departments are included (even with 0)
            $byDeptComplete = collect($topDepts)->mapWithKeys(function ($dept) use ($byDept) {
                return [$dept => $byDept->get($dept, 0)];
            });

            $monthlyTrends[$monthYear] = [
                'label' => $monthName,
                'count' => $monthRequests->count(),
                'amount' => (float)$monthRequests->sum('total_amount_payable'),
                'hours' => round((float)$monthRequests->sum('total_hours'), 2),
                'by_department' => $byDeptComplete->toArray(),
            ];

            $currentDate->addMonth();
        }

        // Calculate growth/decline metrics
        $trendsArray = array_values($monthlyTrends);
        $growth_amount = 0;
        $growth_count = 0;
        $growth_percent = 0;
        $count_growth_percent = 0;

        if (count($trendsArray) >= 2) {
            $latest = $trendsArray[count($trendsArray) - 1];
            $previous = $trendsArray[count($trendsArray) - 2];

            $growth_amount = $latest['amount'] - $previous['amount'];
            $growth_count = $latest['count'] - $previous['count'];

            if ($previous['amount'] > 0) {
                $growth_percent = (($latest['amount'] - $previous['amount']) / $previous['amount']) * 100;
            }

            if ($previous['count'] > 0) {
                $count_growth_percent = (($latest['count'] - $previous['count']) / $previous['count']) * 100;
            }
        }

        // Department trends - apply date range filter
        $filteredApproved = $allApproved->filter(function ($req) use ($startDate, $endDate) {
            $hrApproval = $req->workflow?->histories?->first();
            if (!$hrApproval) return false;
            $approvedAt = $hrApproval->updated_at ?? $hrApproval->created_at;
            if (!$approvedAt) return false;
            $approved = Carbon::parse($approvedAt);
            return $approved->gte($startDate) && $approved->lte($endDate);
        });

        $deptTrends = $filteredApproved->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
            ->map(function ($rows) {
                return [
                    'total_requests' => $rows->count(),
                    'total_employees' => $rows->pluck('user_id')->unique()->count(),
                    'total_hours' => round((float)$rows->sum('total_hours'), 2),
                    'total_amount' => (float)$rows->sum('total_amount_payable'),
                    'avg_per_request' => $rows->count() > 0 ? round((float)$rows->sum('total_amount_payable') / $rows->count(), 2) : 0,
                ];
            })
            ->sortByDesc('total_amount')
            ->take(10); // Top 10 departments

        // Top departments for chart (filtered by date range)
        $topDeptsFiltered = $filteredApproved->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
            ->map(function ($rows) {
                return (float)$rows->sum('total_amount_payable');
            })
            ->sortByDesc(function ($amount) {
                return $amount;
            })
            ->take(8)
            ->keys()
            ->values()
            ->all();

        $analytics = [
            'monthly_trends' => $monthlyTrends,
            'department_trends' => $deptTrends,
            'top_departments' => $topDeptsFiltered,
            'growth_amount' => $growth_amount,
            'growth_count' => $growth_count,
            'growth_percent' => $growth_percent,
            'count_growth_percent' => $count_growth_percent,
        ];

        return view('locum_requests.report', compact(
            'actionedRequests',
            'deptSummary',
            'grandTotals',
            'summaryYearOptions',
            'summaryMonthOptions',
            'summaryYear',
            'summaryMonth',
            'paymentReport',
            'paymentReportSummary',
            'analytics',
            'currentMonth',
            'currentYear',
            'allDepartments',
            'analyticsDept'
        ));
    }

    // Keep old method name for backward compatibility
    public function actioned(Request $request)
    {
        return $this->report($request);
    }
    public function showLocumRequest(int $id)
    {
        $req = LocumRequest::with([
            'user.department',
            'locumAgreement',
            'workflow.histories' => fn($q) => $q->with(['attendedBy', 'forwardedBy'])->orderBy('id'),
        ])->findOrFail($id);

        $wf        = $req->workflow;
        $histories = $wf ? $wf->histories : collect();
        $last      = $histories->sortByDesc(fn($h) => $h->updated_at ?? $h->created_at)->first();

        $pendingLabel = fn(?string $step) => match ($step) {
            'In-Charge Approval'    => 'Pending In-Charge Approval',
            'Line Manager Approval' => 'Pending Line Manager Approval',
            'HEC Approval'          => 'Pending HEC Approval',
            'HR Approval'           => 'Pending HR Approval',
            default                 => 'Pending',
        };

        $statusLabel = 'Pending';
        $statusClass = 'warning';
        $tooltip     = null;

        if ($wf && (int) $wf->work_flow_completed === 1) {
            $statusLabel = ($last && $last->step_name === 'HR Approval' && (string) $last->status === '1')
                ? 'HR Approved' : 'Approved';
            $statusClass = 'success';
        } elseif ($last) {
            $code = (int) ($last->locum_request_status ?? 0);
            if ((string) $last->status === '2' || $code === 5) {
                $statusLabel = 'Rejected';
                $statusClass = 'danger';
                $tooltip     = $last->rejection_reason ?: null;
            } else {
                $nextPending = $histories->firstWhere('status', 0);
                $statusLabel = $nextPending ? $pendingLabel($nextPending->step_name) : $pendingLabel($last->step_name);
                $statusClass = 'warning';
            }
        }

        $pendingWith = null;
        $pendingStep = null;
        if ($statusClass === 'warning') {
            $next        = $histories->firstWhere('status', 0);
            $pendingStep = $next?->step_name;
            if ($next?->attendedBy) {
                $nm = trim(collect([$next->attendedBy->fname ?? null, $next->attendedBy->lname ?? null])->filter()->implode(' '));
                $pendingWith = $nm !== '' ? $nm : ($next->attendedBy->email ?? null);
            }
        }

        $steps = $histories->map(function ($h) {
            $attendedName = $h->attendedBy
                ? (trim(collect([$h->attendedBy->fname ?? null, $h->attendedBy->lname ?? null])->filter()->implode(' ')) ?: ($h->attendedBy->email ?? '—'))
                : '—';
            $forwardedName = $h->forwardedBy
                ? (trim(collect([$h->forwardedBy->fname ?? null, $h->forwardedBy->lname ?? null])->filter()->implode(' ')) ?: ($h->forwardedBy->email ?? '—'))
                : '—';

            $statusText = match ((string) $h->status) {
                '0' => 'Pending',
                '1' => 'Approved',
                '2' => 'Rejected',
                default => '—',
            };

            // Only show an "acted at" timestamp once someone acted (Approved/Rejected).
            $actedAt = null;
            if ((string) $h->status !== '0') {
                // Priority: attend_date > updated_at > created_at
                $dt = $h->attend_date ?: $h->updated_at ?: $h->created_at;
                if ($dt) {
                    if (!$dt instanceof \Carbon\Carbon) {
                        $dt = \Carbon\Carbon::parse($dt);
                    }
                    $actedAt = $dt->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i');
                }
            }

            return [
                'step'         => $h->step_name,
                'attended_by'  => $attendedName,
                'forwarded_by' => $forwardedName,
                'status'       => $statusText,
                'remark'       => $h->remark,
                'rejection'    => $h->rejection_reason,
                'acted_at'     => $actedAt ?? '—',
            ];
        })->values();

        // Submitted at (create action) and HR approved at (approve action when completed)
        $submittedAt = $req->created_at
            ? \Carbon\Carbon::parse($req->created_at)->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i')
            : null;
        $hrApprovedAt = null;
        $hrHistory = $histories->firstWhere(fn ($h) => $h->step_name === 'HR Approval' && (string) $h->status === '1');
        if ($hrHistory) {
            $dt = $hrHistory->attend_date ?: $hrHistory->updated_at ?: $hrHistory->created_at;
            if ($dt) {
                $hrApprovedAt = \Carbon\Carbon::parse($dt)->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i');
            }
        }

        return response()->json([
            'created_at'    => $submittedAt,
            'hr_approved_at' => $hrApprovedAt,
            'user' => [
                'username'   => $req->user->username ?? '—',
                'email'      => $req->user->email ?? '—',
                'department' => optional($req->user->department)->dept_name ?? '—',
                'status'     => $req->user->status ?? '—',
                'end_date'   => optional($req->user->ending_date)->format('d M Y') ?? '—',
            ],
            'agreement' => $req->locumAgreement ? [
                'locum_rate'   => number_format($req->locumAgreement->locum_rate, 2),
                'start_date'   => optional($req->locumAgreement->start_date)->format('d M Y'),
                'end_date'     => optional($req->locumAgreement->end_date)->format('d M Y'),
                'has_contract' => $req->locumAgreement->has_contract ? 'Yes' : 'No',
            ] : null,
            'workflow' => [
                'current_label' => $statusLabel,
                'current_class' => $statusClass, // success|warning|danger
                'tooltip'       => $tooltip,
                'pending_step'  => $pendingStep,
                'pending_with'  => $pendingWith,
                'completed'     => $wf ? (int) $wf->work_flow_completed === 1 : false,
                'steps'         => $steps,
            ],
        ]);
    }

    public function create()
    {
        $user = Auth::user(); // no need to load primaryPlatform anymore

        // Must have a valid (non-expired) approved agreement, or expired but within admin "use until" grace
        $today = \Carbon\Carbon::today();
        $agreement = LocumAgreement::where('user_id', $user->id)
            ->where('status', 2) // Only HR-approved agreements
            ->where(function($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$agreement) {
            // Grace: admin may allow using expired agreement until a date
            $useUntilRaw = SettingsController::getSetting('locum_expired_agreement_use_until', '');
            if ($useUntilRaw !== '' && $useUntilRaw !== null) {
                try {
                    $useUntil = \Carbon\Carbon::parse($useUntilRaw)->endOfDay();
                    if ($today->lte($useUntil)) {
                        $candidate = LocumAgreement::where('user_id', $user->id)
                            ->where('status', 2)
                            ->orderByDesc('updated_at')
                            ->orderByDesc('created_at')
                            ->first();
                        if ($candidate && $candidate->end_date && $today->gt(\Carbon\Carbon::parse($candidate->end_date)->endOfDay())) {
                            $agreement = $candidate;
                        }
                    }
                } catch (\Exception $e) {
                }
            }
        }

        if (!$agreement) {
            $expiredApproved = LocumAgreement::where('user_id', $user->id)
                ->where('status', 2)
                ->where('end_date', '<', $today)
                ->exists();

            if ($expiredApproved) {
                return redirect()->route('locum-requests.index')
                    ->with('error', 'Your locum agreement has expired. Please create a new agreement to continue submitting claims.');
            }

            return redirect()->route('locum-agreements.index')
                ->with('error', 'You must have an active HR-approved locum agreement to create a request. Only agreements approved by HR can be used.');
        }

        if ((int)$agreement->status !== 2) {
            $statusMessages = [
                0 => 'pending Line Manager approval',
                1 => 'pending HR approval',
                3 => 'rejected by Line Manager',
                4 => 'rejected by HR',
            ];
            $statusMsg = $statusMessages[(int)$agreement->status] ?? 'not approved';
            return redirect()->route('locum-agreements.view')
                ->with('error', "Your locum agreement is {$statusMsg}. Only HR-approved agreements can be used for locum requests.");
        }

        // Target month defaults to previous month
        $prev = now('Africa/Dar_es_Salaam')->subMonth();
        $previousMonth = $prev->format('F');
        $previousYear  = (int) $prev->format('Y');
        $defaultClaimMonth = $prev->format('F Y');

        // Get deadline day from system settings (default to 5th if not set)
        $today = now('Africa/Dar_es_Salaam');
        $deadlineDay = (int) SettingsController::getSetting('locum_submission_deadline', 5);
        $deadline = $today->copy()->startOfMonth()->addDays($deadlineDay - 1)->endOfDay();

        if ($today->gt($deadline)) {
            return redirect()->route('locum-requests.index')
                ->with('error', "Submission for {$defaultClaimMonth} is closed. The deadline was {$deadline->format('j F Y')}.");
        }

        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        // Static holiday list (adjust as needed)
        $holidays = [
            "{$previousYear}-01-01" => "New Year's Day",
            "{$previousYear}-01-12" => 'Zanzibar Revolution Day',
            "{$previousYear}-04-07" => 'Karume Day',
            "{$previousYear}-04-26" => 'Union Day',
            "{$previousYear}-05-01" => "Workers' Day",
            "{$previousYear}-07-07" => 'Saba Saba',
            "{$previousYear}-08-08" => 'Nane Nane',
            "{$previousYear}-10-14" => 'Nyerere Day',
            "{$previousYear}-12-09" => 'Independence Day',
            "{$previousYear}-12-25" => 'Christmas Day',
            "{$previousYear}-12-26" => 'Boxing Day',
            "{$previousYear}-03-29" => 'Eid al-Fitr (Estimated)',
            "{$previousYear}-06-05" => 'Eid al-Adha (Estimated)',
            "{$previousYear}-09-27" => 'Maulid (Estimated)',
        ];

        // Shifts: id + name only (users will input hours manually)
        $shifts = ShiftSetting::select('id', 'name')
            ->orderBy('name')
            ->get();

        // Platforms now include locum_hours (8 or 12)
        $platforms = Platform::select('id', 'name', 'locum_hours')
            ->orderBy('name')
            ->get();

        // Handy map for JS: { [platformId]: locum_hours }
        $platformHours = $platforms->pluck('locum_hours', 'id');

        // Get department settings for unit selection logic
        $department = $user->department;
        $hasThreeLevel = (bool) ($department->has_three_level_approval ?? false); // LM → HEC → HR
        $hasInchargeLmHec = (bool) ($department->has_incharge_lm_hec_flow ?? false); // In-Charge → LM → HEC → HR
        $isStandardFlow = !$hasThreeLevel && !$hasInchargeLmHec && !($department->has_incharge_platform_flow ?? false); // LM → HR (standard)

        // Disable unit selection if flow is LM→HEC→HR or LM→HR (standard)
        $disableUnitSelection = $hasThreeLevel || $isStandardFlow || $hasInchargeLmHec;

        return view('locum_requests.create', compact(
            'user',
            'agreement',
            'months',
            'previousMonth',
            'previousYear',
            'holidays',
            'shifts',
            'platforms',
            'platformHours',
            'disableUnitSelection'
        ));
    }

    public function exportActioned(Request $request)
    {
        $year       = $request->integer('year') ?: null;            // e.g. 2025
        $month      = $request->integer('month') ?: null;           // 1..12
        $department = $request->query('department');                // '_all' or Dept name
        $basis      = $request->query('basis', 'approved');         // 'approved' | 'locum' | 'created'  <-- default now 'approved'

        // Base: HR-approved ONLY, by an HR user, and completed workflow
        $query = LocumRequest::query()
            ->with([
                'user.department',
                'locumAgreement',
                // Load ONLY the latest HR-Approved history to get the approval timestamp cleanly
                'workflow.histories' => function ($q) {
                    $q->where('step_name', 'HR Approval')
                        ->where('status', 1)
                        ->latest(); // latest approvals first; we'll take ->first()
                },
                // Optional but handy if you show who approved
                'workflow.histories.user.roles',
            ])
            ->whereHas('workflow.histories', function ($q) {
                $q->where('step_name', 'HR Approval')
                    ->where('status', 1)
                    ->whereHas('user.roles', fn($rq) => $rq->where('name', 'hr'));
            })
            ->whereHas('workflow', fn($q) => $q->where('work_flow_completed', 1));

        // Department (DB-level)
        if ($department && $department !== '_all') {
            $query->whereHas('user.department', fn($q) => $q->where('dept_name', $department));
        }

        // If basis is 'created' we can apply SQL filters. For 'approved' or 'locum', we’ll filter in PHP.
        if ($basis === 'created') {
            if ($year)  $query->whereYear('created_at', $year);
            if ($month) $query->whereMonth('created_at', $month);
        }

        $allApproved = $query->orderBy('created_at', 'desc')->get();

        // Helpers
        $parseClaim = function (?string $text, $createdAt) {
            $created = $createdAt ? Carbon::parse($createdAt) : null;
            $fallbackYear = $created ? (int)$created->year : (int)date('Y');

            if (!$text || !is_string($text)) return [null, null];
            $t = trim($text);

            if (preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})$/i', $t, $m)) {
                return [Carbon::parse("1 {$m[1]}")->month, (int)$m[2]];
            }
            if (preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)$/i', $t, $m)) {
                return [Carbon::parse("1 {$m[1]}")->month, $fallbackYear];
            }
            if (preg_match('/^(\d{4})[-\/](\d{1,2})$/', $t, $m)) return [(int)$m[2], (int)$m[1]];
            if (preg_match('/^(\d{1,2})[-\/](\d{4})$/', $t, $m)) return [(int)$m[1], (int)$m[2]];

            try {
                $c = Carbon::parse($t);
                return [(int)$c->month, (int)$c->year];
            } catch (\Throwable $e) {
                return [null, null];
            }
        };

        $hrApprovedAt = function ($req) {
            // Because we constrained histories to HR Approval + status=1 and sorted latest(),
            // the first() is the HR approval record we want.
            $h = optional($req->workflow)->histories->first();
            return $h?->updated_at ?? $h?->created_at; // updated_at is the safest; fallback created_at
        };

        // Precise PHP-side filtering per chosen basis (default = 'approved')
        $filtered = $allApproved->filter(function ($req) use ($basis, $year, $month, $parseClaim, $hrApprovedAt) {
            $createdMonth = optional($req->created_at)->month;
            $createdYear  = optional($req->created_at)->year;

            [$claimMonth, $claimYear] = $parseClaim($req->locum_month, $req->created_at);

            if ($basis === 'approved') {
                $approvedTime = $hrApprovedAt($req);
                if (!$approvedTime) return false;
                $appr = Carbon::parse($approvedTime);
                if ($year  && (int)$appr->year  !== $year)  return false;
                if ($month && (int)$appr->month !== $month) return false;
                return true;
            }

            if ($basis === 'locum') {
                if ($year  && $claimYear  !== $year)  return false;
                if ($month && $claimMonth !== $month) return false;
                return true;
            }

            // 'created'
            if ($year  && (int)$createdYear  !== $year)  return false;
            if ($month && (int)$createdMonth !== $month) return false;
            return true;
        })->values();

        // Filename reflects basis and filters
        $filename = 'locum_requests_hr_approved';
        $parts = [$basis];
        if ($department && $department !== '_all') $parts[] = Str::slug($department, '_');
        if ($year)  $parts[] = $year;
        if ($month) $parts[] = str_pad($month, 2, '0', STR_PAD_LEFT);
        if ($parts) $filename .= '_' . implode('_', $parts);
        $filename .= '.xlsx';

        return Excel::download(
            new ApprovedLocumRequestsExport(
                $filtered,
                $department === '_all' ? null : $department,
                $year,
                $month
            ),
            $filename
        );
    }

    /**
     * Show all approved locum requests for HR (similar to oncall approvedRequests)
     */
    public function approvedLocumRequests(Request $request)
    {
        $user     = Auth::user();
        $isHR     = $user->hasRole('hr');

        if (!$isHR) {
            Alert::error('Unauthorized', 'Only HR can view approved locum requests.');
            return redirect()->route('locum-requests.index');
        }

        // Payroll Year/Month (default: now in TZ)
        $now      = Carbon::now('Africa/Dar_es_Salaam');
        $payYear  = (int)($request->query('pay_year',  $now->year));
        $payMonth = (int)($request->query('pay_month', $now->month));

        // Optional filters
        $filterDept   = $request->query('department');
        $filterUser   = trim((string)$request->query('employee'));

        // Get all departments for HR
        $departments = Departments::orderBy('dept_name')->get();

        $q = LocumRequest::with([
            'user.department',
            'locumAgreement',
            'workflow.histories' => function ($h) {
                $h->where('step_name', 'HR Approval')
                    ->where('status', 1)
                    ->latest();
            }
        ]);

        // HR sees all approved requests
        $q->whereHas('workflow.histories', function ($h) use ($payYear, $payMonth) {
            $h->where('step_name', 'HR Approval')
                ->where('status', 1)
                ->whereYear('created_at', $payYear)
                ->whereMonth('created_at', $payMonth);
        })
            ->whereHas('workflow', fn($w) => $w->where('work_flow_completed', 1))
            ->where('status', 'approved');

        // Optional department filter
        if ($filterDept) {
            $q->whereHas('user', fn($u) => $u->where('deptId', (int)$filterDept));
        }

        // Optional employee filter
        if ($filterUser !== '') {
            $q->whereHas('user', function ($u) use ($filterUser) {
                $u->where(function ($sub) use ($filterUser) {
                    $sub->where('fname', 'like', "%{$filterUser}%")
                        ->orWhere('lname', 'like', "%{$filterUser}%")
                        ->orWhere('username', 'like', "%{$filterUser}%")
                        ->orWhere('email', 'like', "%{$filterUser}%");
                });
            });
        }

        $approvedRequests = $q->orderBy('created_at', 'desc')->get();

        $monthsList = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        return view('locum_requests.approved_requests', [
            'approvedRequests' => $approvedRequests,
            'departments'      => $departments,
            'payYear'          => $payYear,
            'payMonth'         => $payMonth,
            'isHR'             => $isHR,
            'filterUser'       => $filterUser,
            'monthsList'       => $monthsList,
        ]);
    }

    public function showWorkedDays($id)
    {
        try {
            $locum = LocumRequest::with(['user', 'locumAgreement:id,locum_rate'])->findOrFail($id);
            $user  = $locum->user;

            // -------- Normalize worked_days to Y-m-d keys --------
            $workedDaysRaw = $locum->worked_days;
            if (is_string($workedDaysRaw)) {
                $workedDaysRaw = json_decode($workedDaysRaw, true) ?? [];
            }

            $workedDays = [];
            $yearsSeen  = [];
            foreach ((array) $workedDaysRaw as $date => $info) {
                try {
                    $dt = \Carbon\Carbon::hasFormat($date, 'Y-m-d')
                        ? \Carbon\Carbon::createFromFormat('Y-m-d', $date)
                        : \Carbon\Carbon::createFromFormat('j F Y', $date);
                } catch (\Throwable $e) {
                    \Log::warning('Bad worked_days date key', ['key' => $date, 'locum_id' => $locum->id]);
                    continue;
                }
                $workedDays[$dt->toDateString()] = is_array($info) ? $info : [];
                $yearsSeen[(int)$dt->year] = true;
            }

            // -------- Resolve month/year window --------
            $monthName  = $locum->locum_month;
            $monthIndex = array_search($monthName, [
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December'
            ], true);
            if ($monthIndex === false) {
                \Log::error('Invalid locum_month on LocumRequest', ['locum_id' => $locum->id, 'locum_month' => $locum->locum_month]);
                return response()->json(['message' => 'Bad data: invalid locum month'], 422);
            }
            $monthIndex += 1;

            $year = !empty($yearsSeen)
                ? array_key_first($yearsSeen)
                : (int)\Carbon\Carbon::parse($locum->created_at)->year;

            // -------- BioTime skeleton for the month --------
            $anchor      = \Carbon\Carbon::create($year, $monthIndex, 1)->startOfMonth();
            $endOfMonth  = (clone $anchor)->endOfMonth();
            $bioTimeData = [];
            for ($d = (clone $anchor); $d->lte($endOfMonth); $d->addDay()) {
                $iso = $d->toDateString();
                $bioTimeData[$iso] = [
                    'worked'    => 0,
                    'hours'     => '0.00',
                    'time_in'   => null,
                    'time_out'  => null,
                    'ot_hours'  => '0.00',
                ];
            }

            // -------- Enrich entries: shift/platform/unit + in-charge --------
            $shiftIds = $platformIds = $unitIds = [];
            foreach ($workedDays as $iso => $info) {
                $entries = $info['entries'] ?? [];
                if (!is_array($entries)) continue;
                foreach ($entries as $e) {
                    if (!empty($e['shift_id']))    $shiftIds[]    = (int)$e['shift_id'];
                    if (!empty($e['platform_id'])) $platformIds[] = (int)$e['platform_id'];
                    if (!empty($e['unit_id']))     $unitIds[]     = (int)$e['unit_id'];
                }
            }
            $shiftIds    = array_values(array_unique($shiftIds));
            $platformIds = array_values(array_unique($platformIds));
            $unitIds     = array_values(array_unique($unitIds));

            $shiftMap = empty($shiftIds)
                ? collect()
                : \App\Models\ShiftSetting::whereIn('id', $shiftIds)->pluck('name', 'id');

            $platformsCollection = empty($platformIds)
                ? collect()
                : \App\Models\Platform::whereIn('id', $platformIds)->get(['id', 'name', 'locum_hours']);
            $platforms = $platformsCollection->keyBy('id');

            $unitNameMap = empty($unitIds)
                ? collect()
                : \App\Models\Unit::whereIn('id', $unitIds)->pluck('name', 'id');

            // Build unit_id => incharge display name via leftJoin (works even if relation name differs)
            $unitInchargeMap = empty($unitIds)
                ? collect()
                : \App\Models\Unit::query()
                ->whereIn('units.id', $unitIds)
                ->leftJoin('users', 'users.id', '=', 'units.incharge_user_id')
                ->get([
                    'units.id as unit_id',
                    'users.fname',
                    'users.mname',
                    'users.lname',
                    'users.username',
                ])
                ->mapWithKeys(function ($r) {
                    $name = trim(($r->fname ?? '') . ' ' . ($r->mname ?? '') . ' ' . ($r->lname ?? ''));
                    $display = $name !== '' ? $name : ($r->username ?? '—');
                    return [$r->unit_id => $display];
                });

            foreach ($workedDays as $iso => &$info) {
                $entries = $info['entries'] ?? [];
                if (!is_array($entries)) $entries = [];
                $total   = 0.0;

                foreach ($entries as &$e) {
                    $sid = (int)($e['shift_id'] ?? 0);
                    $pid = (int)($e['platform_id'] ?? 0);
                    $uid = (int)($e['unit_id'] ?? 0);
                    $hrs = (float)($e['hours'] ?? 0);

                    $platObj = $platforms->get($pid);

                    $e['shift_name']         = (string)($shiftMap[$sid] ?? '—');
                    $e['platform_name']      = $platObj ? (string)$platObj->name : '—';
                    $e['platform_thr']       = $platObj ? (int)$platObj->locum_hours : 8;
                    $e['unit_name']          = (string)($unitNameMap[$uid] ?? '—');
                    $e['unit_incharge_name'] = (string)($unitInchargeMap[$uid] ?? '—');
                    $e['hours']              = $hrs;

                    $total += $hrs;
                }
                unset($e);

                $info['worked']          = !empty($info['worked']) && (string)$info['worked'] === '1' ? '1' : '0';
                $info['entries']         = $entries;
                $info['day_total_hours'] = round($total, 2);
            }
            unset($info);

            // -------- No CCBRT? Return enriched only --------
            if (empty($user->ccbrt_code)) {
                \Log::info('No CCBRT code; returning enrichment only', ['user_id' => $user->id, 'locum_id' => $locum->id]);
                return response()->json([
                    'workedDays'  => $workedDays,
                    'bioTimeData' => $bioTimeData,
                    'locum_month' => $monthName,
                    'locum_year'  => $year,
                    'ccbrt_code'  => null,
                    'locum_rate'  => (float)($locum->locumAgreement->locum_rate ?? 0),
                ]);
            }

            // -------- BioTime (fail-soft) --------
            $bioOk = true;
            try {
                \DB::connection('bio')->getPdo();
            } catch (\Throwable $e) {
                $bioOk = false;
                \Log::error('BioTime connection failed', ['error' => $e->getMessage()]);
            }

            if ($bioOk) {
                $trxTable = 'iclock_transaction';
                $hasPunchState = false;
                try {
                    $hasPunchState = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, 'punch_state');
                } catch (\Throwable $e) {
                    \Log::warning('BioTime hasColumn check failed; assuming no punch_state', ['error' => $e->getMessage()]);
                    $hasPunchState = false;
                }

                try {
                    $start = (clone $anchor)->startOfDay();
                    $end   = (clone $endOfMonth)->addDay()->endOfDay();

                    $cols = $hasPunchState ? "emp_code, punch_time, punch_state" : "emp_code, punch_time";
                    $sql  = "
                    SELECT {$cols}
                    FROM {$trxTable}
                    WHERE emp_code = ?
                      AND punch_time >= ?
                      AND punch_time < ?
                    ORDER BY punch_time ASC
                ";
                    $rows = \DB::connection('bio')->select($sql, [trim($user->ccbrt_code), $start, $end]);

                    // Normalize punches
                    $punches = [];
                    foreach ($rows as $r) {
                        $dt  = \Carbon\Carbon::parse($r->punch_time);
                        $dir = 'UNK';
                        if ($hasPunchState) {
                            // 0 = IN, 1 = OUT
                            $dir = ((string)$r->punch_state === '0') ? 'IN' : (((string)$r->punch_state === '1') ? 'OUT' : 'UNK');
                        }
                        $punches[] = [
                            'dt'   => $dt,
                            'date' => $dt->toDateString(),
                            'time' => $dt->format('H:i:s'),
                            'dir'  => $dir,
                        ];
                    }

                    // Pair into sessions (36h cap)
                    $MAX_GAP_HOURS = 36;
                    $sessions = [];
                    $open = null;
                    $pushSession = function (array $a, array $b) use (&$sessions) {
                        $mins = (int) round(($b['dt']->getTimestamp() - $a['dt']->getTimestamp()) / 60);
                        if ($mins > 0) {
                            $sessions[] = [
                                'startDate' => $a['date'],
                                'startTime' => $a['time'],
                                'startDT'   => $a['dt'],
                                'endDate'   => $b['date'],
                                'endTime'   => $b['time'],
                                'endDT'     => $b['dt'],
                                'minutes'   => $mins,
                            ];
                        }
                    };

                    foreach ($punches as $p) {
                        $dir = ($p['dir'] === 'UNK') ? ($open ? 'OUT' : 'IN') : $p['dir'];
                        if ($dir === 'IN') {
                            if ($open) {
                                $gapH = ($p['dt']->getTimestamp() - $open['dt']->getTimestamp()) / 3600;
                                if ($gapH > 0 && $gapH <= $MAX_GAP_HOURS) $pushSession($open, $p);
                            }
                            $open = $p;
                        } else {
                            if ($open) {
                                $gapH = ($p['dt']->getTimestamp() - $open['dt']->getTimestamp()) / 3600;
                                if ($gapH > 0 && $gapH <= $MAX_GAP_HOURS) {
                                    $pushSession($open, $p);
                                    $open = null;
                                } else {
                                    $open = null;
                                }
                            }
                        }
                    }

                    // Aggregate to day
                    $days = [];
                    $isInSelectedMonth = fn(\Carbon\Carbon $c) => ((int)$c->month === $monthIndex) && ((int)$c->year === $year);

                    foreach ($sessions as $s) {
                        if (!$isInSelectedMonth($s['startDT'])) continue;
                        $key = $s['startDT']->toDateString();

                        if (!isset($days[$key])) {
                            $days[$key] = [
                                'firstInDT' => $s['startDT'],
                                'firstIn'   => $s['startTime'],
                                'lastOutDT' => $s['endDT'],
                                'lastOut'   => $s['endTime'],
                                'totalMin'  => 0,
                            ];
                        }
                        $days[$key]['totalMin'] += $s['minutes'];
                        if ($s['startDT']->lt($days[$key]['firstInDT'])) {
                            $days[$key]['firstInDT'] = $s['startDT'];
                            $days[$key]['firstIn']   = $s['startTime'];
                        }
                        if ($s['endDT']->gt($days[$key]['lastOutDT'])) {
                            $days[$key]['lastOutDT'] = $s['endDT'];
                            $days[$key]['lastOut']   = $s['endTime'];
                        }
                    }

                    // Possible open session this month
                    if ($open && $isInSelectedMonth($open['dt'])) {
                        $key  = $open['dt']->toDateString();
                        $now  = \Carbon\Carbon::now();
                        $mins = (int) round(($now->getTimestamp() - $open['dt']->getTimestamp()) / 60);
                        $cap  = 36 * 60;
                        if (!isset($days[$key])) {
                            $days[$key] = [
                                'firstInDT' => $open['dt'],
                                'firstIn'   => $open['time'],
                                'lastOutDT' => null,
                                'lastOut'   => null,
                                'totalMin'  => max(0, min($mins, $cap)),
                            ];
                        } else {
                            $days[$key]['firstInDT'] = $open['dt']->lt($days[$key]['firstInDT']) ? $open['dt'] : $days[$key]['firstInDT'];
                            $days[$key]['firstIn']   = $open['time'];
                            $days[$key]['totalMin'] += max(0, min($mins, $cap));
                        }
                    }

                    foreach ($days as $iso => $agg) {
                        if (!isset($bioTimeData[$iso])) continue;
                        $hoursDec = $agg['totalMin'] / 60;
                        $otDec    = max(0, $hoursDec - 8);
                        $bioTimeData[$iso] = [
                            'worked'    => $hoursDec > 0 ? 1 : 0,
                            'hours'     => number_format($hoursDec, 2, '.', ''),
                            'time_in'   => $agg['firstIn'] ?? null,
                            'time_out'  => $agg['lastOut'] ?? null,
                            'ot_hours'  => number_format($otDec, 2, '.', ''),
                        ];
                    }
                } catch (\Throwable $e) {
                    \Log::error('BioTime query failed', ['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()]);
                }
            }

            return response()->json([
                'workedDays'  => $workedDays,
                'bioTimeData' => $bioTimeData,
                'locum_month' => $monthName,
                'locum_year'  => $year,
                'ccbrt_code'  => $user->ccbrt_code,
                'locum_rate'  => (float)($locum->locumAgreement->locum_rate ?? 0),
            ]);
        } catch (\Throwable $e) {
            \Log::error('showWorkedDays failed', [
                'id' => $id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json(['message' => 'Failed to load details'], 500);
        }
    }


    public function fetchBioTimeData(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            \Log::error('Invalid user', ['user_id' => null]);
            return response()->json(['error' => 'Invalid user'], 401);
        }
        if (empty($user->ccbrt_code)) {
            \Log::info('No CCBRT code for user, returning empty BioTime data', ['user_id' => $user->id]);
            // build empty map for requested month below after we parse month/year
        }

        // --- Inputs ---
        $monthName = (string) $request->input('month');
        $year = (int) $request->input('year', date('Y'));

        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];
        $idx = array_search($monthName, $months, true);
        if ($idx === false) {
            \Log::error('Invalid month selected', ['month' => $monthName]);
            return response()->json(['error' => 'Invalid month selected'], 400);
        }
        $month = $idx + 1;

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        // Initialize empty map for every day of the target month
        $bioTimeData = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $bioTimeData[$d->toDateString()] = ['hours' => '0.00', 'worked' => '0'];
        }

        if (empty($user->ccbrt_code)) {
            // no code, return empty map
            return response()->json(['bioTimeData' => $bioTimeData]);
        }

        // --- DB connectivity check ---
        try {
            \DB::connection('bio')->getPdo();
        } catch (\Throwable $e) {
            \Log::error('BioTime DB connection failed', ['error' => $e->getMessage()]);
            return response()->json(['bioTimeData' => $bioTimeData]);
        }

        $empCode = trim($user->ccbrt_code);

        // --- Fetch raw punches with 1-day buffer either side to pair cross-midnight ---
        $from = $start->copy()->subDay()->startOfDay();
        $to   = $end->copy()->addDay()->endOfDay();

        try {
            // Detect columns
            $trxTable  = 'iclock_transaction';
            $pick = function (string $table, array $candidates) {
                foreach ($candidates as $c) {
                    if (\Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($table, $c)) return $c;
                }
                return null;
            };
            $colEmp  = $pick($trxTable, ['emp_code', 'emp_id', 'employee_code', 'user_id', 'userid', 'pin']) ?? 'emp_code';
            $colTime = $pick($trxTable, ['punch_time', 'checktime', 'log_time', 'time']) ?? 'punch_time';
            $colState = $pick($trxTable, ['punch_state']); // nullable

            $sql = "
            SELECT
                {$colTime}  AS punch_time
                " . ($colState ? ", {$colState} AS punch_state" : ", NULL::text AS punch_state") . "
            FROM {$trxTable}
            WHERE {$colEmp} = ?
              AND {$colTime} BETWEEN ? AND ?
            ORDER BY {$colTime} ASC
        ";

            $rows = \DB::connection('bio')->select($sql, [
                $empCode,
                $from->toDateTimeString(),
                $to->toDateTimeString()
            ]);

            // --- Normalize + pair into sessions (IN→OUT), night-shift OK ---
            $events = [];
            foreach ($rows as $r) {
                $dt  = new \DateTime($r->punch_time);
                $dir = 'UNK';
                if ($colState) {
                    // Adjust mapping if your devices are reversed
                    $ps = (string) $r->punch_state;
                    if ($ps === '0') $dir = 'IN';
                    elseif ($ps === '1') $dir = 'OUT';
                }
                $events[] = ['dt' => $dt, 'dir' => $dir];
            }
            usort($events, fn($a, $b) => $a['dt'] <=> $b['dt']);

            $sessions = [];
            $open = null;
            $MAX_GAP_HOURS = 36; // allow overnight duty up to 36h
            foreach ($events as $e) {
                $dir = $e['dir'];
                if ($dir === 'UNK') $dir = $open ? 'OUT' : 'IN';

                if ($dir === 'IN') {
                    if ($open) {
                        $gapH = ($e['dt']->getTimestamp() - $open['dt']->getTimestamp()) / 3600;
                        if ($gapH > 0 && $gapH <= $MAX_GAP_HOURS) {
                            $sessions[] = ['start' => $open['dt'], 'end' => $e['dt']];
                        }
                    }
                    $open = ['dt' => $e['dt']];
                } else { // OUT
                    if ($open) {
                        $gapH = ($e['dt']->getTimestamp() - $open['dt']->getTimestamp()) / 3600;
                        if ($gapH > 0 && $gapH <= $MAX_GAP_HOURS) {
                            $sessions[] = ['start' => $open['dt'], 'end' => $e['dt']];
                        }
                        $open = null;
                    }
                }
            }
            // note: if $open remains, it's an unfinished IN; we DO NOT count it into past days

            // --- Aggregate minutes by the session START date (anchor) ---
            $minsByDate = [];
            foreach ($sessions as $s) {
                $startDate = $s['start']->format('Y-m-d');
                $minutes = max(0, (int) round(($s['end']->getTimestamp() - $s['start']->getTimestamp()) / 60));

                // Only count sessions whose START falls inside the selected month
                if ($startDate >= $start->toDateString() && $startDate <= $end->toDateString()) {
                    $minsByDate[$startDate] = ($minsByDate[$startDate] ?? 0) + $minutes;
                }
            }

            // --- Fill response map (sum split shifts, convert to hours.dec) ---
            foreach ($minsByDate as $iso => $mins) {
                $hours = $mins / 60;
                $bioTimeData[$iso] = [
                    'hours'  => number_format($hours, 2, '.', ''),
                    'worked' => $hours > 0 ? '1' : '0',
                ];
            }

            return response()->json(['bioTimeData' => $bioTimeData]);
        } catch (\Throwable $e) {
            \Log::error('Error running night-shift-aware BioTime query', ['error' => $e->getMessage()]);
            return response()->json(['bioTimeData' => $bioTimeData]);
        }
    }

    public function store(Request $request)
    {
        if (!\Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to create a locum request.');
        }

        // Check deadline: submissions close on the configured day of the current month
        $today = now('Africa/Dar_es_Salaam');
        $previous = $today->copy()->subMonth();
        $defaultClaimMonth = $previous->format('F Y');
        $deadlineDay = (int) SettingsController::getSetting('locum_submission_deadline', 5);
        $deadline = $today->copy()->startOfMonth()->addDays($deadlineDay - 1)->endOfDay();

        if ($today->gt($deadline)) {
            return redirect()->route('locum-requests.index')
                ->with('error', "Submission for {$defaultClaimMonth} is closed. The deadline was {$deadline->format('j F Y')}.");
        }

        // 1) Validate input (entries per day)
        $validated = $request->validate([
            'locum_agreement_id'            => ['required', 'exists:locum_agreements,id'],
            'locum_month'                   => ['required', Rule::in([
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December'
            ])],
            'locum_year'                    => ['required', 'integer', 'min:2000', 'max:2100'],

            'worked_days'                   => ['required', 'array'],
            'worked_days.*.worked'          => ['nullable', 'boolean'],

            // entries per worked day
            'worked_days.*.entries'                             => ['nullable', 'array'], // required when worked = 1 (checked below)
            'worked_days.*.entries.*.shift_id'                  => ['required', 'integer', 'exists:shift_settings,id'],
            'worked_days.*.entries.*.hours'                     => ['required', 'numeric', 'min:0.01', 'max:36'],
            'worked_days.*.entries.*.platform_id'               => ['required', 'integer', 'exists:platforms,id'],
            'worked_days.*.entries.*.unit_id'                   => ['nullable', 'integer', 'exists:units,id'],

            'description'                                       => ['nullable', 'string', 'max:2000'],
        ]);

        $tz   = 'Africa/Dar_es_Salaam';
        $now  = \Carbon\Carbon::now($tz);
        $user = \Auth::user();

        // 2) Validate month selection - prevent future months and require reason for previous months
        $selectedMonth = $validated['locum_month'];
        $selectedYear = (int)$validated['locum_year'];
        $monthIndex = array_search($selectedMonth, [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ]);
        $selectedDate = \Carbon\Carbon::create($selectedYear, $monthIndex + 1, 1, 0, 0, 0, $tz);
        $currentMonthStart = $now->copy()->startOfMonth();

        // Prevent claiming for future months
        if ($selectedDate->gt($currentMonthStart)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['locum_month' => 'You cannot claim for future months. Please select a month that has already passed.']);
        }


        // 3) Resolve target year from first selected day if not provided
        $selectedIsos = array_keys(array_filter(
            $validated['worked_days'] ?? [],
            fn($r) => (isset($r['worked']) && (int)$r['worked'] === 1)
        ));
        if (empty($validated['locum_year']) && !empty($selectedIsos)) {
            try {
                $firstPicked = \Carbon\Carbon::createFromFormat('Y-m-d', $selectedIsos[0], $tz);
                $targetYear  = (int)$firstPicked->year;
            } catch (\Throwable $e) {
                $targetYear  = (int)$now->copy()->subMonth()->year;
            }
        } else {
            $targetYear = $validated['locum_year']
                ? (int)$validated['locum_year']
                : (int)$now->copy()->subMonth()->year;
        }

        // 3) Lock month & year
        try {
            $targetMonthCarbon = \Carbon\Carbon::parse(sprintf('%s %d', $validated['locum_month'], $targetYear), $tz);
        } catch (\Throwable $e) {
            return back()->withErrors(['locum_month' => 'Invalid month/year selection.'])->withInput();
        }
        $selMonthNum = (int) $targetMonthCarbon->format('n');
        $selYearNum  = (int) $targetMonthCarbon->format('Y');

        // 4) Enforce single request per (user, month, year)
        $hasYearColumn = \Schema::hasColumn('locum_requests', 'locum_year');
        $alreadyExists = $hasYearColumn
            ? \App\Models\LocumRequest::where('user_id', $user->id)
            ->where('locum_month', $validated['locum_month'])
            ->where('locum_year', $selYearNum)
            ->exists()
            : \App\Models\LocumRequest::where('user_id', $user->id)
            ->where('locum_month', $validated['locum_month'])
            ->whereYear('created_at', $selYearNum)
            ->exists();

        if ($alreadyExists) {
            return back()->withErrors([
                'locum_limit' => "You have already submitted a locum request for {$validated['locum_month']} {$selYearNum}. Only one is allowed per month."
            ])->withInput();
        }

        // 5) Agreement + rate
        // Use the agreement selected by user; must be HR-approved and either valid or expired within grace period
        $today = \Carbon\Carbon::today();
        $agreement = \App\Models\LocumAgreement::where('id', $validated['locum_agreement_id'])
            ->where('user_id', $user->id)
            ->where('status', 2)
            ->first();

        if (!$agreement) {
            return back()->withErrors(['locum_agreement_id' => 'The selected agreement is not valid or not approved by HR.'])->withInput();
        }

        // Allow expired agreement only when admin "use until" grace is set and today <= use_until
        $agreementEnd = $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->endOfDay() : null;
        if ($agreementEnd && $today->gt($agreementEnd)) {
            $useUntilRaw = SettingsController::getSetting('locum_expired_agreement_use_until', '');
            if ($useUntilRaw === '' || $useUntilRaw === null) {
                return back()->withErrors(['locum_agreement_id' => 'Your locum agreement has expired. Please create a new agreement to continue submitting claims.'])->withInput();
            }
            try {
                $useUntil = \Carbon\Carbon::parse($useUntilRaw)->endOfDay();
                if ($today->gt($useUntil)) {
                    return back()->withErrors(['locum_agreement_id' => 'The grace period for using your expired agreement has ended. Please create a new agreement.'])->withInput();
                }
            } catch (\Exception $e) {
                return back()->withErrors(['locum_agreement_id' => 'Your locum agreement has expired. Please create a new agreement.'])->withInput();
            }
        }

        $rate = (float) ($agreement->locum_rate ?? 0);

        // 6) Normalize & validate selected entries (must be inside locked month/year)
        $rows = []; // each = {date, shift_id, platform_id, unit_id, hours}
        foreach ($validated['worked_days'] as $isoDate => $day) {
            try {
                $date = \Carbon\Carbon::createFromFormat('Y-m-d', $isoDate, $tz);
            } catch (\Throwable $e) {
                \Log::warning("Invalid date key in worked_days: {$isoDate}");
                return back()->withErrors(['worked_days' => "Invalid date: {$isoDate}"])->withInput();
            }

            if ((int)$date->format('n') !== $selMonthNum || (int)$date->format('Y') !== $selYearNum) {
                return back()->withErrors([
                    'worked_days' => "Date {$date->format('d M Y')} is not in {$validated['locum_month']} {$selYearNum}."
                ])->withInput();
            }

            $worked = (int)($day['worked'] ?? 0);
            if ($worked !== 1) continue;

            $entries = $day['entries'] ?? [];
            if (!is_array($entries) || count($entries) === 0) {
                return back()->withErrors([
                    'worked_days' => "Please add at least one entry for {$date->format('d M Y')}."
                ])->withInput();
            }

            foreach ($entries as $ix => $ent) {
                $hrs = (float) ($ent['hours'] ?? 0);
                if ($hrs <= 0) {
                    return back()->withErrors([
                        'worked_days' => "Hours must be greater than 0 for {$date->format('d M Y')} (entry #" . ($ix + 1) . ")."
                    ])->withInput();
                }

                $rows[] = [
                    'iso_date'    => $isoDate,
                    'date_label'  => $date->format('Y-m-d'),
                    'weekday'     => (int)$date->format('w'),
                    'shift_id'    => (int)$ent['shift_id'],
                    'platform_id' => (int)$ent['platform_id'],
                    'unit_id'     => isset($ent['unit_id']) ? (int)$ent['unit_id'] : null,
                    'hours'       => round($hrs, 2),
                ];
            }
        }

        if (empty($rows)) {
            return back()->withErrors(['worked_days' => 'Please select at least one worked day and add at least one entry.'])->withInput();
        }

        // 7) Platform / Unit-specific hours (8/12) & names
        $platformHours = \App\Models\Platform::pluck('locum_hours', 'id'); // id => 8|12 (default per platform)
        $unitHours     = \App\Models\Unit::pluck('locum_hours', 'id');     // id => nullable override per unit
        $shiftNames    = \App\Models\ShiftSetting::pluck('name', 'id');    // id => name
        $platformNames = \App\Models\Platform::pluck('name', 'id');        // id => name
        $unitNames     = \App\Models\Unit::pluck('name', 'id');            // id => name

        // Group by SHIFT × PLATFORM × UNIT (if unit specified), or SHIFT × PLATFORM (if no unit)
        // This ensures entries for the same unit/platform are combined before calculating locums
        // Different units on the same platform are calculated separately
        $byShiftPlatUnit = []; // [shift_id][platform_id][unit_id_or_null] => ['hours' => float, 'effectiveReq' => int]
        foreach ($rows as $r) {
            $sid = $r['shift_id'];
            $pid = $r['platform_id'];
            $uid = $r['unit_id'] ?? null; // null if no unit selected

            // Determine effective requirement (unit hours override platform hours)
            $effectiveReq = (int) ($uid && isset($unitHours[$uid]) && $unitHours[$uid]
                ? $unitHours[$uid]
                : ($platformHours[$pid] ?? 8));
            if ($effectiveReq <= 0) {
                $effectiveReq = 8;
            }

            $hrsRow = round((float) $r['hours'], 2);

            // Use unit_id as key if present, otherwise use 'null' as key for platform-only grouping
            $unitKey = $uid ?? 'null';

            if (!isset($byShiftPlatUnit[$sid][$pid][$unitKey])) {
                $byShiftPlatUnit[$sid][$pid][$unitKey] = [
                    'hours' => 0.0,
                    'effectiveReq' => $effectiveReq,
                    'unit_id' => $uid
                ];
            }

            // Sum hours first (don't calculate locums per row)
            $byShiftPlatUnit[$sid][$pid][$unitKey]['hours'] += $hrsRow;
            // Keep the effective requirement (prefer unit override if available)
            if ($uid && isset($unitHours[$uid]) && $unitHours[$uid]) {
                $byShiftPlatUnit[$sid][$pid][$unitKey]['effectiveReq'] = (int)$unitHours[$uid];
            }
        }

        // Now calculate locums from the total hours for each Shift×Platform×Unit group
        // Then aggregate by Shift×Platform for display
        $byShiftPlat = []; // [shift_id][platform_id] => ['hours' => float, 'locums' => int, 'remainder' => float]
        foreach ($byShiftPlatUnit as $sid => $platMap) {
            foreach ($platMap as $pid => $unitMap) {
                $platformTotalHours = 0.0;
                $platformTotalLocums = 0;
                $platformTotalRemainder = 0.0;

                foreach ($unitMap as $unitKey => $data) {
                    $totalHours = round((float)($data['hours'] ?? 0), 2);
                    $effectiveReq = (int)($data['effectiveReq'] ?? 8);
                    if ($effectiveReq <= 0) {
                        $effectiveReq = 8;
                    }

                    // Calculate locums from total hours for this specific unit/platform combination
                    $locums = (int) floor($totalHours / $effectiveReq);
                    $remainder = round($totalHours - ($locums * $effectiveReq), 2);

                    // Aggregate for the platform
                    $platformTotalHours += $totalHours;
                    $platformTotalLocums += $locums;
                    $platformTotalRemainder += $remainder;
                }

                if (!isset($byShiftPlat[$sid][$pid])) {
                    $byShiftPlat[$sid][$pid] = [
                        'hours' => 0.0,
                        'locums' => 0,
                        'remainder' => 0.0
                    ];
                }

                $byShiftPlat[$sid][$pid]['hours'] = round($platformTotalHours, 2);
                $byShiftPlat[$sid][$pid]['locums'] = $platformTotalLocums;
                $byShiftPlat[$sid][$pid]['remainder'] = round($platformTotalRemainder, 2);
            }
        }

        // Build per-shift breakdown using platform thresholds
        $perShiftBreakdown = [
            'required_hours' => 'per_platform',
            'shifts'         => [],
            'grand'          => ['total_hours' => 0.0, 'locums' => 0, 'amount' => 0.0, 'remainder' => 0.0],
        ];

        $grandHours = 0.0;
        $grandLocums = 0;
        $grandRemainderHours = 0.0;

        foreach ($byShiftPlat as $sid => $platMap) {
            $shiftTotalHours = 0.0;
            $shiftLocums     = 0;
            $shiftRemainder  = 0.0;

            $platformsBreak = [];
            foreach ($platMap as $pid => $hrs) {
                $reqHrs = (int) ($platformHours[$pid] ?? 8);
                $hrsVal = round((float) ($hrs['hours'] ?? 0), 2);
                $locums = (int) ($hrs['locums'] ?? 0);
                $remainder = round((float) ($hrs['remainder'] ?? 0), 2);

                $platformsBreak[] = [
                    'platform_id'  => $pid,
                    'platform'     => (string)($platformNames[$pid] ?? 'Unknown'),
                    'required'     => $reqHrs,
                    'hours'        => $hrsVal,
                    'locums'       => $locums,
                    'remainder'    => $remainder,
                ];

                $shiftTotalHours += $hrsVal;
                $shiftLocums     += $locums;
                $shiftRemainder  += $remainder;
            }

            $perShiftBreakdown['shifts'][(string)$sid] = [
                'shift_id'     => $sid,
                'name'         => (string)($shiftNames[$sid] ?? 'Unknown'),
                'platforms'    => $platformsBreak,
                'total_hours'  => round($shiftTotalHours, 2),
                'locums'       => $shiftLocums,
                'remainder'    => round($shiftRemainder, 2),
                'amount'       => round($shiftLocums * $rate, 2),
            ];

            $grandHours       += $shiftTotalHours;
            $grandLocums      += $shiftLocums;
            $grandRemainderHours += $shiftRemainder;
        }

        $perShiftBreakdown['grand']['total_hours'] = round($grandHours, 2);
        $perShiftBreakdown['grand']['locums']      = (int)$grandLocums;
        $perShiftBreakdown['grand']['amount']      = round($grandLocums * $rate, 2);
        $perShiftBreakdown['grand']['remainder']   = round($grandRemainderHours, 2);

        // "Eligible days" - Group by date first, then check if total hours per day meet requirement
        $eligibleDaysCount = 0;
        $eligibleHoursSum  = 0.0;

        // Group entries by date
        $hoursByDate = [];
        foreach ($rows as $r) {
            $dateKey = $r['iso_date'];
            if (!isset($hoursByDate[$dateKey])) {
                $hoursByDate[$dateKey] = [
                    'total_hours' => 0.0,
                    'platforms' => []
                ];
            }
            $hoursByDate[$dateKey]['total_hours'] += $r['hours'];

            // Track platform requirements for this date
            $pid = $r['platform_id'];
            $uid = $r['unit_id'] ?? null;
            $req = (int) ($uid && isset($unitHours[$uid]) && $unitHours[$uid]
                ? $unitHours[$uid]
                : ($platformHours[$pid] ?? 8));
            if ($req <= 0) {
                $req = 8;
            }
            if (!isset($hoursByDate[$dateKey]['platforms'][$pid])) {
                $hoursByDate[$dateKey]['platforms'][$pid] = [
                    'required' => $req,
                    'hours' => 0.0
                ];
            }
            $hoursByDate[$dateKey]['platforms'][$pid]['hours'] += $r['hours'];
        }

        // Count eligible days: a day is eligible if at least one platform on that day meets its requirement
        foreach ($hoursByDate as $dateKey => $dateData) {
            $dayIsEligible = false;
            $dayTotalHours = 0.0;

            foreach ($dateData['platforms'] as $pid => $platData) {
                $dayTotalHours += $platData['hours'];
                // Day is eligible if any platform on that day meets its requirement
                if ($platData['hours'] >= $platData['required']) {
                    $dayIsEligible = true;
                }
            }

            if ($dayIsEligible) {
                $eligibleDaysCount += 1;
                $eligibleHoursSum += $dayTotalHours;
            }
        }
        $eligibleHoursSum = round($eligibleHoursSum, 2);

        // Use grand total hours (all hours claimed) instead of eligible hours for total_hours
        // This ensures consistency between the summary table and the approver view
        $totalHoursClaimed = round($grandHours, 2);

        // Distinct ids actually selected
        $distinctUnitIds     = collect($rows)->pluck('unit_id')->filter()->unique()->values()->all();
        $distinctPlatformIds = collect($rows)->pluck('platform_id')->filter()->unique()->values()->all();

        \DB::beginTransaction();
        try {
            // Decide flow based on requestor's department settings
            $dept       = $user->department; // ensure relation exists on User
            $flowThree  = (bool) ($dept->has_three_level_approval ?? false);     // LM → HEC → HR
            $flowPlat   = (bool) ($dept->has_incharge_platform_flow ?? false);   // In-Charge → Platform → LM → HR
            $flowILHH   = (bool) ($dept->has_incharge_lm_hec_flow ?? false);     // In-Charge → LM → HEC → HR
            $flowStd    = !$flowThree && !$flowPlat && !$flowILHH;               // LM → HR (default)

            $approvalFlowLabel = $flowPlat
                ? 'incharge_platform'
                : ($flowILHH
                    ? 'incharge_lm_hec'
                    : ($flowThree ? 'three_level' : 'standard'));

            // Persist request
            $locumRequest = \App\Models\LocumRequest::create([
                'user_id'              => $user->id,
                'locum_agreement_id'   => $validated['locum_agreement_id'],
                'rate_used'            => $rate, // Store the rate used at creation time (for rejected claims)
                'locum_month'          => $validated['locum_month'],
                'locum_year'           => $selYearNum,

                'hours_per_locum'      => null,

                'number_of_days'       => $eligibleDaysCount,
                'total_hours'          => $totalHoursClaimed,
                'grand_total_locums'   => (int)($perShiftBreakdown['grand']['locums'] ?? 0),
                'total_amount'         => (float)($perShiftBreakdown['grand']['amount'] ?? 0),
                'total_amount_payable' => (float)($perShiftBreakdown['grand']['amount'] ?? 0),

                'per_shift_breakdown'  => $perShiftBreakdown,
                'worked_days'          => $validated['worked_days'],
                'description'          => $validated['description'] ?? null,
                'unit_ids'             => !empty($distinctUnitIds) ? array_values(array_filter($distinctUnitIds)) : [],
                'platform_ids'         => !empty($distinctPlatformIds) ? array_values(array_filter($distinctPlatformIds)) : [],
                'approval_flow'        => $approvalFlowLabel,
            ]);


            // Create workflow shell
            $workflow = \App\Models\Workflow::create([
                'user_id'             => $user->id,
                'locum_request_id'    => $locumRequest->id,
                'work_flow_status'    => 0,
                'work_flow_completed' => 0,
            ]);

            // Helper: resolve LM for this department
            $resolveLM = function (int $deptId): ?\App\Models\User {
                return \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
                    ->where('deptId', $deptId)
                    ->first();
            };

            // Check requester's role to determine routing
            $isIncharge = $user->hasAnyRole(['incharge', 'in-charge']);
            $isPlatformManager = $user->hasAnyRole(['Platform-Manager', 'platform-manager']);
            $isLineManager = $user->hasAnyRole(['line-manager', 'line_manager']);
            $isNormalUser = !$isIncharge && !$isPlatformManager && !$isLineManager;

            // Helper to resolve platform managers from platform IDs
            $resolvePlatformManagersFromIds = function (array $platformIds): array {
                if (empty($platformIds)) return [];

                $platforms = \App\Models\Platform::whereIn('id', $platformIds)
                    ->whereNotNull('manager_user_id')
                    ->get(['id', 'name', 'manager_user_id']);

                $managerIds = $platforms->pluck('manager_user_id')->filter()->unique()->values()->all();
                if (empty($managerIds)) return [];

                $managers = \App\Models\User::whereIn('id', $managerIds)->get();
                return $managers->all();
            };

            // Ensure arrays are properly formatted (ensure they're arrays, not null)
            $unitIdsArray = !empty($distinctUnitIds) ? array_values(array_filter($distinctUnitIds, fn($id) => !is_null($id))) : [];
            $platformIdsArray = !empty($distinctPlatformIds) ? array_values(array_filter($distinctPlatformIds, fn($id) => !is_null($id))) : [];

            // Ensure grand_total_locums is calculated correctly - recalculate if needed
            $grandTotalLocums = isset($perShiftBreakdown['grand']['locums']) ? (int)$perShiftBreakdown['grand']['locums'] : 0;
            $totalAmount = isset($perShiftBreakdown['grand']['amount']) ? (float)$perShiftBreakdown['grand']['amount'] : 0.0;

            // Double-check calculation - ensure we're using the correct value
            // The perShiftBreakdown should already have the correct values, but verify
            if ($grandTotalLocums === 0 && isset($perShiftBreakdown['grand']['locums']) && $perShiftBreakdown['grand']['locums'] > 0) {
                $grandTotalLocums = (int)$perShiftBreakdown['grand']['locums'];
                $totalAmount = (float)$perShiftBreakdown['grand']['amount'];
            }

            // Final validation: if still 0, check if we have any hours worked
            if ($grandTotalLocums === 0 && $eligibleHoursSum > 0) {
                \Log::warning('grand_total_locums is 0 but we have hours worked', [
                    'eligible_hours_sum' => $eligibleHoursSum,
                    'per_shift_breakdown' => $perShiftBreakdown,
                ]);
            }

            // Log for debugging
            \Log::info('LocumRequest creation data', [
                'user_id' => $user->id,
                'grand_total_locums' => $grandTotalLocums,
                'total_amount' => $totalAmount,
                'unit_ids_count' => count($unitIdsArray),
                'platform_ids_count' => count($platformIdsArray),
                'per_shift_breakdown_grand' => $perShiftBreakdown['grand'] ?? null,
            ]);

            // Update the locum request with all data immediately after creation to ensure it's saved
            // Use fresh() to reload the model and ensure we're updating the correct record
            $locumRequest->fresh();
            $updateResult = $locumRequest->update([
                'unit_ids' => $unitIdsArray,
                'platform_ids' => $platformIdsArray,
                'grand_total_locums' => $grandTotalLocums,
                'total_amount' => $totalAmount,
            ]);

            \Log::info('LocumRequest update result', [
                'locum_request_id' => $locumRequest->id,
                'update_result' => $updateResult,
                'grand_total_locums' => $locumRequest->grand_total_locums,
                'total_amount' => $locumRequest->total_amount,
            ]);

            // Seed first approver(s) based on requester role and department flow
            if ($isIncharge) {
                // In-Charge requests: Skip In-Charge step, go directly to Platform Manager(s)
                $managers = $resolvePlatformManagersFromIds($distinctPlatformIds);

                if (!empty($managers)) {
                    foreach ($managers as $manager) {
                        \App\Models\WorkFlowHistory::create([
                            'work_flow_id'         => $workflow->id,
                            'forwarded_by'         => $user->id,
                            'attended_by'          => $manager->id,
                            'step_name'            => 'Platform Manager Approval',
                            'action_taken'         => 'Forwarded',
                            'status'               => 0,
                            'remark'               => 'In-Charge request - Awaiting Platform Manager approval.',
                            'locum_request_status' => 2, // Platform Manager stage
                        ]);
                    }
                } else {
                    // No Platform Manager: go to Line Manager
                    $lm = $resolveLM((int) $user->deptId);
                    if ($lm && $lm->id !== $user->id) {
                        \App\Models\WorkFlowHistory::create([
                            'work_flow_id'         => $workflow->id,
                            'forwarded_by'         => $user->id,
                            'attended_by'          => $lm->id,
                            'step_name'            => 'Line Manager Approval',
                            'action_taken'         => 'Forwarded',
                            'status'               => 0,
                            'remark'               => 'In-Charge request - Awaiting Line Manager approval (no Platform Manager).',
                            'locum_request_status' => 3, // LM stage
                        ]);
                    } else {
                        // No LM: go to HR
                        $hr = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                            ->when(\Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                            ->first();

                        if (!$hr) {
                            \DB::rollBack();
                            return back()->withErrors([
                                'worked_days' => 'No Platform Manager, Line Manager, or HR approver configured.'
                            ])->withInput();
                        }

                        \App\Models\WorkFlowHistory::create([
                            'work_flow_id'         => $workflow->id,
                            'forwarded_by'         => $user->id,
                            'attended_by'          => $hr->id,
                            'step_name'            => 'HR Approval',
                            'action_taken'         => 'Forwarded',
                            'status'               => 0,
                            'remark'               => 'In-Charge request - Awaiting HR approval (no Platform Manager or LM).',
                            'locum_request_status' => 5,
                        ]);
                    }
                }
            } elseif ($isPlatformManager) {
                // Platform Manager requests: Skip In-Charge and Platform Manager steps, go directly to Line Manager
                $lm = $resolveLM((int) $user->deptId);
                if ($lm && $lm->id !== $user->id) {
                    \App\Models\WorkFlowHistory::create([
                        'work_flow_id'         => $workflow->id,
                        'forwarded_by'         => $user->id,
                        'attended_by'          => $lm->id,
                        'step_name'            => 'Line Manager Approval',
                        'action_taken'         => 'Forwarded',
                        'status'               => 0,
                        'remark'               => 'Platform Manager request - Awaiting Line Manager approval.',
                        'locum_request_status' => 3, // LM stage
                    ]);
                } else {
                    // No LM: go to HR
                    $hr = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                        ->when(\Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                        ->first();

                    if (!$hr) {
                        \DB::rollBack();
                        return back()->withErrors([
                            'worked_days' => 'No Line Manager or HR approver configured for your department.'
                        ])->withInput();
                    }

                    \App\Models\WorkFlowHistory::create([
                        'work_flow_id'         => $workflow->id,
                        'forwarded_by'         => $user->id,
                        'attended_by'          => $hr->id,
                        'step_name'            => 'HR Approval',
                        'action_taken'         => 'Forwarded',
                        'status'               => 0,
                        'remark'               => 'Platform Manager request - Awaiting HR approval (no LM configured).',
                        'locum_request_status' => 5,
                    ]);
                }
            } elseif ($isLineManager) {
                // Line Manager requests: Skip In-Charge, Platform Manager, and Line Manager steps, go directly to HEC
                // IMPORTANT: Line managers should NEVER approve their own requests - always route to HEC directly
                $dept = $user->department;

                // Exclude the line manager from being selected as HEC approver (in case they're also a HEC member)
                $hecApprover = $this->resolveHecApproverForDept($dept, [$user->id]);

                if ($hecApprover && $hecApprover->id !== $user->id) {
                    // Route directly to HEC member (skip all intermediate steps including Line Manager approval)
                    \App\Models\WorkFlowHistory::create([
                        'work_flow_id'         => $workflow->id,
                        'forwarded_by'         => $user->id,
                        'attended_by'          => $hecApprover->id,
                        'step_name'            => 'HEC Approval',
                        'action_taken'         => 'Forwarded',
                        'status'               => 0,
                        'remark'               => 'Line Manager request - Awaiting HEC approval (skipped Line Manager approval step).',
                        'locum_request_status' => 4, // HEC stage
                    ]);
                } else {
                    // No HEC or HEC is the same as requester: go to HR
                    $hr = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                        ->when(\Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                        ->where('id', '!=', $user->id) // Ensure HR is not the line manager themselves
                        ->first();

                    if (!$hr) {
                        \DB::rollBack();
                        return back()->withErrors([
                            'worked_days' => 'No HEC member or HR approver configured for your department.'
                        ])->withInput();
                    }

                    \App\Models\WorkFlowHistory::create([
                        'work_flow_id'         => $workflow->id,
                        'forwarded_by'         => $user->id,
                        'attended_by'          => $hr->id,
                        'step_name'            => 'HR Approval',
                        'action_taken'         => 'Forwarded',
                        'status'               => 0,
                        'remark'               => 'Line Manager request - Awaiting HR approval (no HEC configured, skipped Line Manager approval step).',
                        'locum_request_status' => 5,
                    ]);
                }
            } else {
                // Normal user requests: Follow department's approval flow settings
                if ($flowPlat || $flowILHH) {
                    // Starts with In-Charge
                    $units = \App\Models\Unit::whereIn('id', $distinctUnitIds)->get(['id', 'name', 'incharge_user_id']);
                    $inchargeUserIds = [];

                    foreach ($units as $u) {
                        if (!$u->incharge_user_id) {
                            \DB::rollBack();
                            return back()->withErrors([
                                'worked_days' => "Unit \"{$u->name}\" has no assigned in-charge."
                            ])->withInput();
                        }

                        $incharge = \App\Models\User::where('id', $u->incharge_user_id)
                            ->whereHas('roles', fn($q) => $q->where('name', 'incharge'))
                            ->first();

                        if (!$incharge) {
                            \DB::rollBack();
                            return back()->withErrors([
                                'worked_days' => "Assigned user for unit \"{$u->name}\" is not an In-Charge."
                            ])->withInput();
                        }

                        \App\Models\WorkFlowHistory::create([
                            'work_flow_id'         => $workflow->id,
                            'forwarded_by'         => $user->id,
                            'attended_by'          => $incharge->id,
                            'step_name'            => 'Incharge Approval',
                            'action_taken'         => 'Forwarded',
                            'status'               => 0,
                            'remark'               => 'Awaiting in-charge approval (unit selected in request).',
                            'locum_request_status' => 1, // pending in-charge
                        ]);

                        $inchargeUserIds[] = $incharge->id;
                    }

                    $inchargeIdsArray = !empty($inchargeUserIds) ? array_values(array_unique($inchargeUserIds)) : [];

                    // Update incharge_user_ids along with other fields to ensure all data is saved
                    $locumRequest->fresh();
                    $locumRequest->update([
                        'incharge_user_ids' => $inchargeIdsArray,
                        'unit_ids' => $unitIdsArray,
                        'platform_ids' => $platformIdsArray,
                        'grand_total_locums' => $grandTotalLocums,
                        'total_amount' => $totalAmount,
                    ]);

                    \Log::info('Updated incharge_user_ids and other fields for normal user flow', [
                        'locum_request_id' => $locumRequest->id,
                        'incharge_user_ids' => $inchargeIdsArray,
                        'grand_total_locums' => $locumRequest->grand_total_locums,
                        'total_amount' => $locumRequest->total_amount,
                        'unit_ids' => $locumRequest->unit_ids,
                        'platform_ids' => $locumRequest->platform_ids,
                    ]);
                } else {
                    // Starts with Line Manager (Standard or Three-Level)
                    // IMPORTANT: If requester is a line manager, skip Line Manager approval and go to HEC
                    if ($isLineManager) {
                        // Line manager creating request - should have been caught earlier, but as safeguard, route to HEC
                        $dept = $user->department;
                        $hecApprover = $this->resolveHecApproverForDept($dept, [$user->id]);

                        if ($hecApprover && $hecApprover->id !== $user->id) {
                            \App\Models\WorkFlowHistory::create([
                                'work_flow_id'         => $workflow->id,
                                'forwarded_by'         => $user->id,
                                'attended_by'          => $hecApprover->id,
                                'step_name'            => 'HEC Approval',
                                'action_taken'         => 'Forwarded',
                                'status'               => 0,
                                'remark'               => 'Line Manager request - Awaiting HEC approval (safeguard: skipped Line Manager approval).',
                                'locum_request_status' => 4, // HEC stage
                            ]);
                        } else {
                            // No HEC: go to HR
                            $hr = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                ->when(\Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                ->where('id', '!=', $user->id)
                                ->first();

                            if ($hr) {
                                \App\Models\WorkFlowHistory::create([
                                    'work_flow_id'         => $workflow->id,
                                    'forwarded_by'         => $user->id,
                                    'attended_by'          => $hr->id,
                                    'step_name'            => 'HR Approval',
                                    'action_taken'         => 'Forwarded',
                                    'status'               => 0,
                                    'remark'               => 'Line Manager request - Awaiting HR approval (safeguard: no HEC, skipped Line Manager approval).',
                                    'locum_request_status' => 5,
                                ]);
                            } else {
                                \DB::rollBack();
                                return back()->withErrors([
                                    'worked_days' => 'No HEC member or HR approver configured for your department.'
                                ])->withInput();
                            }
                        }
                    } else {
                        // Normal user: route to Line Manager
                        $lm = $resolveLM((int) $user->deptId);
                        if ($lm && $lm->id !== $user->id) {
                            \App\Models\WorkFlowHistory::create([
                                'work_flow_id'         => $workflow->id,
                                'forwarded_by'         => $user->id,
                                'attended_by'          => $lm->id,
                                'step_name'            => 'Line Manager Approval',
                                'action_taken'         => 'Forwarded',
                                'status'               => 0,
                                'remark'               => 'Awaiting Line Manager approval.',
                                'locum_request_status' => 3, // LM stage
                            ]);
                        } else {
                            // No LM: seed HR directly
                            $hr = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                ->when(\Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                ->first();

                            if (!$hr) {
                                \DB::rollBack();
                                return back()->withErrors([
                                    'worked_days' => 'No Line Manager or HR approver configured for your department.'
                                ])->withInput();
                            }

                            \App\Models\WorkFlowHistory::create([
                                'work_flow_id'         => $workflow->id,
                                'forwarded_by'         => $user->id,
                                'attended_by'          => $hr->id,
                                'step_name'            => 'HR Approval',
                                'action_taken'         => 'Forwarded',
                                'status'               => 0,
                                'remark'               => 'Awaiting HR approval (no LM configured).',
                                'locum_request_status' => 5,
                            ]);
                        }
                    }
                }
            }

            \DB::commit();

            // === Send email to first approver(s) ===
            try {
                $workflow->load('histories', 'user'); // ensure relations for the mailable

                // Get the first pending approver(s)
                $firstApprovers = $workflow->histories()
                    ->where('status', 0)
                    ->where('action_taken', 'Forwarded')
                    ->get();

                foreach ($firstApprovers as $history) {
                    $approver = \App\Models\User::find($history->attended_by);
                    if ($approver && $approver->email) {
                        Mail::to($approver->email)->queue(
                            new \App\Mail\LocumApprovalRequest(
                                locumRequest: $locumRequest,
                                workflow: $workflow,
                                submitter: $user,
                                approver: $approver
                            )
                        );
                        \Log::info('Sent LocumApprovalRequest email (initial submission)', [
                            'workflow_id' => $workflow->id,
                            'to'          => $approver->email,
                            'step'        => $history->step_name,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('LocumApprovalRequest send failed (initial submission)', [
                    'workflow_id' => $workflow->id ?? null,
                    'error'       => $e->getMessage(),
                ]);
                // Do not fail the request just because email failed
            }

            // Determine first approver text based on requester role
            $firstHopText = 'approver';
            if ($isIncharge) {
                $firstHopText = 'Platform Manager(s)';
            } elseif ($isPlatformManager) {
                $firstHopText = 'Line Manager';
            } elseif ($isLineManager) {
                $firstHopText = 'HEC member';
            } elseif ($flowPlat || $flowILHH) {
                $firstHopText = 'unit in-charge(s)';
            } else {
                $firstHopText = 'Line Manager';
            }

            return redirect()->route('locum-requests.index')
                ->with('success', "Locum claim for {$validated['locum_month']} {$selYearNum} submitted to {$firstHopText} for approval.");
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('Store locum request failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Failed to submit: ' . $e->getMessage()])->withInput();
        }
    }


    public function createForStaff()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'You must be logged in to create a locum request.');
        }

        if (!$user->hasRole('incharge')) {
            return redirect()->route('locum-requests.create')->with('error', 'Only incharge users can claim locum for staff.');
        }

        // Only users in this in-charge's department WITH an active HR-approved agreement (status = 2)
        $users = User::where('deptId', $user->deptId)
            ->whereHas('locumAgreements', function ($query) {
                $query->where('has_contract', true)
                    ->whereNull('rejection_status')
                    ->where(function ($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                    })
                    ->where('status', 2); // Only HR-approved agreements
            })
            ->with(['locumAgreements' => function ($query) {
                $query->where('has_contract', true)
                    ->whereNull('rejection_status')
                    ->where(function ($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                    })
                    ->where('status', 2) // Only HR-approved agreements
                    ->orderByDesc('created_at');
            }])
            ->get();

        if ($users->isEmpty()) {
            return redirect()->route('locum-requests.index')->with('error', 'No users with active HR-approved locum agreements found in your department. Only HR-approved agreements can be used for locum requests.');
        }

        // Last 12 months including current, e.g. "September 2025"
        $months = collect(range(0, 11))
            ->map(fn($i) => now()->subMonths($i)->format('F Y'))
            ->unique()
            ->sortByDesc(fn($month) => Carbon::createFromFormat('F Y', $month)->timestamp)
            ->values()
            ->toArray();

        Log::info('Generated months for dropdown:', ['months' => $months]);

        $agreement = null;

        Log::info('Fetched users for locum claim:', [
            'incharge_id' => $user->id,
            'deptId'      => $user->deptId,
            'user_count'  => $users->count(),
        ]);

        return view('locum_requests.createclaimforstaff', compact('user', 'users', 'agreement', 'months'));
    }

    public function claimForStaff(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to create a locum request.');
        }

        $user = Auth::user();

        if (!$user->hasRole('incharge')) {
            return redirect()->route('locum-requests.create')->with('error', 'Only incharge users can claim locum for staff.');
        }

        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) use ($user) {
                    $query->where('deptId', $user->deptId);
                }),
            ],
            'locum_month' => [
                'required',
                'string',
                Rule::in(
                    collect(range(0, 11))
                        ->map(fn($i) => now()->subMonths($i)->format('F Y'))
                        ->unique()
                        ->toArray()
                ),
            ],
            'worked_days' => 'required|array',
            'worked_days.*.worked' => 'nullable|boolean',
            'worked_days.*.hours'  => 'nullable|numeric|min:0|max:36',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Enforce "previous month only"
        $claimableMonth = now()->subMonth()->format('F Y');
        if ($validated['locum_month'] !== $claimableMonth) {
            return back()->withErrors(['locum_month' => "You can only claim for {$claimableMonth}."])->withInput();
        }

        try {
            $targetDate  = Carbon::createFromFormat('F Y', $validated['locum_month']);
            $targetMonth = $targetDate->format('F');
            $targetYear  = $targetDate->year;
        } catch (\Exception $e) {
            Log::error('Invalid month format in claimForStaff', [
                'locum_month' => $validated['locum_month'],
                'error'       => $e->getMessage(),
            ]);
            return back()->withErrors(['locum_month' => 'Invalid month and year format.']);
        }

        Log::info('Validated worked_days:', $validated['worked_days']);

        $totalDays  = 0;
        $totalHours = 0;

        // Optional: log holidays/weekends (no special pay logic here unless you add it)
        $currentYear = Carbon::now()->year;
        $holidays = [
            "$currentYear-01-01" => "New Year's Day",
            "$currentYear-01-12" => "Zanzibar Revolution Day",
            "$currentYear-04-07" => "Karume Day",
            "$currentYear-04-26" => "Union Day",
            "$currentYear-05-01" => "Workers' Day",
            "$currentYear-07-07" => "Saba Saba",
            "$currentYear-08-08" => "Nane Nane",
            "$currentYear-10-14" => "Nyerere Day",
            "$currentYear-12-09" => "Independence Day",
            "$currentYear-12-25" => "Christmas Day",
            "$currentYear-12-26" => "Boxing Day",
        ];

        foreach ($validated['worked_days'] as $dateStr => $data) {
            try {
                $date = \DateTime::createFromFormat('j F Y', $dateStr);
                if ($date === false) {
                    Log::error("Invalid date format: {$dateStr}");
                    continue;
                }
                $isoDate = $date->format('Y-m-d');
            } catch (\Exception $e) {
                Log::error("Date parsing error for {$dateStr}: {$e->getMessage()}");
                continue;
            }

            if (isset($holidays[$isoDate]) && ($data['worked'] ?? 0) == 1) {
                Log::info("Holiday selected: {$dateStr} ({$holidays[$isoDate]})");
            }

            $isWeekend = (int)$date->format('N') >= 6;
            if ($isWeekend && ($data['worked'] ?? 0) == 1) {
                Log::info("Weekend day selected: {$dateStr}");
            }

            if (($data['worked'] ?? 0) == 1) {
                $totalDays++;
                $totalHours += (float) ($data['hours'] ?? 0);
            }
        }

        if ($totalDays === 0) {
            return back()->withErrors(['worked_days' => 'At least one day must be selected']);
        }

        Log::info('Calculated values:', ['totalDays' => $totalDays, 'totalHours' => $totalHours]);

        // Ensure staff has an active HR-approved agreement (status = 2) - final guard
        $agreement = LocumAgreement::where('user_id', $validated['user_id'])
            ->where('has_contract', true)
            ->whereNull('rejection_status')
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where('status', 2) // Only HR-approved agreements can be used
            ->orderByDesc('created_at')
            ->first();

        if (!$agreement) {
            return back()->withErrors(['user_id' => 'The selected staff member does not have an active HR-approved locum agreement. Only agreements approved by HR can be used for locum requests.']);
        }


        // Education-level sensitive amount (rate × multiplier × claimed days)
        $educationMultiplier = (float) ($agreement->education_multiplier ?? 1.0);
        $rateUsed = (float) $agreement->locum_rate; // Store the rate used at creation time
        $totalAmount = $totalDays * $rateUsed * $educationMultiplier;

        $locumRequest = LocumRequest::create([
            'user_id'              => $validated['user_id'],
            'locum_agreement_id'   => $agreement->id,
            'rate_used'            => $rateUsed, // Store the rate used at creation time (for rejected claims)
            'locum_month'          => $targetMonth,
            'locum_year'           => $targetYear,
            'number_of_days'       => $totalDays,
            'total_hours'          => $totalHours,
            'total_amount'         => $totalAmount,
            'total_amount_payable' => $totalAmount,
            'worked_days'          => json_encode($validated['worked_days']),
            'submited_by_incharge' => $user->id,
            'reason'               => $validated['reason'] ?? null,
        ]);

        $staff = User::find($validated['user_id']);

        // Find Line Manager for the same department
        $lineManager = User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
            ->where('deptId', $agreement->user->deptId)
            ->first();

        if (!$lineManager) {
            Log::error('No Line Manager found for department ID: ' . $agreement->user->deptId);
            return back()->withErrors(['error' => 'No Line Manager found for the user\'s department.']);
        }

        // Create Workflow
        $workflow = Workflow::create([
            'user_id'            => $validated['user_id'],
            'locum_request_id'   => $locumRequest->id,
            'work_flow_status'   => 0, // Pending
            'work_flow_completed' => 0, // Not completed
        ]);

        WorkFlowHistory::create([
            'work_flow_id'          => $workflow->id,
            'forwarded_by'          => $user->id,
            'attended_by'           => $lineManager->id,
            'step_name'             => 'Line Manager Approval',
            'action_taken'          => 'Forwarded',
            'status'                => 0, // Pending
            'remark'                => 'Locum request submitted for Line Manager approval.',
            'locum_request_status'  => 1, // Pending Line Manager Approval
        ]);

        Log::info('Created LocumRequest:', $locumRequest->toArray());
        Log::info('Created Workflow:', $workflow->toArray());

        return redirect()->route('locum-requests.index')->with('success', 'Locum request submitted for approval.');
    }

    public function inchargeFetchBioTimeData(Request $request)
    {
        $request->validate([
            'ccbrt_code' => 'nullable|string',
            'month_year' => 'required|string', // "F Y", e.g. "August 2025"
        ]);

        $ccbrtCode = trim((string) $request->input('ccbrt_code', ''));
        $monthYear = trim((string) $request->input('month_year'));

        Log::info('inchargeFetchBioTimeData called', [
            'ccbrt_code' => $ccbrtCode,
            'month_year' => $monthYear,
        ]);

        // ---- Parse month/year ----
        try {
            $anchor = \Carbon\Carbon::createFromFormat('F Y', $monthYear)->startOfMonth();
            $month  = (int) $anchor->month;
            $year   = (int) $anchor->year;
            $daysInMonth = (int) $anchor->daysInMonth;
        } catch (\Throwable $e) {
            Log::error('Invalid month and year format', ['month_year' => $monthYear, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid month and year format'], 400);
        }

        // ---- Build default skeleton (keys must be "j F Y") ----
        $workedDays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateObj = (clone $anchor)->day($d);
            $key     = $dateObj->format('j F Y'); // e.g. "5 August 2025"
            $workedDays[$key] = [
                'worked'   => 0,
                'hours'    => '0.00',
                'time_in'  => null,
                'time_out' => null,
            ];
        }

        // No code -> return skeleton
        if ($ccbrtCode === '' || strtoupper($ccbrtCode) === 'N/A') {
            Log::info('No valid ccbrt_code provided, returning empty worked_days');
            return response()->json(['worked_days' => $workedDays]);
        }

        // ---- Connect + find schema bits ----
        try {
            \DB::connection('bio')->getPdo();
        } catch (\Throwable $e) {
            Log::error('BioTime DB connection failed', ['error' => $e->getMessage()]);
            return response()->json(['worked_days' => $workedDays], 200);
        }

        $trxTable = 'iclock_transaction';
        $hasPunchState = \Illuminate\Support\Facades\Schema::connection('bio')->hasColumn($trxTable, 'punch_state');

        // ---- Fetch punches from month start to end + 1 day (catch overnight OUTs) ----
        $start = (clone $anchor)->startOfDay();
        $end   = (clone $anchor)->endOfMonth()->addDay()->endOfDay(); // +1 day

        try {
            $selectCols = $hasPunchState
                ? "emp_code, punch_time, punch_state"
                : "emp_code, punch_time";
            $sql = "
            SELECT {$selectCols}
            FROM {$trxTable}
            WHERE emp_code = ?
              AND punch_time >= ?
              AND punch_time < ?
            ORDER BY punch_time ASC
        ";
            $rows = \DB::connection('bio')->select($sql, [$ccbrtCode, $start, $end]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('BioTime query failed', ['error' => $e->getMessage()]);
            return response()->json(['worked_days' => $workedDays], 200);
        }

        // ---- Normalize punches ----
        $punches = [];
        foreach ($rows as $r) {
            $dt   = \Carbon\Carbon::parse($r->punch_time);
            $date = $dt->toDateString();          // Y-m-d
            $time = $dt->format('H:i:s');         // HH:MM:SS

            // Map direction (adjust here if your devices are reversed)
            $dir = 'UNK';
            if ($hasPunchState) {
                // Convention used elsewhere in your app: 0 = Check In, 1 = Check Out
                $dir = ($r->punch_state === '0') ? 'IN' : (($r->punch_state === '1') ? 'OUT' : 'UNK');
            }

            $punches[] = [
                'dt'   => $dt,
                'date' => $date,
                'time' => $time,
                'dir'  => $dir,
            ];
        }
        // Already ordered by SQL

        // ---- Pair into sessions (allow up to 36h gaps; cross-midnight OK) ----
        $MAX_GAP_HOURS = 36;
        $sessions = [];
        $open = null;

        $pushSession = function ($a, $b) use (&$sessions) {
            $mins = (int) round(($b['dt']->getTimestamp() - $a['dt']->getTimestamp()) / 60);
            if ($mins > 0) {
                $sessions[] = [
                    'startDate' => $a['date'],
                    'startTime' => $a['time'],
                    'startDT'   => $a['dt'],
                    'endDate'   => $b['date'],
                    'endTime'   => $b['time'],
                    'endDT'     => $b['dt'],
                    'minutes'   => $mins,
                ];
            }
        };

        foreach ($punches as $p) {
            $dir = ($p['dir'] === 'UNK') ? ($open ? 'OUT' : 'IN') : $p['dir'];

            if ($dir === 'IN') {
                if ($open) {
                    $gapH = ($p['dt']->getTimestamp() - $open['dt']->getTimestamp()) / 3600;
                    if ($gapH > 0 && $gapH <= $MAX_GAP_HOURS) {
                        $pushSession($open, $p);
                    }
                }
                $open = $p;
            } else { // OUT
                if ($open) {
                    $gapH = ($p['dt']->getTimestamp() - $open['dt']->getTimestamp()) / 3600;
                    if ($gapH > 0 && $gapH <= $MAX_GAP_HOURS) {
                        $pushSession($open, $p);
                        $open = null;
                    } else {
                        // unreasonable gap; drop the open
                        $open = null;
                    }
                }
            }
        }

        // ---- Aggregate to start-day (only count days in the selected month) ----
        $days = []; // Y-m-d => aggregates
        $isInSelectedMonth = function (\Carbon\Carbon $c) use ($month, $year) {
            return ((int)$c->month === $month) && ((int)$c->year === $year);
        };

        foreach ($sessions as $s) {
            if (!$isInSelectedMonth($s['startDT'])) continue; // only anchor to days inside target month
            $key = $s['startDT']->toDateString();

            if (!isset($days[$key])) {
                $days[$key] = [
                    'firstInDT' => $s['startDT'],
                    'firstIn'   => $s['startTime'],
                    'lastOutDT' => $s['endDT'],
                    'lastOut'   => $s['endTime'],
                    'totalMin'  => 0,
                    'hasPunch'  => true,
                    'openDT'    => null, // set later if needed
                ];
            }
            $days[$key]['totalMin'] += $s['minutes'];

            // update first/last
            if ($s['startDT']->lt($days[$key]['firstInDT'])) {
                $days[$key]['firstInDT'] = $s['startDT'];
                $days[$key]['firstIn']   = $s['startTime'];
            }
            if ($s['endDT']->gt($days[$key]['lastOutDT'])) {
                $days[$key]['lastOutDT'] = $s['endDT'];
                $days[$key]['lastOut']   = $s['endTime'];
            }
        }

        // If there's an unfinished open session that started in the selected month:
        if ($open && $isInSelectedMonth($open['dt'])) {
            $key = $open['dt']->toDateString();
            if (!isset($days[$key])) {
                $days[$key] = [
                    'firstInDT' => $open['dt'],
                    'firstIn'   => $open['time'],
                    'lastOutDT' => null,
                    'lastOut'   => null,
                    'totalMin'  => 0,
                    'hasPunch'  => true,
                    'openDT'    => $open['dt'],
                ];
            } else {
                // ensure first-in is earliest
                if ($open['dt']->lt($days[$key]['firstInDT'])) {
                    $days[$key]['firstInDT'] = $open['dt'];
                    $days[$key]['firstIn']   = $open['time'];
                }
                $days[$key]['openDT'] = $open['dt'];
            }

            // If it's for today, count up to "now" (cap to MAX_GAP_HOURS)
            $now = \Carbon\Carbon::now();
            if ($key === $now->toDateString()) {
                $mins = (int) round(($now->getTimestamp() - $open['dt']->getTimestamp()) / 60);
                $cap  = $MAX_GAP_HOURS * 60;
                $days[$key]['totalMin'] += max(0, min($mins, $cap));
            }
        }

        // ---- Write back to requested "j F Y" keys ----
        foreach ($days as $iso => $agg) {
            $dateObj = \Carbon\Carbon::parse($iso);
            $key     = $dateObj->format('j F Y');

            $hoursDec = number_format(($agg['totalMin'] / 60), 2);

            $workedDays[$key] = [
                'worked'   => 1,
                'hours'    => $hoursDec,
                'time_in'  => $agg['firstIn'] ?? null,
                'time_out' => $agg['lastOut'] ?? null, // null for incomplete days
            ];
        }

        return response()->json(['worked_days' => $workedDays]);
    }

    public function bulkApprove(Request $request)
    {
        Log::debug('Bulk approve request received:', $request->all());

        try {
            try {
                $request->validate([
                    'request_ids'      => ['required', 'array', 'min:1'],
                    'request_ids.*'    => ['exists:workflows,id'],
                    'action'           => ['required', 'in:approve,reject'],
                    'rejection_reason' => ['required_if:action,reject', 'string', 'max:255'],
                ], [
                    'request_ids.required' => 'Please select at least one request.',
                    'request_ids.min'      => 'Please select at least one request.',
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'icon'  => 'error',
                    'title' => 'Validation Error',
                    'text'  => implode("\n", array_merge(...array_values($e->errors()))),
                ], 422);
            }

            $currentUser = Auth::user();

            $workflows = Workflow::whereIn('id', $request->request_ids)
                ->whereHas('histories', function ($q) use ($currentUser) {
                    $q->where('attended_by', $currentUser->id)->where('status', 0);
                })
                ->with([
                    'user.department' => function ($q) {
                        $q->select('id', 'dept_name', 'hec_id', 'has_three_level_approval', 'has_incharge_platform_flow', 'has_incharge_lm_hec_flow');
                    },
                    'user.department.hec' => function ($q) {
                        $q->select('id', 'hec_level_name');
                    },
                    'user.department.hec',
                    'locumRequest',
                    'histories' => fn($q) => $q->orderBy('id'),
                ])
                ->get();

            if ($workflows->isEmpty()) {
                return response()->json([
                    'icon'  => 'error',
                    'title' => 'Error',
                    'text'  => 'No authorized workflows found for this action.',
                ], 403);
            }

            // ---- Step labels
            $STEP_INCHARGE = 'Incharge Approval';
            $STEP_PLATFORM = 'Platform Manager Approval';
            $STEP_LM       = 'Line Manager Approval';
            $STEP_HEC      = 'HEC Approval';
            $STEP_HR       = 'HR Approval';

            // ---- Status codes (informational for the UI ladder)
            $STATUS_INCHARGE = 1;
            $STATUS_PLATFORM = 2;
            $STATUS_LM       = 3;
            $STATUS_HEC      = 4;
            $STATUS_HR       = 5;
            $STATUS_REJECTED = 5;

            // ---- Small helpers
            $resolveLM = function (int $deptId): ?User {
                return User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
                    ->where('deptId', $deptId)
                    ->first();
            };

            $resolveFlowKey = function (?string $storedFlow, ?Departments $dept): string {
                // Always check department settings first (they can be updated after request creation)
                // Department settings take precedence as they are the source of truth
                if ($dept) {
                    if ($dept->has_incharge_platform_flow) return 'incharge_platform';
                    if ($dept->has_incharge_lm_hec_flow)   return 'incharge_lm_hec';
                    if ($dept->has_three_level_approval)   return 'three_level';
                }
                // Fallback to stored flow on request if department has no specific settings
                if ($storedFlow && in_array($storedFlow, ['standard', 'three_level', 'incharge_platform', 'incharge_lm_hec'], true)) {
                    return $storedFlow;
                }
                return 'standard'; // default: LM → HR
            };

            $hrApproverCache  = null;   // cache a single HR approver
            $hecApproverCache = [];     // cache per-workflow HEC approver

            // Group workflows by user_id for in-charge and platform manager approvals
            // When an in-charge/platform manager approves/rejects, process ALL requests from that user
            $workflowsByUser = $workflows->groupBy('user_id');
            $processedWorkflowIds = [];

            foreach ($workflowsByUser as $userId => $userWorkflows) {
                // Get the first workflow to check the step name
                $firstWorkflow = $userWorkflows->first();
                $firstHistory = $firstWorkflow->histories()
                    ->where('attended_by', $currentUser->id)
                    ->where('status', 0)
                    ->first();

                // Check if this is an in-charge or platform manager approval step
                $isInchargeStep = $firstHistory && $firstHistory->step_name === $STEP_INCHARGE;
                $isPlatformStep = $firstHistory && $firstHistory->step_name === $STEP_PLATFORM;

                if (($isInchargeStep || $isPlatformStep) && $request->action === 'approve') {
                    // Find ALL pending requests from this user that need approval at this step
                    $stepName = $isInchargeStep ? $STEP_INCHARGE : $STEP_PLATFORM;
                    $allUserWorkflows = Workflow::where('user_id', $userId)
                        ->where('work_flow_completed', 0)
                        ->whereHas('histories', function ($q) use ($currentUser, $stepName) {
                            $q->where('attended_by', $currentUser->id)
                                ->where('step_name', $stepName)
                                ->where('status', 0);
                        })
                        ->with([
                            'user.department.hec',
                            'locumRequest',
                            'histories' => fn($q) => $q->orderBy('id'),
                        ])
                        ->get();

                    // Process all workflows from this user
                    foreach ($allUserWorkflows as $workflow) {
                        if (in_array($workflow->id, $processedWorkflowIds)) continue;

                        DB::transaction(function () use (
                            $request,
                            $workflow,
                            $currentUser,
                            $STEP_INCHARGE,
                            $STEP_PLATFORM,
                            $STEP_LM,
                            $STEP_HEC,
                            $STEP_HR,
                            $STATUS_INCHARGE,
                            $STATUS_PLATFORM,
                            $STATUS_LM,
                            $STATUS_HEC,
                            $STATUS_HR,
                            $STATUS_REJECTED,
                            &$hrApproverCache,
                            &$hecApproverCache,
                            $resolveLM,
                            $resolveFlowKey,
                            &$processedWorkflowIds,
                            $stepName
                        ) {
                            $currentHistory = $workflow->histories()
                                ->where('attended_by', $currentUser->id)
                                ->where('step_name', $stepName)
                                ->where('status', 0)
                                ->lockForUpdate()
                                ->first();

                            if (!$currentHistory) return;

                            $locumRequest = $workflow->locumRequest ?? LocumRequest::find($workflow->locum_request_id);

                            if (!$locumRequest) {
                                Log::error('LocumRequest not found for workflow in bulkApprove', [
                                    'workflow_id' => $workflow->id,
                                    'locum_request_id' => $workflow->locum_request_id,
                                ]);
                                $processedWorkflowIds[] = $workflow->id;
                                return; // Skip this workflow
                            }

                            $dept = $workflow->user->department;
                            $flowKey = $resolveFlowKey($locumRequest->approval_flow, $dept);

                            // Approve this workflow
                            $currentHistory->update([
                                'status' => 1,
                                'action_taken' => 'Approved',
                                'who_approve' => $currentUser->id,
                                'attend_date' => now(),
                                'locum_request_status' => $stepName === $STEP_INCHARGE ? $STATUS_INCHARGE : $STATUS_PLATFORM,
                            ]);

                            // Check if all approvers at this step have responded
                            $pendingAtStep = $workflow->histories()
                                ->where('step_name', $stepName)
                                ->where('status', 0)
                                ->count();

                            if ($pendingAtStep > 0) {
                                $processedWorkflowIds[] = $workflow->id;
                                return; // Wait for other approvers
                            }

                            // All approvers responded - proceed to next step
                            $nextStep = null;
                            $nextStatus = null;
                            $nextAssignees = [];

                            if ($stepName === $STEP_INCHARGE) {
                                // Guard against duplicate next rows
                                $existsNextPending = $workflow->histories()
                                    ->whereIn('step_name', [$STEP_PLATFORM, $STEP_LM])
                                    ->where('status', 0)
                                    ->exists();
                                if ($existsNextPending) {
                                    $processedWorkflowIds[] = $workflow->id;
                                    return;
                                }

                                if ($flowKey === 'incharge_platform') {
                                    // → Platform Manager(s)
                                    // Ensure locumRequest is loaded
                                    if (!$locumRequest) {
                                        $locumRequest = $workflow->locumRequest ?? LocumRequest::find($workflow->locum_request_id);
                                    }

                                    if (!$locumRequest) {
                                        Log::error('LocumRequest not found for workflow', ['workflow_id' => $workflow->id]);
                                        $processedWorkflowIds[] = $workflow->id;
                                        return;
                                    }

                                    $managers = collect($this->resolvePlatformManagersForRequest($locumRequest))
                                        ->pluck('id')
                                        ->unique()
                                        ->values()
                                        ->filter(fn($id) => $id !== $workflow->user_id)
                                        ->values();

                                    if ($managers->isNotEmpty()) {
                                        $nextStep = $STEP_PLATFORM;
                                        $nextStatus = $STATUS_PLATFORM;
                                        $nextAssignees = $managers->all();
                                    } else {
                                        // Fallback to LM
                                        $lm = $resolveLM((int) $workflow->user->deptId);
                                        if ($lm && $lm->id !== $workflow->user_id) {
                                            $nextStep = $STEP_LM;
                                            $nextStatus = $STATUS_LM;
                                            $nextAssignees = [$lm->id];
                                        } else {
                                            // Fallback to HR
                                            if (!$hrApproverCache) {
                                                $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                                    ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                                    ->first();
                                            }
                                            if ($hrApproverCache) {
                                                $nextStep = $STEP_HR;
                                                $nextStatus = $STATUS_HR;
                                                $nextAssignees = [$hrApproverCache->id];
                                            }
                                        }
                                    }
                                } else {
                                    // any other incharge-leading flow → LM
                                    $lm = $resolveLM((int) $workflow->user->deptId);
                                    if ($lm && $lm->id !== $workflow->user_id) {
                                        $nextStep = $STEP_LM;
                                        $nextStatus = $STATUS_LM;
                                        $nextAssignees = [$lm->id];
                                    } else {
                                        // No LM → HEC or HR
                                        if ($flowKey === 'incharge_lm_hec') {
                                            $hecApprover = $hecApproverCache[$workflow->id] ?? $this->resolveHecApproverForDept(
                                                $dept,
                                                [$currentUser->id, $workflow->user_id]
                                            );
                                            if ($hecApprover) {
                                                $hecApproverCache[$workflow->id] = $hecApprover;
                                                $nextStep = $STEP_HEC;
                                                $nextStatus = $STATUS_HEC;
                                                $nextAssignees = [$hecApprover->id];
                                            }
                                        } else {
                                            // → HR
                                            if (!$hrApproverCache) {
                                                $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                                    ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                                    ->first();
                                            }
                                            if ($hrApproverCache) {
                                                $nextStep = $STEP_HR;
                                                $nextStatus = $STATUS_HR;
                                                $nextAssignees = [$hrApproverCache->id];
                                            }
                                        }
                                    }
                                }
                            } elseif ($stepName === $STEP_PLATFORM) {
                                // After Platform Manager → LM (always, then LM routes based on department flow)
                                $existsNext = $workflow->histories()
                                    ->whereIn('step_name', [$STEP_LM, $STEP_HEC, $STEP_HR])
                                    ->where('status', 0)
                                    ->exists();
                                if ($existsNext) {
                                    $processedWorkflowIds[] = $workflow->id;
                                    return;
                                }

                                $lm = $resolveLM((int) $workflow->user->deptId);
                                if ($lm && $lm->id !== $workflow->user_id) {
                                    $nextStep = $STEP_LM;
                                    $nextStatus = $STATUS_LM;
                                    $nextAssignees = [$lm->id];
                                } else {
                                    // No LM → Check department flow for HEC requirement
                                    if ($flowKey === 'incharge_lm_hec' || $flowKey === 'three_level') {
                                        $hecApprover = $hecApproverCache[$workflow->id] ?? $this->resolveHecApproverForDept(
                                            $dept,
                                            [$currentUser->id, $workflow->user_id]
                                        );
                                        if ($hecApprover) {
                                            $hecApproverCache[$workflow->id] = $hecApprover;
                                            $nextStep = $STEP_HEC;
                                            $nextStatus = $STATUS_HEC;
                                            $nextAssignees = [$hecApprover->id];
                                        } else {
                                            // No HEC → HR
                                            if (!$hrApproverCache) {
                                                $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                                    ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                                    ->first();
                                            }
                                            if ($hrApproverCache) {
                                                $nextStep = $STEP_HR;
                                                $nextStatus = $STATUS_HR;
                                                $nextAssignees = [$hrApproverCache->id];
                                            }
                                        }
                                    } else {
                                        // Standard flow → HR
                                        if (!$hrApproverCache) {
                                            $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                                ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                                ->first();
                                        }
                                        if ($hrApproverCache) {
                                            $nextStep = $STEP_HR;
                                            $nextStatus = $STATUS_HR;
                                            $nextAssignees = [$hrApproverCache->id];
                                        }
                                    }
                                }
                            }

                            // Create next pending row(s) if we have next step
                            if (!empty($nextAssignees) && $nextStep) {
                                $alreadyPending = $workflow->histories()
                                    ->where('step_name', $nextStep)
                                    ->where('status', 0)
                                    ->pluck('attended_by')
                                    ->toArray();

                                foreach (array_unique($nextAssignees) as $assigneeId) {
                                    if (in_array($assigneeId, $alreadyPending, true)) continue;

                                    WorkFlowHistory::create([
                                        'work_flow_id' => $workflow->id,
                                        'forwarded_by' => $currentUser->id,
                                        'attended_by' => $assigneeId,
                                        'step_name' => $nextStep,
                                        'action_taken' => 'Forwarded',
                                        'status' => 0,
                                        'remark' => "Forwarded to {$nextStep}",
                                        'locum_request_status' => $nextStatus,
                                    ]);

                                    // Notify next approver
                                    if ($locumRequest) {
                                        $nextUser = User::find($assigneeId);
                                        if ($nextUser?->email) {
                                            Mail::to($nextUser->email)->queue(
                                                new \App\Mail\LocumApprovalRequest(
                                                    $locumRequest,
                                                    $workflow->fresh('histories'),
                                                    $workflow->user,
                                                    $nextUser
                                                )
                                            );
                                        }
                                    }
                                }
                            }

                            $processedWorkflowIds[] = $workflow->id;
                        });
                    }
                    continue; // Skip normal processing for this user
                } elseif (($isInchargeStep || $isPlatformStep) && $request->action === 'reject') {
                    // Find ALL pending requests from this user that need rejection at this step
                    $stepName = $isInchargeStep ? $STEP_INCHARGE : $STEP_PLATFORM;
                    $allUserWorkflows = Workflow::where('user_id', $userId)
                        ->where('work_flow_completed', 0)
                        ->whereHas('histories', function ($q) use ($currentUser, $stepName) {
                            $q->where('attended_by', $currentUser->id)
                                ->where('step_name', $stepName)
                                ->where('status', 0);
                        })
                        ->with([
                            'user.department.hec',
                            'locumRequest',
                            'histories' => fn($q) => $q->orderBy('id'),
                        ])
                        ->get();

                    // Reject all workflows from this user
                    foreach ($allUserWorkflows as $workflow) {
                        if (in_array($workflow->id, $processedWorkflowIds)) continue;

                        DB::transaction(function () use (
                            $request,
                            $workflow,
                            $currentUser,
                            $STATUS_REJECTED,
                            &$processedWorkflowIds,
                            $stepName
                        ) {
                            $currentHistory = $workflow->histories()
                                ->where('attended_by', $currentUser->id)
                                ->where('step_name', $stepName)
                                ->where('status', 0)
                                ->lockForUpdate()
                                ->first();

                            if (!$currentHistory) return;

                            // Reject this workflow
                            $currentHistory->update([
                                'status' => 2,
                                'action_taken' => 'Rejected',
                                'rejection_reason' => $request->rejection_reason,
                                'who_approve' => $currentUser->id,
                                'attend_date' => now(),
                                'locum_request_status' => $STATUS_REJECTED,
                            ]);

                            // Check if all approvers at this step have responded
                            $pendingAtStep = $workflow->histories()
                                ->where('step_name', $stepName)
                                ->where('status', 0)
                                ->count();

                            if ($pendingAtStep > 0) {
                                $processedWorkflowIds[] = $workflow->id;
                                return; // Wait for other approvers
                            }

                            // Check if any approver approved
                            $approvedAtStep = $workflow->histories()
                                ->where('step_name', $stepName)
                                ->where('status', 1)
                                ->count();

                            if ($approvedAtStep > 0) {
                                $processedWorkflowIds[] = $workflow->id;
                                return; // At least one approved, workflow continues
                            }

                            // All approvers rejected - mark workflow as rejected
                            $workflow->update([
                                'work_flow_status' => 2,
                                'work_flow_completed' => 1,
                            ]);

                            $locumRequest = $workflow->locumRequest ?? LocumRequest::find($workflow->locum_request_id);
                            if ($workflow->user?->email && $locumRequest) {
                                Mail::to($workflow->user->email)->queue(
                                    new \App\Mail\LocumStatusUpdate($locumRequest, 'rejected', $currentUser, $request->rejection_reason)
                                );
                            }

                            $processedWorkflowIds[] = $workflow->id;
                        });
                    }
                    continue; // Skip normal processing for this user
                }
            }

            // Process remaining workflows (non-in-charge/platform steps or already processed)
            foreach ($workflows as $workflow) {
                if (in_array($workflow->id, $processedWorkflowIds)) continue;
                DB::transaction(function () use (
                    $request,
                    $workflow,
                    $currentUser,
                    $STEP_INCHARGE,
                    $STEP_PLATFORM,
                    $STEP_LM,
                    $STEP_HEC,
                    $STEP_HR,
                    $STATUS_INCHARGE,
                    $STATUS_PLATFORM,
                    $STATUS_LM,
                    $STATUS_HEC,
                    $STATUS_HR,
                    $STATUS_REJECTED,
                    &$hrApproverCache,
                    &$hecApproverCache,
                    $resolveLM,
                    $resolveFlowKey
                ) {
                    // Lock the current pending row for this user
                    $currentHistory = $workflow->histories()
                        ->where('attended_by', $currentUser->id)
                        ->where('status', 0)
                        ->lockForUpdate()
                        ->first();

                    if (!$currentHistory) {
                        throw new \RuntimeException('Pending item not found (race).');
                    }

                    $locumRequest = $workflow->locumRequest ?? LocumRequest::find($workflow->locum_request_id);

                    if (!$locumRequest) {
                        Log::error('LocumRequest not found for workflow in bulkApprove (non-incharge/platform)', [
                            'workflow_id' => $workflow->id,
                            'locum_request_id' => $workflow->locum_request_id,
                        ]);
                        return; // Skip this workflow (inside transaction closure, use return)
                    }

                    $dept = $workflow->user->department;

                    // Active flow for this request/department
                    $flowKey = $resolveFlowKey($locumRequest->approval_flow, $dept);
                    // flowKey ∈ { standard, three_level, incharge_platform, incharge_lm_hec }

                    // ---------- Reject ----------
                    if ($request->action === 'reject') {
                        $currentHistory->update([
                            'status'               => 2,
                            'action_taken'         => 'Rejected',
                            'rejection_reason'     => $request->rejection_reason,
                            'who_approve'          => $currentUser->id,
                            'attend_date'          => now(),
                            'locum_request_status' => $STATUS_REJECTED,
                        ]);

                        $workflow->update([
                            'work_flow_status'    => 2,
                            'work_flow_completed' => 1,
                        ]);

                        if ($workflow->user?->email && $locumRequest) {
                            Mail::to($workflow->user->email)->queue(
                                new \App\Mail\LocumStatusUpdate($locumRequest, 'rejected', $currentUser, $request->rejection_reason)
                            );
                        }
                        return;
                    }

                    // ---------- Approve current step ----------
                    $currentHistory->update([
                        'status'               => 1,
                        'action_taken'         => 'Approved',
                        'who_approve'          => $currentUser->id,
                        'attend_date'          => now(),
                        'locum_request_status' => match ($currentHistory->step_name) {
                            $STEP_INCHARGE => $STATUS_INCHARGE,
                            $STEP_PLATFORM => $STATUS_PLATFORM,
                            $STEP_LM       => $STATUS_LM,
                            $STEP_HEC      => $STATUS_HEC,
                            $STEP_HR       => $STATUS_HR,
                            default        => $currentHistory->locum_request_status,
                        },
                    ]);

                    $nextStep      = null;
                    $nextStatus    = null;
                    $nextAssignees = [];

                    // ---------- Routing logic (driven by $flowKey) ----------
                    if ($currentHistory->step_name === $STEP_INCHARGE) {
                        // Wait for all in-charges to finish
                        $pendingIncharge = $workflow->histories()
                            ->where('step_name', $STEP_INCHARGE)
                            ->where('status', 0)
                            ->lockForUpdate()
                            ->count();
                        if ($pendingIncharge > 0) return;

                        // Guard against duplicate next rows
                        $existsNextPending = $workflow->histories()
                            ->whereIn('step_name', [$STEP_PLATFORM, $STEP_LM])
                            ->where('status', 0)
                            ->exists();
                        if ($existsNextPending) return;

                        if ($flowKey === 'incharge_platform') {
                            // → Platform Manager(s)
                            // Ensure locumRequest is loaded
                            if (!$locumRequest) {
                                $locumRequest = $workflow->locumRequest ?? LocumRequest::find($workflow->locum_request_id);
                            }

                            if (!$locumRequest) {
                                Log::error('LocumRequest not found for workflow', ['workflow_id' => $workflow->id]);
                                return; // Skip this workflow (inside transaction closure, use return)
                            }

                            $managers = collect($this->resolvePlatformManagersForRequest($locumRequest))
                                ->pluck('id')
                                ->unique()
                                ->values()
                                ->filter(fn($id) => $id !== $workflow->user_id)
                                ->values();

                            if ($managers->isNotEmpty()) {
                                $nextStep      = $STEP_PLATFORM;
                                $nextStatus    = $STATUS_PLATFORM;
                                $nextAssignees = $managers->all();
                            } else {
                                // Fallback to LM
                                $lm = $resolveLM((int) $workflow->user->deptId);
                                if ($lm) {
                                    $nextStep      = $STEP_LM;
                                    $nextStatus    = $STATUS_LM;
                                    $nextAssignees = [$lm->id];
                                } else {
                                    // Fallback to HR
                                    if (!$hrApproverCache) {
                                        $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                            ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                            ->first();
                                    }
                                    if ($hrApproverCache) {
                                        $nextStep      = $STEP_HR;
                                        $nextStatus    = $STATUS_HR;
                                        $nextAssignees = [$hrApproverCache->id];
                                    }
                                }
                            }
                        } else {
                            // any other incharge-leading flow → LM
                            $lm = $resolveLM((int) $workflow->user->deptId);
                            if ($lm) {
                                $nextStep      = $STEP_LM;
                                $nextStatus    = $STATUS_LM;
                                $nextAssignees = [$lm->id];
                            } else {
                                // No LM → HR
                                if (!$hrApproverCache) {
                                    $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                        ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                        ->first();
                                }
                                if ($hrApproverCache) {
                                    $nextStep      = $STEP_HR;
                                    $nextStatus    = $STATUS_HR;
                                    $nextAssignees = [$hrApproverCache->id];
                                }
                            }
                        }
                    } elseif ($currentHistory->step_name === $STEP_PLATFORM) {
                        // Wait for all platform managers to finish
                        $pendingPlatform = $workflow->histories()
                            ->where('step_name', $STEP_PLATFORM)
                            ->where('status', 0)
                            ->lockForUpdate()
                            ->count();
                        if ($pendingPlatform > 0) return;

                        // Guard duplicate next step creation
                        $existsNext = $workflow->histories()
                            ->whereIn('step_name', [$STEP_LM, $STEP_HEC, $STEP_HR])
                            ->where('status', 0)
                            ->exists();
                        if ($existsNext) return;

                        // After Platform Manager → LM (always, regardless of flow)
                        // Then LM will route to HEC or HR based on department flow
                        $lm = $resolveLM((int) $workflow->user->deptId);
                        if ($lm && $lm->id !== $workflow->user_id) {
                            $nextStep      = $STEP_LM;
                            $nextStatus    = $STATUS_LM;
                            $nextAssignees = [$lm->id];
                        } else {
                            // No LM → Check if flow requires HEC, otherwise go to HR
                            if ($flowKey === 'incharge_lm_hec' || $flowKey === 'three_level') {
                                $hecApprover = $hecApproverCache[$workflow->id] ?? $this->resolveHecApproverForDept(
                                    $dept,
                                    [$currentUser->id, $workflow->user_id]
                                );
                                if ($hecApprover) {
                                    $hecApproverCache[$workflow->id] = $hecApprover;
                                    $nextStep      = $STEP_HEC;
                                    $nextStatus    = $STATUS_HEC;
                                    $nextAssignees = [$hecApprover->id];
                                } else {
                                    // No HEC → HR
                                    if (!$hrApproverCache) {
                                        $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                            ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                            ->first();
                                    }
                                    if ($hrApproverCache) {
                                        $nextStep      = $STEP_HR;
                                        $nextStatus    = $STATUS_HR;
                                        $nextAssignees = [$hrApproverCache->id];
                                    }
                                }
                            } else {
                                // Standard flow → HR
                                if (!$hrApproverCache) {
                                    $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                        ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                        ->first();
                                }
                                if ($hrApproverCache) {
                                    $nextStep      = $STEP_HR;
                                    $nextStatus    = $STATUS_HR;
                                    $nextAssignees = [$hrApproverCache->id];
                                }
                            }
                        }
                    } elseif ($currentHistory->step_name === $STEP_LM) {
                        // After LM depends on the department flow settings
                        $requiresHEC = in_array($flowKey, ['three_level', 'incharge_lm_hec'], true);

                        if ($requiresHEC) {
                            // → HEC
                            $hecApprover = $hecApproverCache[$workflow->id] ?? $this->resolveHecApproverForDept(
                                $dept,
                                [$currentUser->id, $workflow->user_id]
                            );
                            if (!$hecApprover) {
                                throw new \RuntimeException('No HEC approver configured for department.');
                            }
                            $hecApproverCache[$workflow->id] = $hecApprover;

                            $nextStep      = $STEP_HEC;
                            $nextStatus    = $STATUS_HEC;
                            $nextAssignees = [$hecApprover->id];
                        } else {
                            // → HR
                            if (!$hrApproverCache) {
                                $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                    ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                    ->first();
                            }
                            if (!$hrApproverCache) {
                                throw new \RuntimeException('No HR approver found.');
                            }
                            $nextStep      = $STEP_HR;
                            $nextStatus    = $STATUS_HR;
                            $nextAssignees = [$hrApproverCache->id];
                        }
                    } elseif ($currentHistory->step_name === $STEP_HEC) {
                        // → HR
                        if (!$hrApproverCache) {
                            $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                                ->when(Schema::hasColumn('users', 'approvelocum'), fn($qq) => $qq->where('approvelocum', 1))
                                ->first();
                        }
                        if (!$hrApproverCache) {
                            throw new \RuntimeException('No HR approver found.');
                        }
                        $nextStep      = $STEP_HR;
                        $nextStatus    = $STATUS_HR;
                        $nextAssignees = [$hrApproverCache->id];
                    }

                    // ---------- Create next pending row(s) OR finish ----------
                    if (!empty($nextAssignees) && $nextStep) {
                        // Guard duplicates
                        $alreadyPending = $workflow->histories()
                            ->where('step_name', $nextStep)
                            ->where('status', 0)
                            ->pluck('attended_by')
                            ->toArray();

                        foreach (array_unique($nextAssignees) as $assigneeId) {
                            if (in_array($assigneeId, $alreadyPending, true)) continue;

                            WorkFlowHistory::create([
                                'work_flow_id'         => $workflow->id,
                                'forwarded_by'         => $currentUser->id,
                                'attended_by'          => $assigneeId,
                                'step_name'            => $nextStep,
                                'action_taken'         => 'Forwarded',
                                'status'               => 0,
                                'remark'               => "Forwarded to {$nextStep}",
                                'locum_request_status' => $nextStatus,
                            ]);

                            // Notify next approver
                            if ($locumRequest) {
                                $nextUser = User::find($assigneeId);
                                if ($nextUser?->email) {
                                    Mail::to($nextUser->email)->queue(
                                        new \App\Mail\LocumApprovalRequest(
                                            $locumRequest,
                                            $workflow->fresh('histories'),
                                            $workflow->user,
                                            $nextUser
                                        )
                                    );
                                }
                            }
                        }
                    } else {
                        // Terminal HR approval
                        if ($currentHistory->step_name === $STEP_HR) {
                            $workflow->update([
                                'work_flow_status'    => 1,
                                'work_flow_completed' => 1,
                            ]);

                            if ($workflow->user?->email && $locumRequest) {
                                Mail::to($workflow->user->email)->queue(
                                    new \App\Mail\LocumStatusUpdate($locumRequest, 'approved', $currentUser)
                                );
                            }
                        }
                    }
                });
            }

            Alert::success('Success', 'Locum requests processed successfully.');

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'icon'    => 'success',
                    'title'   => 'Success',
                    'text'    => 'Locum requests processed successfully.',
                    'message' => 'Locum requests processed successfully.',
                ], 200);
            }

            return redirect()->route('locum-requests.view')
                ->with('success', 'Locum requests processed successfully.');
        } catch (\Exception $e) {
            Log::error('Bulk approve failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'icon'    => 'error',
                    'title'   => 'Error',
                    'text'    => 'Failed to approve requests: ' . $e->getMessage(),
                    'message' => 'Failed to approve requests: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('locum-requests.view')
                ->with('error', 'Failed to approve requests: ' . $e->getMessage());
        }
    }

    public function bulkReject(Request $request)
    {
        Log::debug('Bulk reject request received:', $request->all());

        try {
            $request->validate([
                'request_ids'      => ['required', 'array', 'min:1'],
                'request_ids.*'    => ['exists:workflows,id'],
                'rejection_reason' => ['required', 'string', 'max:255'],
            ], [
                'request_ids.required' => 'Please select at least one request.',
                'request_ids.min'      => 'Please select at least one request.',
                'rejection_reason.required' => 'Please provide a reason for rejection.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Validation Error',
                'text'  => implode("\n", array_merge(...array_values($e->errors()))),
            ], 422);
        }

        $currentUser = Auth::user();

        $workflows = Workflow::whereIn('id', $request->request_ids)
            ->whereHas('histories', function ($q) use ($currentUser) {
                $q->where('attended_by', $currentUser->id)->where('status', 0);
            })
            ->with([
                'user.department' => function ($q) {
                    $q->select('id', 'dept_name', 'hec_id', 'has_three_level_approval', 'has_incharge_platform_flow', 'has_incharge_lm_hec_flow');
                },
                'user.department.hec' => function ($q) {
                    $q->select('id', 'hec_level_name');
                },
                'user.department.hec',
                'locumRequest',
                'histories' => fn($q) => $q->orderBy('id'),
            ])
            ->get();

        if ($workflows->isEmpty()) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Error',
                'text'  => 'No authorized workflows found for this action.',
            ], 403);
        }

        $STATUS_REJECTED = 5;

        foreach ($workflows as $workflow) {
            DB::transaction(function () use ($request, $workflow, $currentUser, $STATUS_REJECTED) {
                // Lock the current pending row for this user
                $currentHistory = $workflow->histories()
                    ->where('attended_by', $currentUser->id)
                    ->where('status', 0)
                    ->lockForUpdate()
                    ->first();

                if (!$currentHistory) {
                    throw new \RuntimeException('Pending item not found (race).');
                }

                $locumRequest = $workflow->locumRequest ?? LocumRequest::find($workflow->locum_request_id);

                // Update history with rejection
                $currentHistory->update([
                    'status'               => 2,
                    'action_taken'         => 'Rejected',
                    'rejection_reason'     => $request->rejection_reason,
                    'who_approve'          => $currentUser->id,
                    'attend_date'          => now(),
                    'locum_request_status' => $STATUS_REJECTED,
                ]);

                /**
                 * Business rule (confirmed with user):
                 * -----------------------------------------------------------
                 * If ANY approver at a step (In-Charge, Platform Manager,
                 * Line Manager, HEC, or HR) rejects the request, the ENTIRE
                 * locum request is considered rejected and should return to
                 * the requester for edit & resubmission.
                 *
                 * That means we do NOT wait for the remaining approvers at
                 * the same step to respond – one rejection is enough to stop
                 * the workflow.
                 * -----------------------------------------------------------
                 */
                $workflow->update([
                    'work_flow_status'    => 2,
                    'work_flow_completed' => 1,
                ]);

                // Send rejection notification email
                if ($workflow->user?->email && $locumRequest) {
                    try {
                        Mail::to($workflow->user->email)->queue(
                            new \App\Mail\LocumStatusUpdate($locumRequest, 'rejected', $currentUser, $request->rejection_reason)
                        );
                    } catch (\Throwable $mailEx) {
                        Log::error('Locum bulk rejection email failed', [
                            'workflow_id' => $workflow->id,
                            'error' => $mailEx->getMessage(),
                        ]);
                    }
                }
            });
        }

        // Check if this is an AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'icon'    => 'success',
                'title'   => 'Success',
                'text'    => count($workflows) . ' request(s) rejected successfully.',
                'message' => count($workflows) . ' request(s) rejected successfully.',
            ], 200, ['Content-Type' => 'application/json']);
        }

        // Fallback: redirect with flash message for non-AJAX requests
        return redirect()->route('locum-requests.view')
            ->with('success', count($workflows) . ' request(s) rejected successfully.');
    }

    // ==========================================================
    // HELPERS
    // ==========================================================

    /**
     * Resolve unique Platform Managers tied to the platforms used in this request.
     *
     * @return array<\App\Models\User>
     */
    private function resolvePlatformManagersForRequest(LocumRequest $locumRequest): array
    {
        $platformIds = (array) ($locumRequest->platform_ids ?? []);
        if (empty($platformIds)) return [];

        $platforms = Platform::whereIn('id', $platformIds)
            ->whereNotNull('manager_user_id')
            ->get(['id', 'name', 'manager_user_id']);

        $managerIds = $platforms->pluck('manager_user_id')->filter()->unique()->values()->all();
        if (empty($managerIds)) return [];

        // Get platform managers based on manager_user_id assignment (not role-based)
        // Platform managers are assigned via manager_user_id on the platform, not by role
        $managers = User::whereIn('id', $managerIds)->get();

        return $managers->all();
    }

    /**
     * Resolve an HEC approver for a department based on its HEC level name.
     * Maps HEC level → role slug (e.g., 'COO' → 'coo') and finds a User with that role.
     *
     * @param  \App\Models\Departments|null $department
     * @param  array<int>                   $excludeUserIds
     * @return \App\Models\User|null
     */
    private function resolveHecApproverForDept(?Departments $department, array $excludeUserIds = []): ?User
    {
        if (!$department) {
            Log::warning('resolveHecApproverForDept: missing department', [
                'dept' => null,
            ]);
            return null;
        }

        // Ensure HEC relationship is loaded
        if (!$department->relationLoaded('hec') && $department->hec_id) {
            $department->load('hec');
        }

        if (!$department->hec_id) {
            Log::warning('resolveHecApproverForDept: department has no hec_id', [
                'dept_id' => $department->id ?? $department->dept_id ?? null,
            ]);
            return null;
        }

        $hec = $department->hec ?? Hec::find($department->hec_id);
        if (!$hec || !$hec->hec_level_name) {
            Log::error('resolveHecApproverForDept: HEC row not found or missing level name', [
                'hec_id' => $department->hec_id,
                'dept_id' => $department->id ?? $department->dept_id ?? null,
            ]);
            return null;
        }

        // Map HEC level name to role (handle both uppercase and lowercase)
        $hecLevelName = trim($hec->hec_level_name);
        $roleMap = [
            // Uppercase variants (as stored in database)
            'COO' => 'coo',
            'CFO' => 'cfo',
            'CMS' => 'cms',
            'CCDRO' => 'ccdro',
            // Lowercase variants
            'coo' => 'coo',
            'cfo' => 'cfo',
            'cms' => 'cms',
            'ccdro' => 'ccdro',
            // Friendly variants
            'chief operating officer'  => 'coo',
            'chief financial officer'  => 'cfo',
            'chief medical specialist' => 'cms',
        ];

        // Try exact match first, then lowercase
        $roleSlug = $roleMap[$hecLevelName] ?? $roleMap[strtolower($hecLevelName)] ?? null;

        if (!$roleSlug) {
            Log::error('resolveHecApproverForDept: unrecognized hec_level_name', [
                'hec_level_name' => $hec->hec_level_name,
                'normalized'     => strtolower($hecLevelName),
                'dept_id'        => $department->id ?? $department->dept_id ?? null,
            ]);
            return null;
        }

        // Use Spatie Laravel Permission's role() method (same as other controllers)
        $query = User::role($roleSlug);
        if (!empty($excludeUserIds)) {
            $query->whereNotIn('id', $excludeUserIds);
        }

        $approver = $query->orderBy('id')->first();

        if (!$approver) {
            Log::error('resolveHecApproverForDept: no candidate for role', [
                'role'          => $roleSlug,
                'hec_level_name' => $hec->hec_level_name,
                'excluded_ids'  => $excludeUserIds,
                'dept_id'       => $department->id ?? $department->dept_id ?? null,
            ]);
        } else {
            Log::info('resolveHecApproverForDept: found approver', [
                'approver_id'   => $approver->id,
                'approver_name' => $approver->fname . ' ' . $approver->lname,
                'role'          => $roleSlug,
                'hec_level_name' => $hec->hec_level_name,
            ]);
        }

        return $approver;
    }

    public function show($id)
    {
        $request = LocumRequest::where('user_id', auth()->id())->findOrFail($id);
        $agreement = LocumAgreement::where('user_id', auth()->id())->first();

        return view('locum_requests.show', compact('request', 'agreement'));
    }

    public function edit($id)
    {
        $userId = auth()->id();

        // Load request with its workflow + histories
        $locumRequest = LocumRequest::with(['workflow.histories'])
            ->where('user_id', $userId)
            ->findOrFail($id);

        // Safeties if workflow or histories are missing
        $histories   = optional($locumRequest->workflow)->histories ?? collect();
        $lastHistory = $histories->sortByDesc('updated_at')->first() ?? $histories->sortByDesc('created_at')->first();

        // Determine "rejected"
        // We treat it as rejected if:
        //  - the latest history status == 2 (Rejected), OR
        //  - the latest locum_request_status == 5 (your unified "Rejected" code), OR
        //  - the workflow itself is marked rejected (work_flow_status == 2)
        $isRejected = false;
        if ($lastHistory) {
            $code = (int) ($lastHistory->locum_request_status ?? 0);
            $isRejected = ((string) $lastHistory->status === '2') || $code === 5;
        }
        if (!$isRejected && $locumRequest->workflow) {
            $isRejected = (int) $locumRequest->workflow->work_flow_status === 2;
        }

        if (!$isRejected) {
            return redirect()
                ->route('locum-requests.index')
                ->with('error', 'Only rejected requests can be edited.');
        }

        // Check if 2 months have passed since rejection
        $rejectedAt = null;
        if ($lastHistory && (string) $lastHistory->status === '2') {
            $rejectedAt = $lastHistory->attend_date ?: $lastHistory->updated_at ?: $lastHistory->created_at;
        }

        if ($rejectedAt) {
            $rejectedDate = $rejectedAt instanceof \Carbon\Carbon ? $rejectedAt : \Carbon\Carbon::parse($rejectedAt);
            $twoMonthsAgo = now()->subMonths(2);

            if ($rejectedDate->lt($twoMonthsAgo)) {
                return redirect()
                    ->route('locum-requests.index')
                    ->with('error', 'This request was rejected more than 2 months ago and can no longer be edited or resubmitted.');
            }
        }

        // Get the most recent HR-approved agreement (status = 2)
        // Order by updated_at DESC to get the most recently approved agreement by HR
        // If there's a newer agreement that's not HR-approved yet, use the previous HR-approved one
        $today = \Carbon\Carbon::today();
        $agreement = LocumAgreement::where('user_id', $userId)
            ->where('status', 2) // Only HR-approved agreements
            ->where(function($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('updated_at') // Most recently approved by HR
            ->orderByDesc('created_at') // Fallback to creation date
            ->first();

        // If no active HR-approved agreement, get the most recent HR-approved one (even if expired)
        // This allows editing rejected claims even if the agreement expired
        if (!$agreement) {
            $agreement = LocumAgreement::where('user_id', $userId)
                ->where('status', 2) // Only HR-approved agreements
                ->orderByDesc('updated_at') // Most recently approved by HR
                ->orderByDesc('created_at') // Fallback to creation date
                ->first();
        }

        // Get the same data as create method for consistency
        $selectedMonth = $locumRequest->locum_month;
        $selectedYear = $locumRequest->locum_year ?: \Carbon\Carbon::parse($locumRequest->created_at)->format('Y');
        $currentYear = (int) $selectedYear;

        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        // Holidays for selected year
        $holidays = [
            "{$currentYear}-01-01" => "New Year's Day",
            "{$currentYear}-01-12" => 'Zanzibar Revolution Day',
            "{$currentYear}-03-29" => 'Eid al-Fitr (Estimated)',
            "{$currentYear}-04-07" => 'Karume Day',
            "{$currentYear}-04-18" => 'Good Friday',
            "{$currentYear}-04-21" => 'Easter Monday',
            "{$currentYear}-04-26" => 'Union Day',
            "{$currentYear}-05-01" => "Workers' Day",
            "{$currentYear}-06-05" => 'Eid al-Adha (Estimated)',
            "{$currentYear}-07-07" => 'Saba Saba',
            "{$currentYear}-08-08" => 'Nane Nane',
            "{$currentYear}-09-27" => 'Maulid (Estimated)',
            "{$currentYear}-10-14" => 'Nyerere Day',
            "{$currentYear}-12-09" => 'Independence Day',
            "{$currentYear}-12-25" => 'Christmas Day',
            "{$currentYear}-12-26" => 'Boxing Day',
        ];

        // Shifts: id + name only
        $shifts = ShiftSetting::select('id', 'name')
            ->orderBy('name')
            ->get();

        // Platforms now include locum_hours (8 or 12)
        $platforms = Platform::select('id', 'name', 'locum_hours')
            ->orderBy('name')
            ->get();

        // Handy map for JS: { [platformId]: locum_hours }
        $platformHours = $platforms->pluck('locum_hours', 'id');

        // Get department settings for unit selection logic
        $user = Auth::user();
        $department = $user->department;
        $hasThreeLevel = (bool) ($department->has_three_level_approval ?? false); // LM → HEC → HR
        $hasInchargeLmHec = (bool) ($department->has_incharge_lm_hec_flow ?? false); // In-Charge → LM → HEC → HR
        $isStandardFlow = !$hasThreeLevel && !$hasInchargeLmHec && !($department->has_incharge_platform_flow ?? false); // LM → HR (standard)

        // Disable unit selection if flow is LM→HEC→HR or LM→HR (standard)
        $disableUnitSelection = $hasThreeLevel || $isStandardFlow || $hasInchargeLmHec;

        return view('locum_requests.edit', [
            'request'      => $locumRequest,
            'agreement'    => $agreement,
            'months'       => $months,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'holidays'     => $holidays,
            'shifts'       => $shifts,
            'platforms'    => $platforms,
            'platformHours' => $platformHours,
            'disableUnitSelection' => $disableUnitSelection,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Load request with workflow + histories (ownership guard)
        $locumRequest = LocumRequest::with(['workflow.histories', 'locumAgreement'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        // Only allow editing of rejected requests
        $histories   = optional($locumRequest->workflow)->histories ?? collect();
        $lastHistory = $histories->sortByDesc('updated_at')->first() ?? $histories->sortByDesc('created_at')->first();

        $isRejected = false;
        if ($lastHistory) {
            $code = (int) ($lastHistory->locum_request_status ?? 0);
            $isRejected = ((string) $lastHistory->status === '2') || $code === 5;
        }
        if (!$isRejected && $locumRequest->workflow) {
            $isRejected = (int) $locumRequest->workflow->work_flow_status === 2;
        }

        if (!$isRejected) {
            return redirect()->route('locum-requests.index')
                ->with('error', 'Only rejected requests can be updated & resubmitted.');
        }

        // Check if 2 months have passed since rejection
        $rejectedAt = null;
        if ($lastHistory && (string) $lastHistory->status === '2') {
            $rejectedAt = $lastHistory->attend_date ?: $lastHistory->updated_at ?: $lastHistory->created_at;
        }

        if ($rejectedAt) {
            $rejectedDate = $rejectedAt instanceof \Carbon\Carbon ? $rejectedAt : \Carbon\Carbon::parse($rejectedAt);
            $twoMonthsAgo = now()->subMonths(2);

            if ($rejectedDate->lt($twoMonthsAgo)) {
                return redirect()->route('locum-requests.index')
                    ->with('error', 'This request was rejected more than 2 months ago and can no longer be edited or resubmitted.');
            }
        }

        // Validate (same rules as store method - using entries structure)
        $validated = $request->validate([
            'locum_month'                   => ['required', Rule::in([
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December'
            ])],
            'locum_year'                    => ['required', 'integer', 'min:2000', 'max:2100'],
            'worked_days'                   => ['required', 'array'],
            'worked_days.*.worked'          => ['nullable', 'boolean'],
            // entries per worked day
            'worked_days.*.entries'                             => ['nullable', 'array'],
            'worked_days.*.entries.*.shift_id'                  => ['required', 'integer', 'exists:shift_settings,id'],
            'worked_days.*.entries.*.hours'                     => ['required', 'numeric', 'min:0.01', 'max:36'],
            'worked_days.*.entries.*.platform_id'               => ['required', 'integer', 'exists:platforms,id'],
            'worked_days.*.entries.*.unit_id'                   => ['nullable', 'integer', 'exists:units,id'],
            'description'                                       => ['nullable', 'string', 'max:2000'],
        ]);

        $tz = 'Africa/Dar_es_Salaam';
        $now = \Carbon\Carbon::now($tz);

        // Validate month selection - prevent future months
        $selectedMonth = $validated['locum_month'];
        $selectedYear = (int)$validated['locum_year'];
        $monthIndex = array_search($selectedMonth, [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ]);
        $selectedDate = \Carbon\Carbon::create($selectedYear, $monthIndex + 1, 1, 0, 0, 0, $tz);
        $currentMonthStart = $now->copy()->startOfMonth();

        // Prevent claiming for future months
        if ($selectedDate->gt($currentMonthStart)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['locum_month' => 'You cannot claim for future months. Please select a month that has already passed.']);
        }

        // Resolve target year
        $targetYear = $selectedYear;

        // Lock month & year
        try {
            $targetMonthCarbon = \Carbon\Carbon::parse(sprintf('%s %d', $validated['locum_month'], $targetYear), $tz);
        } catch (\Throwable $e) {
            return back()->withErrors(['locum_month' => 'Invalid month/year selection.'])->withInput();
        }
        $selMonthNum = (int) $targetMonthCarbon->format('n');
        $selYearNum  = (int) $targetMonthCarbon->format('Y');

        // Agreement + rate
        // For rejected claims being edited, use the stored rate_used (preserves old rate)
        // For new claims, use the most recent HR-approved agreement rate
        // Always get the most recent HR-approved agreement (status = 2)
        // Order by updated_at DESC to get the most recently approved agreement by HR
        $today = \Carbon\Carbon::today();
        $agreement = LocumAgreement::where('user_id', $user->id)
            ->where('status', 2) // Only HR-approved agreements
            ->where(function($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('updated_at') // Most recently approved by HR
            ->orderByDesc('created_at') // Fallback to creation date
            ->first();

        // If no active HR-approved agreement, get the most recent HR-approved one (even if expired)
        if (!$agreement) {
            $agreement = LocumAgreement::where('user_id', $user->id)
                ->where('status', 2) // Only HR-approved agreements
                ->orderByDesc('updated_at') // Most recently approved by HR
                ->orderByDesc('created_at') // Fallback to creation date
                ->first();
        }

        if (!$agreement) {
            return back()->withErrors(['locum_agreement_id' => 'You must have an HR-approved locum agreement to resubmit this request. Only agreements approved by HR can be used.'])->withInput();
        }

        // Use the stored rate_used for rejected claims (preserves old rate), otherwise use current agreement rate
        $rate = $locumRequest->rate_used ?? (float) ($agreement->locum_rate ?? 0);

        // If rate_used is not set (legacy data), use agreement rate but store it for future edits
        if (!$locumRequest->rate_used) {
            $locumRequest->rate_used = $rate;
            $locumRequest->save();
        }

        // Normalize & validate selected entries (must be inside locked month/year)
        $rows = []; // each = {date, shift_id, platform_id, unit_id, hours}
        foreach ($validated['worked_days'] as $isoDate => $day) {
            try {
                $date = \Carbon\Carbon::createFromFormat('Y-m-d', $isoDate, $tz);
            } catch (\Throwable $e) {
                \Log::warning("Invalid date key in worked_days: {$isoDate}");
                return back()->withErrors(['worked_days' => "Invalid date: {$isoDate}"])->withInput();
            }

            if ((int)$date->format('n') !== $selMonthNum || (int)$date->format('Y') !== $selYearNum) {
                return back()->withErrors([
                    'worked_days' => "Date {$date->format('d M Y')} is not in {$validated['locum_month']} {$selYearNum}."
                ])->withInput();
            }

            $worked = (int)($day['worked'] ?? 0);
            if ($worked !== 1) continue;

            $entries = $day['entries'] ?? [];
            if (!is_array($entries) || count($entries) === 0) {
                return back()->withErrors([
                    'worked_days' => "Please add at least one entry for {$date->format('d M Y')}."
                ])->withInput();
            }

            foreach ($entries as $ix => $ent) {
                $hrs = (float) ($ent['hours'] ?? 0);
                if ($hrs <= 0) {
                    return back()->withErrors([
                        'worked_days' => "Hours must be greater than 0 for {$date->format('d M Y')} (entry #" . ($ix + 1) . ")."
                    ])->withInput();
                }

                $rows[] = [
                    'iso_date'    => $isoDate,
                    'date_label'  => $date->format('Y-m-d'),
                    'weekday'     => (int)$date->format('w'),
                    'shift_id'    => (int)$ent['shift_id'],
                    'platform_id' => (int)$ent['platform_id'],
                    'unit_id'     => isset($ent['unit_id']) ? (int)$ent['unit_id'] : null,
                    'hours'       => round($hrs, 2),
                ];
            }
        }

        if (empty($rows)) {
            return back()->withErrors(['worked_days' => 'Please select at least one worked day and add at least one entry.'])->withInput();
        }

        // Platform / Unit-specific hours (8/12) & names
        $platformHours = \App\Models\Platform::pluck('locum_hours', 'id'); // id => 8|12 (default per platform)
        $unitHours     = \App\Models\Unit::pluck('locum_hours', 'id');     // id => nullable override per unit
        $shiftNames    = \App\Models\ShiftSetting::pluck('name', 'id');    // id => name
        $platformNames = \App\Models\Platform::pluck('name', 'id');        // id => name
        $unitNames     = \App\Models\Unit::pluck('name', 'id');            // id => name

        // Group by SHIFT × PLATFORM × UNIT (if unit specified), or SHIFT × PLATFORM (if no unit)
        // Same logic as in the store() method, so that edit/resubmit uses identical calculations
        $byShiftPlatUnit = []; // [shift_id][platform_id][unit_id_or_null] => ['hours' => float, 'effectiveReq' => int]
        foreach ($rows as $r) {
            $sid = $r['shift_id'];
            $pid = $r['platform_id'];
            $uid = $r['unit_id'] ?? null; // null if no unit selected

            // Determine effective requirement (unit hours override platform hours)
            $effectiveReq = (int) ($uid && isset($unitHours[$uid]) && $unitHours[$uid]
                ? $unitHours[$uid]
                : ($platformHours[$pid] ?? 8));
            if ($effectiveReq <= 0) {
                $effectiveReq = 8;
            }

            $hrsRow = round((float) $r['hours'], 2);

            // Use unit_id as key if present, otherwise use 'null' as key for platform-only grouping
            $unitKey = $uid ?? 'null';

            if (!isset($byShiftPlatUnit[$sid][$pid][$unitKey])) {
                $byShiftPlatUnit[$sid][$pid][$unitKey] = [
                    'hours'        => 0.0,
                    'effectiveReq' => $effectiveReq,
                    'unit_id'      => $uid,
                ];
            }

            // Sum hours first (don't calculate locums per row)
            $byShiftPlatUnit[$sid][$pid][$unitKey]['hours'] += $hrsRow;
            // Keep the effective requirement (prefer unit override if available)
            if ($uid && isset($unitHours[$uid]) && $unitHours[$uid]) {
                $byShiftPlatUnit[$sid][$pid][$unitKey]['effectiveReq'] = (int) $unitHours[$uid];
            }
        }

        // Now calculate locums from the total hours for each Shift×Platform×Unit group
        // Then aggregate by Shift×Platform for the breakdown
        $byShiftPlat = []; // [shift_id][platform_id] => ['hours' => float, 'locums' => int, 'remainder' => float]
        foreach ($byShiftPlatUnit as $sid => $platMap) {
            foreach ($platMap as $pid => $unitMap) {
                $platformTotalHours    = 0.0;
                $platformTotalLocums   = 0;
                $platformTotalRemainder = 0.0;

                foreach ($unitMap as $unitKey => $data) {
                    $totalHours   = round((float) ($data['hours'] ?? 0), 2);
                    $effectiveReq = (int) ($data['effectiveReq'] ?? 8);
                    if ($effectiveReq <= 0) {
                        $effectiveReq = 8;
                    }

                    // Calculate locums from total hours for this specific unit/platform combination
                    $locums    = (int) floor($totalHours / $effectiveReq);
                    $remainder = round($totalHours - ($locums * $effectiveReq), 2);

                    // Aggregate for the platform
                    $platformTotalHours    += $totalHours;
                    $platformTotalLocums   += $locums;
                    $platformTotalRemainder += $remainder;
                }

                if (!isset($byShiftPlat[$sid][$pid])) {
                    $byShiftPlat[$sid][$pid] = [
                        'hours'     => 0.0,
                        'locums'    => 0,
                        'remainder' => 0.0,
                    ];
                }

                $byShiftPlat[$sid][$pid]['hours']     = round($platformTotalHours, 2);
                $byShiftPlat[$sid][$pid]['locums']    = $platformTotalLocums;
                $byShiftPlat[$sid][$pid]['remainder'] = round($platformTotalRemainder, 2);
            }
        }

        // Build per-shift breakdown using platform thresholds
        $perShiftBreakdown = [
            'required_hours' => 'per_platform',
            'shifts'         => [],
            'grand'          => ['total_hours' => 0.0, 'locums' => 0, 'amount' => 0.0, 'remainder' => 0.0],
        ];

        $grandHours = 0.0;
        $grandLocums = 0;
        $grandRemainderHours = 0.0;

        foreach ($byShiftPlat as $sid => $platMap) {
            $shiftTotalHours = 0.0;
            $shiftLocums     = 0;
            $shiftRemainder  = 0.0;

            $platformsBreak = [];
            foreach ($platMap as $pid => $hrs) {
                $reqHrs = (int) ($platformHours[$pid] ?? 8);
                $hrsVal = round((float) ($hrs['hours'] ?? 0), 2);
                $locums = (int) ($hrs['locums'] ?? 0);
                $remainder = round((float) ($hrs['remainder'] ?? 0), 2);

                $platformsBreak[] = [
                    'platform_id'  => $pid,
                    'platform'     => (string)($platformNames[$pid] ?? 'Unknown'),
                    'required'     => $reqHrs,
                    'hours'        => $hrsVal,
                    'locums'       => $locums,
                    'remainder'    => $remainder,
                ];

                $shiftTotalHours += $hrsVal;
                $shiftLocums     += $locums;
                $shiftRemainder  += $remainder;
            }

            $perShiftBreakdown['shifts'][(string)$sid] = [
                'shift_id'     => $sid,
                'name'         => (string)($shiftNames[$sid] ?? 'Unknown'),
                'platforms'    => $platformsBreak,
                'total_hours'  => round($shiftTotalHours, 2),
                'locums'       => $shiftLocums,
                'remainder'    => round($shiftRemainder, 2),
                'amount'       => round($shiftLocums * $rate, 2),
            ];

            $grandHours       += $shiftTotalHours;
            $grandLocums      += $shiftLocums;
            $grandRemainderHours += $shiftRemainder;
        }

        $perShiftBreakdown['grand']['total_hours'] = round($grandHours, 2);
        $perShiftBreakdown['grand']['locums']      = (int)$grandLocums;
        $perShiftBreakdown['grand']['amount']      = round($grandLocums * $rate, 2);
        $perShiftBreakdown['grand']['remainder']   = round($grandRemainderHours, 2);

        // "Eligible days" - Group by date first, then check if total hours per day meet requirement
        $eligibleDaysCount = 0;
        $eligibleHoursSum  = 0.0;

        // Group entries by date
        $hoursByDate = [];
        foreach ($rows as $r) {
            $dateKey = $r['iso_date'];
            if (!isset($hoursByDate[$dateKey])) {
                $hoursByDate[$dateKey] = [
                    'total_hours' => 0.0,
                    'platforms' => []
                ];
            }
            $hoursByDate[$dateKey]['total_hours'] += $r['hours'];

            // Track platform requirements for this date
            $pid = $r['platform_id'];
            $uid = $r['unit_id'] ?? null;
            $req = (int) ($uid && isset($unitHours[$uid]) && $unitHours[$uid]
                ? $unitHours[$uid]
                : ($platformHours[$pid] ?? 8));
            if ($req <= 0) {
                $req = 8;
            }
            if (!isset($hoursByDate[$dateKey]['platforms'][$pid])) {
                $hoursByDate[$dateKey]['platforms'][$pid] = [
                    'required' => $req,
                    'hours' => 0.0
                ];
            }
            $hoursByDate[$dateKey]['platforms'][$pid]['hours'] += $r['hours'];
        }

        // Count eligible days: a day is eligible if at least one platform on that day meets its requirement
        foreach ($hoursByDate as $dateKey => $dateData) {
            $dayIsEligible = false;
            $dayTotalHours = 0.0;

            foreach ($dateData['platforms'] as $pid => $platData) {
                $dayTotalHours += $platData['hours'];
                // Day is eligible if any platform on that day meets its requirement
                if ($platData['hours'] >= $platData['required']) {
                    $dayIsEligible = true;
                }
            }

            if ($dayIsEligible) {
                $eligibleDaysCount += 1;
                $eligibleHoursSum += $dayTotalHours;
            }
        }
        $eligibleHoursSum = round($eligibleHoursSum, 2);

        // Use grand total hours (all hours claimed) instead of eligible hours for total_hours
        // This ensures consistency between the summary table and the approver view
        $totalHoursClaimed = round($grandHours, 2);

        // Distinct ids actually selected
        $distinctUnitIds     = collect($rows)->pluck('unit_id')->filter()->unique()->values()->all();
        $distinctPlatformIds = collect($rows)->pluck('platform_id')->filter()->unique()->values()->all();

        // === Transaction: update & reset workflow, create new approver step ===
        DB::beginTransaction();

        try {
            // Update the LocumRequest
            $locumRequest->update([
                'locum_month'           => $validated['locum_month'],
                'locum_year'            => $selYearNum,
                'number_of_days'        => $eligibleDaysCount,
                'total_hours'           => $totalHoursClaimed,
                'grand_total_locums'     => (int)$perShiftBreakdown['grand']['locums'],
                'total_amount'          => (float)$perShiftBreakdown['grand']['amount'],
                'total_amount_payable'  => (float)$perShiftBreakdown['grand']['amount'],
                'per_shift_breakdown'  => $perShiftBreakdown,
                'worked_days'           => $validated['worked_days'],
                'description'           => $validated['description'] ?? $locumRequest->description,
                'unit_ids'             => $distinctUnitIds,
                'platform_ids'         => $distinctPlatformIds,
            ]);

            // Reset / resubmit workflow
            $workflow = $locumRequest->workflow;

            if (!$workflow) {
                $workflow = Workflow::create([
                    'user_id'             => $user->id,
                    'locum_request_id'    => $locumRequest->id,
                    'work_flow_status'    => 0,
                    'work_flow_completed' => 0,
                ]);
            } else {
                // Mark the whole workflow as active again
                $workflow->update([
                    'work_flow_status'    => 0,
                    'work_flow_completed' => 0,
                ]);

                // Mark any pending rows as "superseded" (status=2) to avoid duplicates
                $workflow->histories()
                    ->where('status', 0)
                    ->update([
                        'status'        => 2,
                        'action_taken'  => 'Superseded by resubmission',
                        'decision_date' => now(), // if this column exists
                    ]);
            }

            // Decide flow based on requestor's department settings (same as store)
            $dept       = $user->department;
            $flowThree  = (bool) ($dept->has_three_level_approval ?? false);     // LM → HEC → HR
            $flowPlat   = (bool) ($dept->has_incharge_platform_flow ?? false);   // In-Charge → Platform → LM → HR
            $flowILHH   = (bool) ($dept->has_incharge_lm_hec_flow ?? false);     // In-Charge → LM → HEC → HR
            $flowStd    = !$flowThree && !$flowPlat && !$flowILHH;               // LM → HR (default)

            $approvalFlowLabel = $flowPlat
                ? 'incharge_platform'
                : ($flowILHH
                    ? 'incharge_lm_hec'
                    : ($flowThree ? 'three_level' : 'standard'));

            $locumRequest->update(['approval_flow' => $approvalFlowLabel]);

            // Helper: resolve LM for this department
            $resolveLM = function (int $deptId): ?\App\Models\User {
                return \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
                    ->where('deptId', $deptId)
                    ->first();
            };

            // Seed first approver(s) by flow (same logic as store)
            if ($flowPlat || $flowILHH) {
                // Starts with In-Charge
                $units = \App\Models\Unit::whereIn('id', $distinctUnitIds)->get(['id', 'name', 'incharge_user_id']);
                $inchargeUserIds = [];

                foreach ($units as $u) {
                    if (!$u->incharge_user_id) {
                        \DB::rollBack();
                        return back()->withErrors([
                            'worked_days' => "Unit \"{$u->name}\" has no assigned in-charge."
                        ])->withInput();
                    }

                    $incharge = \App\Models\User::where('id', $u->incharge_user_id)
                        ->whereHas('roles', fn($q) => $q->where('name', 'incharge'))
                        ->first();

                    if (!$incharge) {
                        \DB::rollBack();
                        return back()->withErrors([
                            'worked_days' => "Assigned user for unit \"{$u->name}\" is not an In-Charge."
                        ])->withInput();
                    }

                    \App\Models\WorkFlowHistory::create([
                        'work_flow_id'         => $workflow->id,
                        'forwarded_by'         => $user->id,
                        'attended_by'          => $incharge->id,
                        'step_name'            => 'Incharge Approval',
                        'action_taken'         => 'Forwarded',
                        'status'               => 0,
                        'remark'               => 'Locum request re-submitted for in-charge approval.',
                        'locum_request_status' => 1, // pending in-charge
                    ]);

                    $inchargeUserIds[] = $incharge->id;
                }

                $locumRequest->update(['incharge_user_ids' => array_values(array_unique($inchargeUserIds))]);
            } else {
                // Starts with Line Manager (Standard or Three-Level)
                $lm = $resolveLM((int) $user->deptId);
                if ($lm) {
                    \App\Models\WorkFlowHistory::create([
                        'work_flow_id'         => $workflow->id,
                        'forwarded_by'         => $user->id,
                        'attended_by'          => $lm->id,
                        'step_name'            => 'Line Manager Approval',
                        'action_taken'         => 'Forwarded',
                        'status'               => 0,
                        'remark'               => 'Locum request re-submitted for Line Manager approval.',
                        'locum_request_status' => 3, // LM stage
                    ]);
                } else {
                    // No LM: seed HR directly
                    $hr = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                        ->where('approvelocum', 1)
                        ->first();

                    if (!$hr) {
                        \DB::rollBack();
                        return back()->withErrors([
                            'worked_days' => 'No Line Manager or HR approver configured for your department.'
                        ])->withInput();
                    }

                    \App\Models\WorkFlowHistory::create([
                        'work_flow_id'         => $workflow->id,
                        'forwarded_by'         => $user->id,
                        'attended_by'          => $hr->id,
                        'step_name'            => 'HR Approval',
                        'action_taken'         => 'Forwarded',
                        'status'               => 0,
                        'remark'               => 'Locum request re-submitted for HR approval (no LM configured).',
                        'locum_request_status' => 5,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('LocumRequest update/resubmit failed', [
                'locum_request_id' => $locumRequest->id,
                'error'            => $e->getMessage(),
            ]);
            return back()
                ->withErrors(['error' => 'Failed to re-submit request: ' . $e->getMessage()])
                ->withInput();
        }

        // === Send email to first approver(s) ===
        try {
            $workflow->load('histories', 'user'); // ensure relations for the mailable

            // Get the first pending approver(s)
            $firstApprovers = $workflow->histories()
                ->where('status', 0)
                ->where('action_taken', 'Forwarded')
                ->get();

            foreach ($firstApprovers as $history) {
                $approver = \App\Models\User::find($history->attended_by);
                if ($approver && $approver->email) {
                    Mail::to($approver->email)->queue(
                        new LocumApprovalRequest(
                            locumRequest: $locumRequest,
                            workflow: $workflow,
                            submitter: $user,
                            approver: $approver
                        )
                    );
                    Log::info('Sent LocumApprovalRequest email (resubmission)', [
                        'workflow_id' => $workflow->id,
                        'to'          => $approver->email,
                        'step'        => $history->step_name,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('LocumApprovalRequest send failed (resubmission)', [
                'workflow_id' => $workflow->id ?? null,
                'error'       => $e->getMessage(),
            ]);
            // Do not fail the request just because email failed
        }

        Log::info('LocumRequest updated & resubmitted:', [
            'locum_request_id' => $locumRequest->id,
            'workflow_id'      => $workflow->id,
        ]);

        return redirect()
            ->route('locum-requests.index')
            ->with('success', 'Locum request updated and re-submitted for approval.');
    }

    public function destroy($id)
    {
        $locumRequest = LocumRequest::where('user_id', auth()->id())->findOrFail($id);

        if ($locumRequest->status !== 'pending') {
            return redirect()->route('locum-requests.index')->with('error', 'Only pending requests can be deleted.');
        }

        $locumRequest->delete();

        Log::info('Deleted LocumRequest:', ['id' => $id]);

        return redirect()->route('locum-requests.index')->with('success', 'Locum request deleted successfully.');
    }
}
