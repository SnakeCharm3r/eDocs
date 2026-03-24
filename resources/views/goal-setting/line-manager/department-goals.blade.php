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
                        <h3 class="page-title mb-0">Department Goals</h3>
                        <small class="text-muted">Edit and agree goals before sending to HEC</small>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('goal-setting.line-manager.inbox') }}" class="btn btn-secondary btn-sm">Inbox</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('goal-setting.line-manager.department-goals') }}" class="row g-2 align-items-end">
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
                    <h5 class="mb-0">Goals</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Cycle</th>
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
                                        <td><strong>{{ $goal->title }}</strong></td>
                                        <td>
                                            @php
                                                $status = $goal->status ?? 'draft';
                                                $badge = 'bg-secondary';
                                                if (str_contains($status, 'submitted')) $badge = 'bg-warning';
                                                if (str_contains($status, 'approved')) $badge = 'bg-success';
                                                if ($status === 'rejected') $badge = 'bg-danger';
                                            @endphp
                                            <span class="badge {{ $badge }}">{{ str_replace('_', ' ', $status) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('goal-setting.goals.edit', $goal->id) }}" class="btn btn-sm btn-outline-success">Edit</a>

                                            <form method="POST" action="{{ route('goal-setting.goals.approve-line-manager', $goal->id) }}" style="display:inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>

                                            <form method="POST" action="{{ route('goal-setting.goals.submit-hec', $goal->id) }}" style="display:inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Send to HEC</button>
                                            </form>

                                            @role('line-manager')
                                                <form method="POST" action="{{ route('goal-setting.goals.destroy', $goal->id) }}" style="display:inline-block;" onsubmit="return confirm('Delete this goal?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endrole
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No department goals found.</td>
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
