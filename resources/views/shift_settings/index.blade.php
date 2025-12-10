@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Shift Settings</h3>
                        <div class="text-muted small">Define named shifts (e.g., Official Duty, Day/Night), times, and hours.
                        </div>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal"
                                data-bs-target="#createShiftModal">
                                <i class="fas fa-plus"></i> New Shift
                            </button>
                        @endif
                        {{-- <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Departments
                        </a> --}}
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            {{-- Top-level error summary (optional but helpful) --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">There were some problems with your submission:</div>
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle" id="shiftTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name / Code</th>
                                    <th>Official Duty</th>
                                    <th>Time</th>
                                    <th>Days / Day Hrs</th>
                                    <th>Weekly Hrs</th>
                                    <th>Status</th>
                                    <th class="text-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($shifts as $i => $s)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $s->name }}</div>
                                            @if ($s->code)
                                                <div class="text-muted small">{{ $s->code }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($s->is_official_duty)
                                                <span class="badge bg-primary">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($s->start_time && $s->end_time)
                                                {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}
                                                –
                                                {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $s->days_per_week ?? '—' }} days/wk</div>
                                            <div class="text-muted small">{{ $s->hours_per_day ?? '—' }} h/day</div>
                                        </td>
                                        <td>{{ $s->weekly_hours ?? ($s->computed_weekly_hours ?? '—') }}</td>
                                        <td>
                                            @if ($s->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-light text-dark">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('shift-settings.edit', $s->id) }}"
                                                        class="btn btn-sm btn-outline-success" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger delete-shift-btn"
                                                        data-id="{{ $s->id }}"
                                                        data-name="{{ $s->name }}"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($shifts->isEmpty())
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No shifts yet.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Create Modal --}}
    <div class="modal fade" id="createShiftModal" tabindex="-1" aria-labelledby="createShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('shift-settings.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createShiftModalLabel">New Shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required placeholder="e.g., Official Duty">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                            value="{{ old('code') }}" placeholder="e.g., OD">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input @error('is_official_duty') is-invalid @enderror" type="checkbox"
                            name="is_official_duty" value="1" id="is_official_duty"
                            {{ old('is_official_duty') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_official_duty">Official Duty</label>
                        @error('is_official_duty')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time"
                                class="form-control @error('start_time') is-invalid @enderror"
                                value="{{ old('start_time') }}">
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time"
                                class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}">
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label">Days / Week</label>
                            <input type="number" name="days_per_week"
                                class="form-control @error('days_per_week') is-invalid @enderror" min="1"
                                max="7" value="{{ old('days_per_week') }}" placeholder="5">
                            @error('days_per_week')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col">
                            <label class="form-label">Hours / Day</label>
                            <input type="number" step="0.25" name="hours_per_day"
                                class="form-control @error('hours_per_day') is-invalid @enderror"
                                value="{{ old('hours_per_day') }}" placeholder="8">
                            @error('hours_per_day')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Weekly Hours (optional)</label>
                        <input type="number" step="0.25" name="weekly_hours"
                            class="form-control @error('weekly_hours') is-invalid @enderror"
                            value="{{ old('weekly_hours') }}" placeholder="auto-calculated if empty">
                        @error('weekly_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Active?</label>
                        <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                            <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('is_active')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2"
                            placeholder="Notes...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTables if table exists
            if ($('#shiftTable').length) {
                $('#shiftTable').DataTable({
                    pageLength: 25,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                    language: {
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)"
                    },
                    columnDefs: [{
                        orderable: false,
                        targets: -1
                    }]
                });
            }

            // Handle delete button click
            $(document).on('click', '.delete-shift-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var button = $(this);
                var shiftId = button.data('id');
                var shiftName = button.data('name');

                if (!shiftId) {
                    console.error('Shift ID not found');
                    alert('Shift ID not found. Please refresh the page and try again.');
                    return;
                }

                // Show confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    html: `Do you want to delete <strong>${shiftName}</strong>?<br><br>This action will permanently delete the shift.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Make AJAX request to delete
                        $.ajax({
                            url: `/shift-settings/${shiftId}`,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || 'Shift deleted successfully!',
                                    confirmButtonColor: '#28a745',
                                    timer: 2000
                                }).then(() => {
                                    // Reload the page to refresh the table
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                var errorMessage = 'An error occurred while deleting the shift.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.status === 403) {
                                    errorMessage = 'You do not have permission to delete this shift.';
                                } else if (xhr.status === 404) {
                                    errorMessage = 'Shift not found.';
                                } else if (xhr.status === 500) {
                                    errorMessage = 'Server error. Please try again.';
                                } else if (xhr.responseText) {
                                    try {
                                        var response = JSON.parse(xhr.responseText);
                                        if (response.message) {
                                            errorMessage = response.message;
                                        }
                                    } catch (e) {
                                        errorMessage = xhr.responseText.substring(0, 200);
                                    }
                                }
                                console.error('Delete error:', xhr.status, xhr.responseText);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    html: `<strong>Status:</strong> ${xhr.status}<br><strong>Message:</strong> ${errorMessage}`,
                                    confirmButtonColor: '#dc3545'
                                });
                            }
                        });
                    }
                });
            });

            // If there are validation errors, re-open the modal so the user sees them
            @if ($errors->any())
                document.addEventListener('DOMContentLoaded', function() {
                    const modalEl = document.getElementById('createShiftModal');
                    if (modalEl) {
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                });
            @endif
        });
    </script>
@endpush
