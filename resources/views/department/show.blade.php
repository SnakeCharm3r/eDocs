@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        {{-- Page Header --}}
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-building me-2 text-success"></i>Department Details
                    </h3>
                    <p class="text-muted mb-0 mt-1">{{ $department->dept_name }}</p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('department.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Departments
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Main Information Card --}}
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2 text-success"></i>Basic Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong><i class="fas fa-building me-2 text-success"></i>Department Name:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $department->dept_name }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong><i class="fas fa-info-circle me-2 text-success"></i>Description:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $department->description ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong><i class="fas fa-level-up-alt me-2 text-success"></i>HEC Level:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $department->hec->hec_level_name ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Entities Card --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-sitemap me-2 text-success"></i>Assigned Entities
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($department->divisions && $department->divisions->count() > 0)
                            <div class="row">
                                @foreach($department->divisions as $division)
                                    <div class="col-md-6 mb-3">
                                        <div class="card border">
                                            <div class="card-body p-3">
                                                <h6 class="mb-2">
                                                    <i class="fas fa-building me-2 text-primary"></i>{{ $division->name }}
                                                </h6>
                                                <div class="small text-muted">
                                                    <div><strong>Code:</strong> {{ $division->code }}</div>
                                                    <div><strong>Status:</strong> 
                                                        <span class="badge {{ $division->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                            {{ ucfirst($division->status ?? 'active') }}
                                                        </span>
                                                    </div>
                                                    @if($division->description)
                                                        <div class="mt-2"><strong>Description:</strong> {{ $division->description }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No entities assigned to this department</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="col-md-4">
                {{-- Statistics Card --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2 text-success"></i>Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><i class="fas fa-users me-2 text-success"></i>Total Users:</span>
                                <strong>{{ $department->user->count() ?? 0 }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><i class="fas fa-sitemap me-2 text-success"></i>Entities:</span>
                                <strong>{{ $department->divisions->count() ?? 0 }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions Card --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2 text-success"></i>Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('department.edit', $department->id) }}" class="btn btn-success">
                                <i class="fas fa-edit me-2"></i>Edit Department
                            </a>
                            <a href="{{ route('department.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

