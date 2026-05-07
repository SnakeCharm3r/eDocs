@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"
        integrity="sha512-AA1Bzp5Q0K1KanKKmvN/4d3IRKVlv9PYgwFPvm32nPO6QS8yH1HO7LbgB1pgiOxPtfeg5zEn2ba64MUcqJx6CA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Remarks</h3>
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
                                    <a href="{{ route('remark.create') }}"
                                        class="btn btn-primary position-absolute top-0 end-0 mt-2 me-2"
                                        style="background-color: #61ce70; border-color: #61ce70;">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </div>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rem as $remark)
                                        <tr>
                                            <td>{{ $remark->rmk_name }}</td>
                                            <td>{{ $remark->status }}</td>
                                            <td>
                                                <a href="{{ route('remark.edit', $remark->id) }}"
                                                    class="btn btn-sm edit-btn" data-id="{{ $remark->id }}"><i
                                                        class="fas fa-edit"></i></a>
                                                <button
                                                    onclick="deleteConfirmation('{{ route('remark.destroy', $remark->id) }}')"
                                                    class="btn btn-sm btn-delete"><i class="fas fa-trash-alt"></i></button>
                                                <form id="delete-form-{{ $remark->id }}"
                                                    action="{{ route('remark.destroy', $remark->id) }}" method="POST"
                                                    style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
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
        function deleteConfirmation(urlToRedirect) {
            swal({
                    title: "Are you sure to delete?",
                    text: "You will not be able to revert this!",
                    icon: "warning",
                    buttons: ["Cancel", "Delete"],
                    dangerMode: true,
                })
                .then((willDelete) => {
                    if (willDelete) {
                        document.getElementById('delete-form-' + urlToRedirect.split('/').pop()).submit();
                    } else {
                        swal("Your remark is safe!");
                    }
                });
        }
    </script>
@endsection
