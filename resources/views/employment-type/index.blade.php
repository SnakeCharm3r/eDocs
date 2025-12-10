@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <!-- Page Title on the Left -->
                    <div class="col-sm-6">
                        <div class="page-sub-header">
                            <h3 class="page-title">Employment Type</h3>
                        </div>
                    </div>

                    <!-- Action Buttons on the Right -->
                    @if (auth()->user()->hasAnyRole('hr', 'super-admin'))
                        <div class="col-sm-6 d-flex justify-content-end gap-2">
                            <!-- Back Button -->
                            <a href="/departments" class="btn btn-secondary mt-2">
                                <i class="fas fa-arrow-left"></i> Back to Departments
                            </a>


                            <!-- Add Employment Button -->
                            <a href="{{ route('employment.create') }}" class="btn btn-primary mt-2"
                                style="background-color: #61ce70; border-color: #61ce70;">
                                <i class="fas fa-plus"></i> Add Employment
                            </a>
                        </div>
                    @endif
                </div>
            </div>


            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">


                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($emp as $employments)
                                        <tr>
                                            <td>{{ $employments->employment_type }}</td>
                                            <td>{{ $employments->description }}</td>
                                            <td>
                                                <a href="{{ route('employment.edit', $employments->id) }}"
                                                    class="btn btn-sm edit-btn" data-id="{{ $employments->id }}"><i
                                                        class="fas fa-edit"></i></a>
                                                <form action="{{ route('employment.destroy', $employments->id) }}"
                                                    method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm"><i
                                                            class="fas fa-trash-alt"></i></button>
                                                </form>
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

@section('content')
@endsection
