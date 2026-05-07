@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <!-- Page Title -->
                        <div class="page-sub-header">
                            <h3 class="page-title">HEC Levels</h3>
                        </div>
                    </div>

                    @php
                        $user = auth()->user();
                        $roles = ['hr', 'Admin', 'super-admin'];
                    @endphp

                    @if ($user && $user->hasAnyRole($roles))
                        <!-- Action Buttons (Back & Add) -->
                        <div class="col-sm-6 d-flex justify-content-end gap-2">
                            <!-- Back Button -->
                            <a href="/departments" class="btn btn-secondary mt-2">
                                <i class="fas fa-arrow-left"></i> Back to Departments
                            </a>

                            <!-- Add Button -->
                            <a href="{{ route('hec.create') }}" class="btn btn-primary mt-2"
                                style="background-color: #61ce70; border-color: #61ce70;">
                                <i class="fas fa-plus"></i> Add HEC Level
                            </a>
                        </div>
                    @endif
                </div>
            </div>


            <!-- Table for HEC Levels -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- HEC Levels Table -->
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Created By</th>
                                        <th>Updated By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($hec as $level)
                                        <tr>
                                            <td>{{ $level->hec_level_name }}</td>
                                            <td>{{ $level->descriptions }}</td>
                                            <td>{{ $level->creator->username ?? 'N/A' }}</td>
                                            <td>{{ $level->updater->username ?? 'N/A' }}</td>

                                            <td>
                                                <!-- Edit Button -->
                                                <a href="{{ route('hec.edit', $level->id) }}"
                                                    class="btn btn-sm btn-outline-success" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <!-- Uncomment to enable delete -->
                                                {{-- <form action="{{ route('hec.destroy', $level->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this level?');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form> --}}
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
@endsection
