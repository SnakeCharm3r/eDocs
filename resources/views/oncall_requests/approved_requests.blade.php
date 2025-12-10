{{-- resources/views/oncall_requests/approved_requests.blade.php --}}
@extends('layouts.template')

@section('content')
    @include('sweetalert::alert')

    @php
        $me = auth()->id();

        // Values provided by controller (defaults applied there)
        $payYear = $payYear ?? \Carbon\Carbon::now('Africa/Dar_es_Salaam')->year;
        $payMonth = $payMonth ?? \Carbon\Carbon::now('Africa/Dar_es_Salaam')->format('F');

        // Optional departments (scoped)
        $deptOptions = collect($departments ?? [])->map(fn($d) => (object) ['id' => $d->id, 'name' => $d->dept_name]);

        // Controller already filtered to: claims APPROVED BY ME within selected month
        $rows = collect($approvedRequests ?? []);

        // KPIs
        $kpiCount = $rows->count();
        $kpiAmount = (float) $rows->sum('total_amount_payable');

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
            'December',
        ];

        $exportUrl = route('oncall_requests.export');
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Header --}}
            <div class="page-header mb-3">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <a href="{{ route('oncall_requests.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <h6 class="text">On-Call Claims Reports</h6>
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
                            @foreach ($monthsList as $m)
                                <option value="{{ $m }}"
                                    {{ (string) $payMonth === (string) $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
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

                    <div class="col-md-2">
                        <div class="d-flex justify-content-end gap-2">
                            <button id="btn-apply" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>
                            </button>
                            <button id="btn-export-payroll" class="btn btn-outline-success"
                                data-export-url="{{ $exportUrl }}">
                                <i class="fas fa-file-excel me-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- KPIs --}}
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3">
                                <div class="text-muted small">Actioned Claims</div>
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
                                <th>#</th> {{-- 1 --}}
                                <th>Staff</th> {{-- 2 (Usernames + Code + Department) --}}
                                <th>Claim Period</th> {{-- 3 (Month + Year) --}}
                                <th>Days</th> {{-- 4 --}}
                                <th>Hours</th> {{-- 5 --}}
                                <th>Amount (TZS)</th> {{-- 6 --}}
                                <th>Education Level</th> {{-- 7 --}}
                                <th>Status</th> {{-- 8 (badge + approve date) --}}
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

                                        // Find MY approval action timestamp (when I approved)
                                        $approveAt = null;
                                        if (optional($r->workflow)->histories) {
                                            $myApprove = $r->workflow->histories->first(function ($h) use ($me) {
                                                return (int) ($h->attended_by ?? 0) === (int) $me &&
                                                    ($h->action_taken ?? '') === 'Approved';
                                            });
                                            if ($myApprove) {
                                                $approveAt = \Carbon\Carbon::parse(
                                                    $myApprove->created_at ?? $myApprove->updated_at,
                                                )
                                                    ->timezone('Africa/Dar_es_Salaam')
                                                    ->format('d M Y, H:i');
                                            }
                                        }

                                        $badgeClass =
                                            $r->status === 'approved'
                                                ? 'bg-success text-dark'
                                                : ($r->status === 'rejected'
                                                    ? 'bg-danger text-white'
                                                    : 'bg-warning text-dark');
                                    @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $fullName }}</div>
                                            <div class="small text-muted">Code: {{ $code }}</div>
                                            <div class="small text-muted">Dept: {{ $deptName }}</div>
                                        </td>
                                        <td>
                                            <div class="text">{{ $period !== '' ? $period : '—' }}</div>
                                        </td>
                                        <td>{{ (int) ($r->number_of_days ?? 0) }}</td>
                                        <td>{{ number_format((float) ($r->total_hours ?? 0), 2) }}</td>
                                        <td>{{ number_format((float) ($r->total_amount_payable ?? 0), 2) }}</td>
                                        <td>{{ $r->education_level ?: '—' }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $badgeClass }}">{{ ucfirst($r->status ?? '—') }}</span>
                                            @if ($approveAt)
                                                <div class="small text-muted mt-1">{{ $approveAt }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- IMPORTANT: exactly 8 <td> to match <th> count --}}
                                <tr>
                                    <td class="text-muted">No items you approved in this month.</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    {{-- Keep your existing shared DataTable include (unchanged) --}}
    @include('components.datatable', ['id' => 'locumTable'])

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Apply filters (reload with year/month + optional dept)
            document.getElementById('btn-apply')?.addEventListener('click', function() {
                const payYear = document.getElementById('filter-pay-year')?.value || '';
                const payMonth = document.getElementById('filter-pay-month')?.value || '';
                const dept = document.getElementById('filter-department')?.value || '';

                const qs = new URLSearchParams();
                if (payYear) qs.set('pay_year', payYear);
                if (payMonth) qs.set('pay_month', payMonth);
                if (dept) qs.set('department', dept);

                window.location = "{{ route('oncall_requests.approved') }}" + (qs.toString() ? ('?' + qs
                    .toString()) : '');
            });

            // Export set (controller computes same population)
            document.getElementById('btn-export-payroll')?.addEventListener('click', function() {
                const url = this.dataset.exportUrl;
                const payYear = document.getElementById('filter-pay-year')?.value || '';
                const payMonth = document.getElementById('filter-pay-month')?.value || '';
                const dept = document.getElementById('filter-department')?.value || '';

                const qs = new URLSearchParams();
                if (payYear) qs.set('pay_year', payYear);
                if (payMonth) qs.set('pay_month', payMonth);
                if (dept) qs.set('department', dept);

                window.location = url + (qs.toString() ? ('?' + qs.toString()) : '');
            });
        });
    </script>
@endsection
