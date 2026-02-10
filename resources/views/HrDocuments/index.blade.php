@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">HR Documents</h5>
                            @hasanyrole('hr|super-admin|it')
                                <a href="{{ route('HrDocuments.addview') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add New
                                </a>
                            @endhasanyrole

                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif

                            @if ($documents->isEmpty())
                                <p>No documents uploaded yet.</p>
                            @else
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th> <!-- Add number column -->
                                            <th>Document Name</th>
                                            <th>Description</th>
                                            <th>Upload Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($documents as $index => $document)
                                            <tr>
                                                <td>{{ $index + 1 }}</td> <!-- Add numbering -->
                                                <td>{{ $document->DocumentName }}</td>
                                                <td>{{ $document->Type }}</td>
                                                <td>{{ $document->created_at->format('d F Y') }}</td>
                                                <td class="d-flex justify-content-center">
                                                    <!-- Check if the current user has the right role to delete -->
                                                    @if (Auth::check() && Auth::user()->hasAnyRole('hr', 'super-admin', 'Admin', 'coo', 'cfo', 'cms', 'it'))
                                                        <!-- Delete Form with confirmation -->
                                                        <form
                                                            action="{{ route('HrDocuments.destroyhrdoc', $document->DocId) }}"
                                                            method="POST" style="display:inline;"
                                                            id="delete-form-{{ $document->DocId }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="btn btn-link text-danger"
                                                                title="Delete"
                                                                onclick="confirmDelete({{ $document->DocId }})">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <!-- Download Button -->
                                                    <a href="{{ route('documents.download', $document->DocId) }}"
                                                        class="btn btn-link text-primary ml-2" title="Download">
                                                        <i class="fas fa-download"></i>
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
        </div>
    </div>
    <!-- Simple JavaScript confirmation for delete -->
    <script>
        function confirmDelete(docId) {
            // Basic JavaScript confirmation
            if (window.confirm("Are you sure you want to delete this document? This action cannot be undone.")) {
                // Submit the delete form if confirmed
                document.getElementById('delete-form-' + docId).submit();
            }
        }
    </script>
@endsection
