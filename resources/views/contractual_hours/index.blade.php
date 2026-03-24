@extends('layouts.template')

@push('styles')
    <style>
        .contractual-hours-table {
            font-size: 0.9rem;
        }
        .contractual-hours-table th {
            background-color: #007A33;
            color: white;
            font-weight: 600;
        }
        .month-name {
            font-weight: 600;
            color: #007A33;
        }
        .holidays-badge {
            font-size: 0.75rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @include('sweetalert::alert')

            {{-- Page Header --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-clock text-success me-2"></i>Contractual Hours Management
                    </h4>
                </div>
                <div class="d-flex gap-2">
                    <form method="GET" action="{{ route('contractual-hours.index') }}" class="d-inline">
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach(range(date('Y') + 1, 2020, -1) as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('contractual-hours.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Contractual Hours
                    </a>
                </div>
            </div>

            {{-- Contractual Hours Table --}}
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover contractual-hours-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Month</th>
                                    <th>Working Days</th>
                                    <th>Public Holidays</th>
                                    <th>Contractual Hours</th>
                                    <th class="text-center" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($contractualHours as $ch)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="month-name">
                                            {{ \Carbon\Carbon::create($ch->year, $ch->month, 1)->format('F Y') }}
                                        </td>
                                        <td>{{ $ch->working_days }}</td>
                                        <td>
                                            @if($ch->public_holidays && is_array($ch->public_holidays) && count($ch->public_holidays) > 0)
                                                @foreach($ch->public_holidays as $holiday)
                                                    <span class="badge bg-warning text-dark holidays-badge me-1">{{ $holiday }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">No public holiday</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $ch->contractual_hours }}</strong> hours</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('contractual-hours.edit', $ch->id) }}" 
                                                   class="btn btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal{{ $ch->id }}" 
                                                        title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Delete Modal --}}
                                    <div class="modal fade" id="deleteModal{{ $ch->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Contractual Hours
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete contractual hours for <strong>{{ \Carbon\Carbon::create($ch->year, $ch->month, 1)->format('F Y') }}</strong>?</p>
                                                    <p class="text-muted small">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form action="{{ route('contractual-hours.destroy', $ch->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="fas fa-trash-alt me-1"></i>Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No Contractual Hours Found for {{ $year }}</h5>
                                            <p class="text-muted">Click "Add Contractual Hours" to create records for this year.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Notes Section --}}
                    @if($contractualHours->isNotEmpty())
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2"><strong>NB:</strong></h6>
                            <ul class="mb-0 small">
                                <li>The contractual hours vary each month, depending on the number of working days in that respective month.</li>
                                <li>Contractual hours may change if a national event, not included in the pre-defined public holidays is announced by the authorities (any changes will be communicated).</li>
                                <li>Public holidays are automatically excluded from the calculation of contractual hours.</li>
                                <li>The total contractual hours are determined by multiplying the number of working days by 9.</li>
                                <li>Locum hours are not included in the contractual hours; they are counted only after the contractual hours have been fully completed.</li>
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

