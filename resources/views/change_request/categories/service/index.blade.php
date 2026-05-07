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
                        <h3 class="page-title">Service Categories</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('service-categories.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Service Category
                        </a>
                        <a href="{{ route('tariff-categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Tariff Categories
                        </a>
                        <a href="{{ route('payment-types.index') }}" class="btn btn-info">
                            <i class="fas fa-credit-card"></i> Payment Types
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="serviceCategoriesTable" class="display nowrap table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Payment Types</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($categories as $index => $category)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $category->name }}</td>
                                                <td>{{ $category->description ?? 'N/A' }}</td>
                                                <td>
                                                    @if($category->payment_types)
                                                        @foreach($category->payment_types as $type)
                                                            <span class="badge bg-info me-1">{{ $type }}</span>
                                                        @endforeach
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($category->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('service-categories.edit', $category->id) }}" class="btn btn-sm btn-outline-success" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('service-categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
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
            $('#serviceCategoriesTable').DataTable({
                responsive: true,
                pageLength: 25,
            });
        });
    </script>
@endpush















