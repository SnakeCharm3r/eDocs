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
                        <h3 class="page-title mb-0">HEC Goal Approvals</h3>
                        <small class="text-muted">Goals submitted to HEC for approval</small>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('goal-setting.cycles.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Cycles
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('goal-setting.hec.inbox') }}" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Cycle</label>
                            <select name="cycle" class="form-select">
                                <option value="">All</option>
                                @foreach ($cycles as $c)
                                    <option value="{{ $c->id }}" {{ (string) $cycleId === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-success">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Pending approvals</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Cycle</th>
                                    <th>Department</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($goals as $i => $goal)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $goal->cycle->name ?? '—' }}</td>
                                        <td>{{ $goal->department->dept_name ?? '—' }}</td>
                                        <td><strong>{{ $goal->title }}</strong></td>
                                        <td><span class="badge bg-warning">{{ str_replace('_', ' ', $goal->status) }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('goal-setting.goals.edit', $goal->id) }}" class="btn btn-sm btn-outline-success">Edit</a>

                                            <form method="POST" action="{{ route('goal-setting.goals.approve-hec', $goal->id) }}" style="display:inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $goal->id }}">Reject</button>

                                            <div class="modal fade" id="rejectModal{{ $goal->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('goal-setting.goals.reject', $goal->id) }}">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Reject Goal</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <label class="form-label">Reason</label>
                                                                <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Reject</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No goals pending HEC approval.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
