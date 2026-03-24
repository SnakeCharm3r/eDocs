@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header d-flex justify-content-between align-items-center">
                        <h3 class="page-title mb-0"><i class="fas fa-link me-2"></i>External System Links</h3>
                        <a href="{{ route('external-system-links.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Add New Link
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead style="background-color: #6c757d; color: white;">
                            <tr>
                                <th>Order</th>
                                <th>Name</th>
                                <th>Logo</th>
                                <th>URL</th>
                                <th>Open in New Tab</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($links as $link)
                                <tr>
                                    <td>{{ $link->display_order }}</td>
                                    <td>
                                        <strong>{{ $link->name }}</strong>
                                        @if($link->description)
                                            <br><small class="text-muted">{{ $link->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($link->logo)
                                            <img src="{{ asset('storage/' . $link->logo) }}" alt="{{ $link->name }}" 
                                                 style="max-width: 60px; max-height: 60px; object-fit: contain;">
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="text-primary">
                                            {{ Str::limit($link->url, 50) }}
                                            <i class="fas fa-external-link-alt ms-1"></i>
                                        </a>
                                    </td>
                                    <td>
                                        @if($link->open_in_new_tab)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($link->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('external-system-links.edit', $link->id) }}" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('external-system-links.destroy', $link->id) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this link?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-link fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No external system links found.</p>
                                        <a href="{{ route('external-system-links.create') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Add First Link
                                        </a>
                                    </td>
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

