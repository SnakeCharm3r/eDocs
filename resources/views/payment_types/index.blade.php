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
                        <h3 class="page-title">Payment Types</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('payment-types.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Payment Type
                        </a>
                        <a href="{{ route('service-categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Service Categories
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="paymentTypesTable" class="display nowrap table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($paymentTypes as $index => $paymentType)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $paymentType->name }}</td>
                                                <td>{{ $paymentType->description ?? 'N/A' }}</td>
                                                <td>
                                                    @if($paymentType->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('payment-types.edit', $paymentType->id) }}" class="btn btn-sm btn-outline-success" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('payment-types.destroy', $paymentType->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payment type?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
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
            $('#paymentTypesTable').DataTable({
                responsive: true,
                pageLength: 25,
            });
        });
    </script>
@endpush

