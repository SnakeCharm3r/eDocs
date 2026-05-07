@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <!-- Page Breadcrumb Section -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Employee Forms</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee Forms Card -->
            <div class="card card-body p-3">
                <div class="container">
                    {{-- Check if user forms are available --}}
                    @if ($userForms->isEmpty())
                        <p>No forms available for this user.</p>
                    @else
                        {{-- Display Forms in a Table --}}
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Form Name</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Loop through each form and display in rows --}}
                                @foreach ($userForms as $index => $form)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $form->name }}</td>
                                        {{-- Format the creation date --}}
                                        <td>{{ \Carbon\Carbon::parse($form->created_at)->format('d F Y') }}</td>
                                        <td>
                                            {{-- View Form Button --}}
                                            <a href="{{ $form->file_url }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
