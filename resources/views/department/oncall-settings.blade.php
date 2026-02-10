@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="page-title mb-2 text-muted">On-Call Approval Settings</h6>
                    </div>
                    <div class="col-auto">
                        <a href="{{ url('/departments') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Departments
                        </a>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="alert alert-info border-0 shadow-sm">
                <div class="fw-semibold mb-1">Notes</div>
                <ul class="mb-0 ps-3">
                    <li><em>Standard</em> routes: <strong>Line Manager → HR</strong>.</li>
                    <li><em>Three-Level</em> routes: <strong>Line Manager → HEC → HR</strong>.</li>
                </ul>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="oncallTable" class="table table-hover table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:6%">#</th>
                                    <th style="width:28%">Department</th>
                                    <th style="width:41%">Current Approval Process</th>
                                    <th style="width:25%">Set Approval Process</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($departments as $department)
                                    @php
                                        $isStandard = !$department->has_oncall_three_level_approval;
                                        $is3Level = (bool) $department->has_oncall_three_level_approval;

                                        $currentText = $is3Level ? 'Line Manager → HEC → HR' : 'Line Manager → HR';
                                    @endphp

                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $department->dept_name }}</td>
                                        <td>{{ $currentText }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('departments.update-oncall-settings') }}"
                                                class="d-inline-flex align-items-center gap-2 approval-form">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="department_id" value="{{ $department->id }}" />

                                                <select name="approval_flow" class="form-select form-select-sm flow-select">
                                                    <option value="standard" {{ $isStandard ? 'selected' : '' }}>
                                                        Standard — Line Manager → HR
                                                    </option>
                                                    <option value="three_level" {{ $is3Level ? 'selected' : '' }}>
                                                        Three-Level — Line Manager → HEC → HR
                                                    </option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No departments found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Auto-submit on selection change --}}
    <script>
        document.addEventListener('change', (e) => {
            if (e.target.matches('.flow-select')) {
                const form = e.target.closest('form');
                if (form) form.requestSubmit();
            }
        });
    </script>

    @include('components.datatable', ['id' => 'oncallTable'])
@endsection
