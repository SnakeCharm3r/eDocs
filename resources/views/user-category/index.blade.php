@extends('layouts.template')
@section('breadcrumb')
@include('sweetalert::alert')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Departments</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row position-relative">
                            <div class="col-md-12">
                                <a href="{{ route('department.create') }}" class="btn btn-primary position-absolute top-0 end-0 mt-2 me-2" style="background-color: #61ce70; border-color: #61ce70;">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                        </div>
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Head Of Department</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($departments as $department)
                                <tr>
                                    <td>{{ $department->dept_name }}</td>
                                    <td>{{ $department->headOfDepartment ? $department->headOfDepartment->fname . ' ' . $department->headOfDepartment->lname : 'N/A' }}</td>
                                    <td>{{ $department->description }}</td>
                                    <td>
                                        <a href="{{ route('department.edit', $department->id) }}" class="btn btn-sm edit-btn" data-id="{{ $department->id }}"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('department.destroy', $department->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm"><i class="fas fa-trash-alt"></i></button>
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
<script>
    $(document).ready(function () {
        $('.table').DataTable();

        // Edit button click event
        $('.edit-btn').on('click', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            $.get('/department/' + id + '/edit', function(data) {
                // Show edit modal with the fetched data
                $('#editModal .modal-content').html(data);
                $('#editModal').modal('show');
            });
        });

        // Submit edit form via AJAX
        $(document).on('submit', '#editDepartmentForm', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            $.ajax({
                type: 'PUT',
                url: url,
                data: form.serialize(),
                success: function(response) {
                    if (response.status == 200) {
                        $('#editModal').modal('hide');
                        location.reload();
                    } else {
                        // Handle validation errors
                        var errors = response.errors;
                        for (var error in errors) {
                            form.find('.' + error + '-error').text(errors[error][0]);
                        }
                    }
                }
            });
        });
    });
</script>



<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Dynamic content will be loaded here -->
        </div>
    </div>
</div>
@endsection

