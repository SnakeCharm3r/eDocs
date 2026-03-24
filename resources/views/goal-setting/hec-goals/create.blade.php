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
                        <h3 class="page-title mb-0">Create HEC Goal</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('goal-setting.cycles.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">HEC strategic goal</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('goal-setting.hec-goals.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cycle <span class="text-danger">*</span></label>
                                <select name="goal_cycle_id" class="form-select" required>
                                    <option value="">Select cycle</option>
                                    @foreach ($cycles as $cycle)
                                        <option value="{{ $cycle->id }}" {{ (string) old('goal_cycle_id', $selectedCycleId) === (string) $cycle->id ? 'selected' : '' }}>
                                            {{ $cycle->name }} ({{ ucfirst($cycle->status) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">KPIs (optional)</h5>
                            <button type="button" class="btn btn-sm btn-outline-success" id="addKpiBtn">
                                <i class="fas fa-plus"></i> Add KPI
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle" id="kpiTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%;">Name</th>
                                        <th style="width: 15%;">Target</th>
                                        <th style="width: 10%;">Unit</th>
                                        <th style="width: 10%;">Weight</th>
                                        <th style="width: 15%;">Baseline</th>
                                        <th style="width: 15%;">Due Date</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('goal-setting.cycles.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Save</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const tableBody = document.querySelector('#kpiTable tbody');
            const addBtn = document.getElementById('addKpiBtn');

            function addRow() {
                const index = tableBody.children.length;
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td><input type="text" class="form-control form-control-sm" name="kpis[${index}][name]" required></td>
                    <td><input type="text" class="form-control form-control-sm" name="kpis[${index}][target]"></td>
                    <td><input type="text" class="form-control form-control-sm" name="kpis[${index}][unit]"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="kpis[${index}][weight]"></td>
                    <td><input type="text" class="form-control form-control-sm" name="kpis[${index}][baseline]"></td>
                    <td><input type="date" class="form-control form-control-sm" name="kpis[${index}][due_date]"></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-kpi">X</button>
                    </td>
                `;

                tableBody.appendChild(tr);
            }

            addBtn.addEventListener('click', addRow);

            tableBody.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-kpi');
                if (!btn) return;
                btn.closest('tr').remove();
            });
        })();
    </script>
@endpush
