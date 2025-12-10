@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .table-responsive {
            overflow-x: auto;
        }

        .action-btns .btn {
            padding: 0.375rem 0.75rem;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h3 class="page-title">Contract Templates</h3>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="{{ route('contract-templates.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add New Template
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            @if ($templates->isEmpty())
                                <div class="alert alert-info text-center">
                                    No contract templates found.
                                    <a href="{{ route('contract-templates.create') }}" class="alert-link">
                                        Create your first template
                                    </a>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Template Name</th>
                                                <th>Type</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($templates as $template)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $template->name }}</td>
                                                    <td>{{ $template->type }}</td>
                                                    <td>{{ $template->created_at->format('d M Y') }}</td>
                                                    <td class="action-btns">
                                                        <a href="{{ route('contract-templates.edit', $template->id) }}"
                                                            class="btn btn-sm btn-warning" title="Edit">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </a>
                                                        <form
                                                            action="{{ route('contract-templates.destroy', $template->id) }}"
                                                            method="POST" style="display: inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                title="Delete" onclick="return confirm('Are you sure?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                        <a href="{{ route('contract-templates.show', $template->id) }}"
                                                            class="btn btn-sm btn-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
