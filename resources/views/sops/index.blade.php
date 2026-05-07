@extends('layouts.template')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-normal">Standard Operating Procedure (SOP)</h6>
                            @role('hr|line-manager|admin|super-admin')
                                <a href="{{ route('sops.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i> Add SOP
                                </a>
                            @endrole
                        </div>

                        <div class="card-body">

                            <!-- Auto-submit Filter Form -->
                            <form method="GET" action="{{ route('sops.index') }}" id="filterForm" class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <input type="text" name="title" id="title" class="form-control"
                                        value="{{ request('title') }}" placeholder="Search SOP title...">
                                </div>

                                <div class="col-md-4">
                                    <select class="form-select" name="department_id" id="department_id">
                                        <option value="">-- All Departments --</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->dept_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>

                            <!-- SOP Table -->
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 50px;">No.</th>
                                        <th>Title</th>
                                        <th>Department</th>
                                        <th>Updated At</th>
                                        <th>Updated By</th>
                                        <th class="text-center" style="width: 160px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sops as $sop)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $sop->title }}</td>
                                            <td>
                                                @if ($sop->global)
                                                    <span class="badge bg-secondary">All Departments</span>
                                                @else
                                                    @if ($sop->departments && $sop->departments->isNotEmpty())
                                                        @foreach ($sop->departments as $department)
                                                            <span
                                                                class="badge bg-success">{{ $department->dept_name }}</span>
                                                        @endforeach
                                                    @else
                                                        <span class="badge bg-warning">No Departments Assigned</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($sop->updated_at)->format('d M Y') }}</td>
                                            <td>{{ $sop->updatedBy ? $sop->updatedBy->username : '—' }}</td>
                                            <td class="text-center">
                                                @if ($sop->pdf_path)
                                                    <a href="{{ asset('storage/' . $sop->pdf_path) }}" target="_blank"
                                                        class="btn btn-warning btn-sm mx-1" title="View PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                @endif

                                                @hasanyrole('line-manager|admin|super-admin|coo|cfo|cms|it|hr')
                                                    <a href="{{ route('sops.edit', $sop->id) }}"
                                                        class="btn btn-success btn-sm mx-1" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <form action="{{ route('sops.destroy', $sop->id) }}" method="POST"
                                                        style="display:inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this SOP?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm mx-1"
                                                            title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                @endhasanyrole
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No SOPs found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Auto Submit Script -->
    <script>
        document.getElementById('department_id').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        document.getElementById('title').addEventListener('input', function() {
            clearTimeout(this.delay);
            this.delay = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 500);
        });
    </script>
@endsection
