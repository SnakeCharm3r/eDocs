@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.0/css/buttons.dataTables.css" />
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Signatures</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container mt-5">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="card card-body p-3">
                    <div class="table-responsive">
                        <table id="example" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Signature</th>
                                    {{-- <th>Actions</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>{{ $user->fname }}</td>
                                        <td>{{ $user->lname }}</td>
                                        <td>
                                            @if ($user->signature)
                                                <img src="data:image/png;base64,{{ $user->signature }}" alt="User Signature"
                                                    style="max-width: 100px; height: auto;">
                                            @else
                                                No Signature
                                            @endif
                                        </td>
                                        {{-- <td>
                                    <a href="{{ route('signature.edit', $user->id) }}" class="btn btn-warning">Edit</a>
                                    <form action="{{ route('signature.destroy', $user->id) }}" method="POST"
                                        style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>

                                </td> --}}
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
                <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.js"></script>
                <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.dataTables.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>
                <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

                <script>
                    new DataTable('#example', {
                        layout: {
                            topStart: {
                                buttons: ['pdf', 'print', 'excel']
                            }
                        }
                    });
                </script>
            </div>
        </div>
    </div>
@endsection
