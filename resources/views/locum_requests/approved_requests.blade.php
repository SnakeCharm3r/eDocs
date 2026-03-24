{{-- resources/views/locum_requests/approved_requests.blade.php --}}
@extends('layouts.template')

@section('content')
    @include('sweetalert::alert')

    @php
        // Values provided by controller
        $payYear = $payYear ?? \Carbon\Carbon::now('Africa/Dar_es_Salaam')->year;
        $payMonth = $payMonth ?? \Carbon\Carbon::now('Africa/Dar_es_Salaam')->month;

        // Optional departments
        $deptOptions = collect($departments ?? [])->map(fn($d) => (object) ['id' => $d->id, 'name' => $d->dept_name]);

        // Controller already filtered to: claims APPROVED BY HR within selected month
        $rows = collect($approvedRequests ?? []);

        // KPIs
        $kpiCount = $rows->count();
        $kpiAmount = (float) $rows->sum('total_amount_payable');

        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Header --}}
            <div class="page-header mb-3">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <a href="{{ route('locum-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <h6 class="text">Locum Claims Reports</h6>
                    </div>
                </div>
            </div>

            <div class="card card-body">

                {{-- Filters (single row) --}}
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <input id="filter-pay-year" type="number" min="2000" class="form-control"
                            value="{{ (int) $payYear }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select id="filter-pay-month" class="form-control">
                            @foreach ($monthNames as $num => $name)
                                <option value="{{ $num }}" {{ (int) $payMonth === $num ? 'selected' : '' }}>
                                    {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Department (optional)</label>
                        <select id="filter-department" class="form-control">
                            <option value="">All</option>
                            @foreach ($deptOptions as $d)
                                <option value="{{ $d->id }}"
                                    {{ (string) request('department') === (string) $d->id ? 'selected' : '' }}>
                                    {{ $d->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($isHR ?? false)
                        <div class="col-md-3">
                            <label class="form-label">Employee (optional)</label>
                            <input id="filter-employee" type="text" class="form-control"
                                placeholder="Name, username or email" value="{{ $filterUser ?? '' }}">
                        </div>
                    @endif

                    <div class="col-md-{{ $isHR ?? false ? '0' : '3' }}">
                        <div class="d-flex justify-content-end gap-2">
                            <button id="btn-apply" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- KPIs --}}
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3">
                                <div class="text-muted small">Approved Claims</div>
                                <div class="h5 mb-0">{{ number_format($kpiCount) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3">
                                <div class="text-muted small">Total Amount (TZS)</div>
                                <div class="h5 mb-0">{{ number_format($kpiAmount, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="locumTable" class="table table-striped table-hover table-bordered align-middle"
                        style="width:100%">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Staff</th>
                                <th>Claim Period</th>
                                <th>Days</th>
                                <th>Hours</th>
                                <th>Amount (TZS)</th>
                                <th>Education Level</th>
                                <th>Status</th>
                                <th>Approved At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($rows->isNotEmpty())
                                @foreach ($rows as $i => $r)
                                    @php
                                        $fullName =
                                            collect([
                                                optional($r->user)->fname,
                                                optional($r->user)->mname,
                                                optional($r->user)->lname,
                                            ])
                                                ->filter()
                                                ->implode(' ') ?:
                                            '—';
                                        $code = $r->user->ccbrt_code ?? '—';
                                        $deptName =
                                            optional(optional($r->user)->department)->dept_name ??
                                            (optional($r->user)->dept_name ?? '—');
                                        $period = trim(($r->locum_month ?? '—') . ' ' . ($r->locum_year ?? ''));

                                        // Find HR approval timestamp
                                        $approveAt = null;
                                        if (optional($r->workflow)->histories) {
                                            $hrApproval = $r->workflow->histories->firstWhere(
                                                'step_name',
                                                'HR Approval',
                                            );
                                            if ($hrApproval) {
                                                $approveAt = \Carbon\Carbon::parse(
                                                    $hrApproval->created_at ?? $hrApproval->updated_at,
                                                )
                                                    ->timezone('Africa/Dar_es_Salaam')
                                                    ->format('d M Y, H:i');
                                            }
                                        }

                                        $badgeClass =
                                            $r->status === 'approved'
                                                ? 'bg-success'
                                                : ($r->status === 'pending'
                                                    ? 'bg-warning'
                                                    : 'bg-danger');
                                        $educationLevel =
                                            optional($r->locumAgreement)->education_level ??
                                            (optional($r->user)->education_level ?? 'N/A');
                                    @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $fullName }}</strong>
                                                <br><small class="text-muted">{{ $code }}</small>
                                                <br><small class="text-muted">{{ $deptName }}</small>
                                            </div>
                                        </td>
                                        <td>{{ $period ?: '—' }}</td>
                                        <td>{{ $r->number_of_days ?? 0 }}</td>
                                        <td>{{ number_format($r->total_hours ?? 0, 2) }}</td>
                                        <td>{{ number_format($r->total_amount_payable ?? 0, 2) }}</td>
                                        <td>{{ $educationLevel }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $badgeClass }}">{{ ucfirst($r->status ?? 'N/A') }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $approveAt ?? '—' }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="text-muted text-center py-5">
                                        No approved requests found for the selected filters.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    @include('components.datatable', ['id' => 'locumTable'])

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Apply filters (reload with year/month + optional dept + employee)
            document.getElementById('btn-apply')?.addEventListener('click', function() {
                const payYear = document.getElementById('filter-pay-year')?.value || '';
                const payMonth = document.getElementById('filter-pay-month')?.value || '';
                const dept = document.getElementById('filter-department')?.value || '';
                const employee = document.getElementById('filter-employee')?.value || '';

                const qs = new URLSearchParams();
                if (payYear) qs.set('pay_year', payYear);
                if (payMonth) qs.set('pay_month', payMonth);
                if (dept) qs.set('department', dept);
                if (employee) qs.set('employee', employee);

                window.location = "{{ route('locum_requests.approved') }}" + (qs.toString() ? ('?' + qs
                    .toString()) : '');
            });
        });
    </script>
@endsection
