@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    {{-- @include('includes.loader') --}}
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- ====== THEME / TOKENS ====== -->
            <style>
                :root {
                    --primary: #4caf50;
                    --primary-600: #388e3c;
                    --primary-50: #e8f5e9;
                    --info: #0288d1;
                    --info-600: #0277bd;
                    --warning: #f57c00;
                    --danger: #d32f2f;
                    --text: #1e2a44;
                    --muted: #64748b;
                    --bg: #f8fafc;
                    --radius: 12px;
                    --focus: 0 0 0 4px rgba(76, 175, 80, 0.3);
                    --shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
                    --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.1);
                    --border: #e2e8f0;
                }

                body {
                    background: var(--bg);
                    color: var(--text);
                    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
                    line-height: 1.6;
                }

                .page-title {
                    font-size: 1.75rem;
                    font-weight: 700;
                    margin-bottom: 1.5rem;
                    color: var(--text);
                }

                .card {
                    border: none;
                    border-radius: var(--radius);
                    box-shadow: var(--shadow);
                    background: #fff;
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                    margin-bottom: 1.5rem;
                    overflow: hidden;
                }

                .card:hover {
                    transform: translateY(-4px);
                    box-shadow: var(--shadow-lg);
                }

                .card-header {
                    background: var(--primary-50);
                    border-bottom: 1px solid var(--border);
                    padding: 1rem 1.25rem;
                    font-weight: 600;
                    color: var(--text);
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                }

                .card-body {
                    padding: 1.5rem;
                }

                .alert {
                    border-radius: var(--radius);
                    padding: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                }

                .grid-cards {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 1rem;
                }

                .tile {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 1rem;
                    border: 1px solid var(--border);
                    border-radius: var(--radius);
                    background: #fff;
                    text-decoration: none;
                    color: inherit;
                    box-shadow: var(--shadow);
                    transition: all 0.2s ease;
                    position: relative;
                    /* For positioning tile__cta */
                }

                .tile:hover {
                    border-color: var(--primary-600);
                    box-shadow: var(--shadow-lg);
                    background: var(--primary-50);
                }

                .tile:focus,
                .tile:focus-visible {
                    outline: none;
                    box-shadow: var(--focus);
                }

                .tile {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                }

                .tile__icon i {
                    font-size: 1.25rem;
                    color: var(--primary);
                    margin-bottom: 0.5rem;
                    /* small spacing before title */
                }

                .tile__title {
                    margin: 0;
                    font-size: 1rem;
                    font-weight: 400;
                }


                .tile__subtitle {
                    margin: 0.25rem 0 0;
                    font-size: 0.875rem;
                    color: var(--muted);
                    line-height: 1.3;
                }

                .tile__cta {
                    font-size: 0.875rem;
                    font-weight: 500;
                    color: var(--primary);
                    padding: 0.25rem 0.75rem;
                    border: 1px solid var(--primary-600);
                    border-radius: 999px;
                    transition: all 0.2s ease;
                    opacity: 0;
                    /* Hidden by default */
                    transform: translateY(10px);
                    /* Slight animation for appearance */
                    position: absolute;
                    right: 1rem;
                }

                .tile:hover .tile__cta {
                    background: var(--primary);
                    color: #fff;
                    border-color: var(--primary);
                    opacity: 1;
                    /* Show on hover */
                    transform: translateY(0);
                    /* Animate into place */
                }

                .scrollable {
                    max-height: 320px;
                    overflow-y: auto;
                    scrollbar-width: thin;
                    scrollbar-color: var(--primary) var(--primary-50);
                }

                .scrollable::-webkit-scrollbar {
                    width: 8px;
                }

                .scrollable::-webkit-scrollbar-track {
                    background: var(--primary-50);
                    border-radius: 4px;
                }

                .scrollable::-webkit-scrollbar-thumb {
                    background: var(--primary);
                    border-radius: 4px;
                }

                .list-unstyled li {
                    padding: 0.75rem;
                    border-radius: 8px;
                    transition: background 0.2s ease;
                }

                .list-unstyled li:hover {
                    background: var(--primary-50);
                }

                /* Responsive */
                @media (max-width: 1200px) {
                    .grid-cards {
                        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                    }
                }

                @media (max-width: 768px) {
                    .page-title {
                        font-size: 1.5rem;
                    }

                    .grid-cards {
                        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                    }
                }

                @media (max-width: 576px) {
                    .page-title {
                        font-size: 1.25rem;
                    }

                    .grid-cards {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <!-- ====== FLASH MESSAGES ====== -->
            @if (session('success') || session('error'))
                <div class="mb-4">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                </div>
            @endif

            <!-- ====== LICENSE STATUS ALERT FOR CLINICAL USERS ====== -->
            @if (isset($isClinicalDepartment) && $isClinicalDepartment && isset($licenseStatus))
                @if (!$licenseStatus['has_license'] || $licenseStatus['is_expired'])
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                            Professional License Alert
                        </div>
                        <div class="card-body">
                            @if (!$licenseStatus['has_license'])
                                <div class="alert alert-danger mb-0" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-times-circle me-2" style="font-size: 1.25rem;"></i>
                                        <div class="flex-grow-1">
                                            <strong>No Professional License Found</strong>
                                            <p class="mb-0 mt-1">You are assigned to a clinical job title, but no professional license information has been recorded. Please contact HR BP.</p>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($licenseStatus['is_expired'])
                                <div class="alert alert-danger mb-0" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle me-2" style="font-size: 1.25rem;"></i>
                                        <div class="flex-grow-1">
                                            <strong>Professional License Expired</strong>
                                            <p class="mb-0 mt-1">
                                                Your professional license expired on 
                                                <strong>{{ \Carbon\Carbon::parse($licenseStatus['expiry_date'])->format('d F Y') }}</strong> 
                                                ({{ abs($licenseStatus['days_until_expiry']) }} day{{ abs($licenseStatus['days_until_expiry']) > 1 ? 's' : '' }} ago). 
                                                Please contact HR BP.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif ($licenseStatus['expiring_soon'])
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                            Professional License Expiring Soon
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning mb-0" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock me-2" style="font-size: 1.25rem;"></i>
                                    <div class="flex-grow-1">
                                        <strong>License Expiring Soon</strong>
                                        <p class="mb-0 mt-1">
                                            Your professional license will expire on 
                                            <strong>{{ \Carbon\Carbon::parse($licenseStatus['expiry_date'])->format('d F Y') }}</strong>
                                            @if ($licenseStatus['days_until_expiry'] == 0)
                                                <strong>(today)</strong>.
                                            @else
                                                (in {{ abs($licenseStatus['days_until_expiry']) }} day{{ abs($licenseStatus['days_until_expiry']) > 1 ? 's' : '' }}).
                                            @endif
                                            Please contact HR BP.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <!-- ====== GREETING + PENDING ACTIONS ====== -->
            @can('approve requests')
                @php
                    $hour = now()->hour;
                    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
                    $user = auth()->user();

                    // Get pending form approvals (ICT, HR, Change Request, Clearance, etc.)
                    $pendingFormCount = \App\Models\Workflow::join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
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
                        ->count();
                    
                    $pendingClearanceCount = 0;
                    if ($user->can('access clearance form')) {
                        // For all users (including HR): Only count pending approvals (status = 0)
                        $pendingClearanceCount = DB::table('clearance_work_flow_histories')
                            ->join('clearance_work_flows', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
                            ->join('clearance_forms', 'clearance_forms.id', '=', 'clearance_work_flows.requested_resource_id')
                            ->where('clearance_work_flow_histories.attended_by', $user->id)
                            ->where('clearance_work_flow_histories.status', 0)
                            ->where('clearance_forms.status', '!=', 'rejected')
                            ->where(function($query) {
                                $query->where('clearance_work_flows.work_flow_completed', '!=', 1)
                                      ->orWhereNull('clearance_work_flows.work_flow_completed');
                            })
                            ->whereIn('clearance_work_flow_histories.id', function($subquery) use ($user) {
                                $subquery->selectRaw('MAX(clearance_work_flow_histories.id)')
                                    ->from('clearance_work_flow_histories')
                                    ->where('clearance_work_flow_histories.status', 0)
                                    ->where('clearance_work_flow_histories.attended_by', $user->id)
                                    ->groupBy('clearance_work_flow_histories.work_flow_id');
                            })
                            ->count();
                    }
                    
                    $totalPendingForms = $pendingFormCount;
                    
                    // Combine queries for efficiency
                    // Get pending recruitment requisitions count for HR
                    $pendingRecruitmentCount = 0;
                    if ($user->hasRole('hr')) {
                        $pendingRecruitmentCount = \App\Models\RecruitmentRequisition::whereIn('status', ['hec_no_objection_in_budget', 'ceo_approved', 'ready_for_hr_processing'])
                            ->count();
                    }
                    
                    $pendingCounts = [
                        'forms' => $totalPendingForms,
                        'clearance' => $pendingClearanceCount,
                        'locum' => DB::table('work_flow_histories')
                            ->where('attended_by', $user->id)
                            ->where('status', 0)
                            ->where(function ($q) {
                                $q->whereNotNull('locum_agreement_status')->orWhereNotNull('locum_request_status');
                            })
                            ->count(),
                        'oncall' => DB::table('work_flow_histories')
                            ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                            ->where('work_flow_histories.attended_by', $user->id)
                            ->where('work_flow_histories.status', 0)
                            ->whereNotNull('workflows.on_call_request_id')
                            ->count(),
                        'recruitment' => $pendingRecruitmentCount,
                    ];
                    $hasPending = array_sum($pendingCounts) > 0;
                @endphp

                @if ($hasPending)
                    <div class="card mb-4">
                        <div class="card-header">
                            <svg width="20" height="20" fill="var(--primary)" viewBox="0 0 20 20" aria-hidden="true">
                                <path
                                    d="M10 15a5 5 0 100-10 5 5 0 000 10zm0 3a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm0-17a1 1 0 011 1v1a1 1 0 01-2 0V2a1 1 0 011-1zm8 8a1 1 0 010 2h-1a1 1 0 010-2h1zM3 10a1 1 0 010 2H2a1 1 0 010-2h1zm12.071-5.071a1 1 0 011.415 1.415l-.707.707a1 1 0 01-1.414-1.414l.706-.708zM5.636 15.657a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414l-.707.707zm10.435 1.414a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414zM5.636 4.343a1 1 0 010 1.414L4.93 6.464a1 1 0 01-1.415-1.414l.707-.707a1 1 0 011.414 0z" />
                            </svg>
                            {{ $greeting }}, {{ ucfirst($user->fname) }}!
                        </div>
                        <div class="card-body py-2">
                            <p class="text-muted mb-2 small">You have pending actions awaiting your attention:</p>
                            @if ($pendingCounts['forms'] > 0)
                                <div class="alert alert-warning mb-2 alert-dismissible fade show py-2 px-3" style="font-size: 0.875rem;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-bell me-2" aria-hidden="true" style="font-size: 0.9rem;"></i>
                                        <div class="flex-grow-1">
                                            <strong style="font-size: 0.9rem;">{{ $pendingCounts['forms'] }} Pending Form Approval{{ $pendingCounts['forms'] > 1 ? 's' : '' }}</strong>
                                            <span class="ms-2">
                                                <a href="{{ route('requestapprove.index') }}" class="text-decoration-none fw-bold" style="font-size: 0.85rem;">Review Now</a>
                                            </span>
                                        </div>
                                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"
                                            aria-label="Close" style="font-size: 0.7rem;"></button>
                                    </div>
                                </div>
                            @endif
                            @if ($pendingCounts['clearance'] > 0)
                                <div class="alert alert-danger mb-2 alert-dismissible fade show py-2 px-3" style="font-size: 0.875rem;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clipboard-check me-2" aria-hidden="true" style="font-size: 0.9rem;"></i>
                                        <div class="flex-grow-1">
                                            <strong style="font-size: 0.9rem;">{{ $pendingCounts['clearance'] }} Pending Clearance Form{{ $pendingCounts['clearance'] > 1 ? 's' : '' }}</strong>
                                            <span class="ms-2">
                                                <a href="{{ route('clearance.index') }}" class="text-decoration-none fw-bold" style="font-size: 0.85rem; color: #721c24;">Review Now</a>
                                            </span>
                                        </div>
                                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"
                                            aria-label="Close" style="font-size: 0.7rem;"></button>
                                    </div>
                                </div>
                            @endif
                            @if ($pendingCounts['locum'])
                                <div class="alert alert-info mb-2 alert-dismissible fade show py-2 px-3" style="font-size: 0.875rem;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-medical me-2" aria-hidden="true" style="font-size: 0.9rem;"></i>
                                        <div class="flex-grow-1">
                                            <strong style="font-size: 0.9rem;">{{ $pendingCounts['locum'] }} Pending Locum
                                                Claim{{ $pendingCounts['locum'] > 1 ? 's' : '' }}</strong>
                                            <span class="ms-2">
                                                <a href="{{ route('locum-requests.viewAgreementRequest') }}"
                                                    class="text-decoration-none fw-bold" style="font-size: 0.85rem;">Review Now</a>
                                            </span>
                                        </div>
                                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"
                                            aria-label="Close" style="font-size: 0.7rem;"></button>
                                    </div>
                                </div>
                            @endif
                            @if ($pendingCounts['oncall'])
                                <div class="alert alert-info mb-2 alert-dismissible fade show py-2 px-3" style="font-size: 0.875rem;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-phone me-2" aria-hidden="true" style="font-size: 0.9rem;"></i>
                                        <div class="flex-grow-1">
                                            <strong style="font-size: 0.9rem;">{{ $pendingCounts['oncall'] }} Pending On-Call
                                                Claim{{ $pendingCounts['oncall'] > 1 ? 's' : '' }}</strong>
                                            <span class="ms-2">
                                                <a href="{{ route('oncall_requests.view') }}" class="text-decoration-none fw-bold" style="font-size: 0.85rem;">Review Now</a>
                                            </span>
                                        </div>
                                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"
                                            aria-label="Close" style="font-size: 0.7rem;"></button>
                                    </div>
                                </div>
                            @endif
                            @if ($pendingCounts['recruitment'] > 0)
                                <div class="alert alert-primary mb-2 alert-dismissible fade show py-2 px-3" style="font-size: 0.875rem;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tie me-2" aria-hidden="true" style="font-size: 0.9rem;"></i>
                                        <div class="flex-grow-1">
                                            <strong style="font-size: 0.9rem;">{{ $pendingCounts['recruitment'] }} Pending Recruitment Requisition{{ $pendingCounts['recruitment'] > 1 ? 's' : '' }}</strong>
                                            <span class="ms-2">
                                                <a href="{{ route('recruitment-requisitions.index') }}" class="text-decoration-none fw-bold" style="font-size: 0.85rem;">Review Now</a>
                                            </span>
                                        </div>
                                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"
                                            aria-label="Close" style="font-size: 0.7rem;"></button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endrole

            <!-- ====== QUICK ACTIONS (FULL WIDTH) ====== -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-bolt me-2" aria-hidden="true"></i> Quick Actions
                </div>
                <div class="card-body">
                    @php
                        $actions = [
                            ['route' => 'form.index', 'icon' => 'server', 'title' => 'IT Access Form'],
                            ['route' => 'bank-details.index', 'icon' => 'university', 'title' => 'Bank Details'],
                            [
                                'route' => 'nhif_registration.index',
                                'icon' => 'briefcase-medical',
                                'title' => 'NHIF Form',
                            ],
                            ['route' => 'loan-declarations.index', 'icon' => 'file-invoice-dollar', 'title' => 'HESLB'],
                            ['route' => 'IDCard.create', 'icon' => 'id-card', 'title' => 'ID Card'],
                            ['route' => 'change_request.create', 'icon' => 'exchange-alt', 'title' => 'Change Request'],
                            ['route' => 'locum-requests.index', 'icon' => 'money-bill', 'title' => 'Locum Claims'],
                            ['route' => 'oncall_requests.index', 'icon' => 'phone', 'title' => 'On-Call Claims'],
                        ];
                    @endphp
                    <div class="grid-cards">
                        @foreach ($actions as $action)
                            <a href="{{ route($action['route']) }}" class="tile" aria-label="{{ $action['title'] }}">
                                <div class="tile__icon">
                                    <i class="fas fa-{{ $action['icon'] }}" aria-hidden="true"></i>
                                </div>
                                <div class="tile__body">
                                    <h5 class="tile__title">{{ $action['title'] }}</h5>
                                    {{-- <p class="tile__subtitle">{{ $action['desc'] }}</p> --}}
                                </div>
                                {{-- <span class="tile__cta">Open</span> --}}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ====== SECOND ROW: NOTICE BOARD + HR & POLICIES ====== -->
            <div class="row">
                <!-- Notice Board -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                            <a href="{{ route('announcements.index') }}" class="text-decoration-none text-dark">Notice
                                Board</a>
                        </div>
                        <div class="card-body scrollable">
                            @php
                                $dashboardAnnouncements = App\Models\Announcement::query()
                                    ->latest()
                                    ->take(5)
                                    ->with('user')
                                    ->get();
                            @endphp
                            @if ($dashboardAnnouncements->isEmpty())
                                <p class="text-muted mb-0">No announcements available.</p>
                            @else
                                <ul class="list-unstyled mb-0">
                                    @foreach ($dashboardAnnouncements as $announcement)
                                        <li class="mb-2">
                                            <a href="{{ Route::has('announcements.show') ? route('announcements.show', $announcement) : route('announcements.index') }}"
                                                class="text-decoration-none text-dark d-block"
                                                title="{{ $announcement->title }}">
                                                <strong class="d-block">
                                                    <i class="fas fa-bullhorn me-2 text-secondary" style="font-size:0.9rem"
                                                        aria-hidden="true"></i>
                                                    {{ Str::limit($announcement->title, 50) }}
                                                </strong>
                                                <small class="text-muted">
                                                    by {{ $announcement->user->username ?? 'Unknown' }},
                                                    {{ $announcement->created_at->diffForHumans() }}
                                                </small>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="text-end mt-3">
                                    <a href="{{ route('announcements.index') }}"
                                        class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- CCBRT Policies & HR Documents -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-book-open me-2" aria-hidden="true"></i> CCBRT Policies & HR Documents
                        </div>
                        <div class="card-body">
                            {{-- <p class="mb-3 text-muted">Access HR documents and CCBRT policies below:</p> --}}
                            <div class="grid-cards" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                                <a href="{{ route('HrDocuments.index') }}" class="tile" aria-label="HR Documents">
                                    <div class="tile__icon"><i class="fas fa-file-alt" aria-hidden="true"></i></div>
                                    <div>
                                        <h5 class="tile__title">HR Documents</h5>
                                        {{-- <p class="tile__subtitle">Policies, letters, and templates.</p> --}}
                                    </div>
                                    <span class="tile__cta">Open</span>
                                </a>
                                <a href="{{ route('policies.user') }}" class="tile" aria-label="CCBRT Policies">
                                    <div class="tile__icon"><i class="fas fa-book" aria-hidden="true"></i></div>
                                    <div>
                                        <h5 class="tile__title">CCBRT Policies</h5>
                                        {{-- <p class="tile__subtitle">Organisation policies and guidelines.</p> --}}
                                    </div>
                                    <span class="tile__cta">Open</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
