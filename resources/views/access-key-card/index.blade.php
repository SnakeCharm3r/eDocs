@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Title -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-key me-2 text-success"></i>Access Key Cards
                        </h3>
                    </div>
                    <div class="col-auto">
                        @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                            <a href="{{ route('access-key-card.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Add New Access Key Card
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <!-- Card Header -->
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2 text-success"></i>Access Key Cards List
                            </h5>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="keyCardTable" class="table table-hover table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th style="width: 30%;">
                                                <i class="fas fa-key me-1 text-success"></i>Card Number
                                            </th>
                                            <th style="width: 20%;">
                                                <i class="fas fa-info-circle me-1 text-success"></i>Status
                                            </th>
                                            <th style="width: 25%;">
                                                <i class="fas fa-link me-1 text-success"></i>Usage
                                            </th>
                                            <th style="width: 20%;">
                                                <i class="fas fa-sticky-note me-1 text-success"></i>Notes
                                            </th>
                                            <th style="width: 10%;" class="text-center">
                                                <i class="fas fa-cog me-1 text-success"></i>Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($keyCards as $index => $card)
                                            @php
                                                // Check usage in IctAccessResource
                                                $usageCount = \App\Models\IctAccessResource::where('access_key_card_id', $card->id)
                                                    ->where('delete_status', '!=', '1')
                                                    ->count();
                                                $isInUse = $usageCount > 0;
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $card->card_number }}</strong></td>
                                                <td>
                                                    <span class="badge {{ $card->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $card->status ?? 'active')) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($isInUse)
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            Used by {{ $usageCount }} resource(s)
                                                        </span>
                                                    @else
                                                        <span class="text-muted">Not in use</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $card->notes ?? '—' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('access-key-card.edit', $card->id) }}"
                                                                class="btn btn-sm btn-outline-success" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            @if ($isInUse)
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    disabled
                                                                    title="Cannot delete: Used by {{ $usageCount }} resource(s)">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @else
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger delete-card-btn"
                                                                    data-id="{{ $card->id }}"
                                                                    data-number="{{ $card->card_number }}"
                                                                    data-usage-count="{{ $usageCount }}"
                                                                    title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            var table = $('#keyCardTable').DataTable({
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                columnDefs: [{
                    orderable: false,
                    targets: -1
                }]
            });

            // Handle delete button click
            $(document).on('click', '.delete-card-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var button = $(this);
                var cardId = button.data('id');
                var cardNumber = button.data('number');
                var usageCount = parseInt(button.data('usage-count')) || 0;

                if (usageCount > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        html: `Cannot delete <strong>${cardNumber}</strong>.<br><br>This Access Key Card is currently being used by <strong>${usageCount}</strong> ICT access resource(s). Please remove all references first before deleting.`,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    html: `Do you want to delete <strong>${cardNumber}</strong>?<br><br>This action will soft delete the Access Key Card.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        $.ajax({
                            url: `/access-key-card/${cardId}`,
                            type: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || 'Access Key Card deleted successfully!',
                                    confirmButtonColor: '#28a745',
                                    timer: 2000
                                }).then(() => { location.reload(); });
                            },
                            error: function(xhr) {
                                var errorMessage = 'An error occurred while deleting the Access Key Card.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
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
        });
    </script>
@endpush

