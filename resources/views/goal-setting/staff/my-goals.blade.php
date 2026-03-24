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
                        <h3 class="page-title mb-0">My Goals</h3>
                    </div>
                    <div class="col-auto">
                        @can('create staff goals')
                            <a href="{{ route('goal-setting.staff.goals.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Add Goal
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('goal-setting.staff.my-goals') }}" class="row g-2 align-items-end">
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

                                            @if (in_array($goal->status, ['draft', 'rejected']))
                                                <form method="POST" action="{{ route('goal-setting.goals.submit-line-manager', $goal->id) }}" style="display:inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Submit to Line Manager</button>
                                                </form>
                                            @endif

                                            @hasanyrole('line-manager|coo|cfo|cms|ccdro|hec-cfo|hec-coo|hec-cms|hec-ccdro|hec-ccd|Super-Admin')
                                                <form method="POST" action="{{ route('goal-setting.goals.destroy', $goal->id) }}" style="display:inline-block;" onsubmit="return confirm('Delete this goal?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endhasanyrole

                                            @if ($goal->status === 'rejected')
                                                <div class="small text-danger mt-1">{{ $goal->rejection_reason }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No goals yet.</td>
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
