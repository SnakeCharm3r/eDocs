@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">User Profile</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Include required CSS -->
            <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

            <div class="container">
                <div class="row flex-lg-nowrap">

                    <div class="col">
                        <div class="row">
                            <div class="col mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <ul class="nav nav-tabs">
                                            <li class="nav-item"><a href="{{ route('profile.index') }}"
                                                    class="nav-link">User Info</a></li>
                                            <li class="nav-item"><a href="{{ route('policies.user') }}"
                                                    class="nav-link">Policies</a></li>
                                            <li class="nav-item"><a href="#security" class="nav-link"
                                                    data-bs-toggle="tab">Password</a></li>
                                            {{-- <li class="nav-item"><a href="{{ route('family-details.index') }}"
                                                    class="nav-link">Family Details</a></li>
                                            <li class="nav-item"><a href="{{ route('health-details.index') }}"
                                                    class="nav-link">Health Details</a></li>
                                            <li class="nav-item"><a href="{{ route('ccbrt_relation.index') }}"
                                                    class="nav-link">CCBRT Relation</a></li>
                                            <li class="nav-item"><a href="{{ route('language_knowledge.index') }}"
                                                    class="active nav-link">Language</a></li> --}}
                                        </ul>
                                        <div class="tab-content pt-3">

                                            <!-- Button to trigger modal -->
                                            <div class="row">
                                                <div class="col d-flex justify-content-end">
                                                    <button type="button" id="addLanguageBtn" class="btn btn-primary mb-3">
                                                        Add Language
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Modal for creating/editing language knowledge -->
                                            <div class="modal fade" id="languageModal" tabindex="-1" role="dialog"
                                                aria-labelledby="languageModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <form id="languageForm" method="POST">
                                                            @csrf
                                                            @method('POST')
                                                            <input type="hidden" id="languageId" name="id">
                                                            <input type="hidden" id="formMode" name="formMode"
                                                                value="create">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="languageModalLabel">Add Language
                                                                </h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row language-item">
                                                                    <div class="col-12 col-md-3">
                                                                        <div class="form-group">
                                                                            <label for="languageInput">Language</label>
                                                                            <input type="text" class="form-control"
                                                                                id="languageInput" name="language"
                                                                                placeholder="e.g., English">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-md-3">
                                                                        <div class="form-group">
                                                                            <label>Speaking</label>
                                                                            <select class="form-control" id="speakingInput"
                                                                                name="speaking">
                                                                                <option value="" disabled selected>
                                                                                    Select proficiency</option>
                                                                                <option value="Very Good">Very Good</option>
                                                                                <option value="Native">Native</option>
                                                                                <option value="Good">Good</option>
                                                                                <option value="Fair">Fair</option>
                                                                                

                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-md-3">
                                                                        <div class="form-group">
                                                                            <label>Reading</label>
                                                                            <select class="form-control" id="readingInput"
                                                                                name="reading">
                                                                                <option value="" disabled selected>
                                                                                    Select proficiency</option>
                                                                                    <option value="Very Good">Very Good</option>
                                                                                    <option value="Native">Native</option>
                                                                                    <option value="Good">Good</option>
                                                                                    <option value="Fair">Fair</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-md-3">
                                                                        <div class="form-group">
                                                                            <label>Writing</label>
                                                                            <select class="form-control" id="writingInput"
                                                                                name="writing">
                                                                                <option value="" disabled selected>
                                                                                    Select proficiency</option>
                                                                                    <option value="Very Good">Very Good</option>
                                                                                    <option value="Native">Native</option>
                                                                                    <option value="Good">Good</option>
                                                                                    <option value="Fair">Fair</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary"
                                                                    id="saveButton">Add Language</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif


                                            <!-- Table for displaying language knowledge -->
                                            <h3>Language Knowledge</h3>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Language</th>
                                                            <th>Speaking</th>
                                                            <th>Reading</th>
                                                            <th>Writing</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($languageKnowledge as $knowledge)
                                                            <tr>
                                                                <td>{{ $knowledge->language }}</td>
                                                                <td>{{ $knowledge->speaking }}</td>
                                                                <td>{{ $knowledge->reading }}</td>
                                                                <td>{{ $knowledge->writing }}</td>
                                                                <td>
                                                                    <!-- Edit and Delete Icons -->
                                                                    <a href="#" class="text-primary edit-btn"
                                                                        data-id="{{ $knowledge->id }}"
                                                                        data-language="{{ $knowledge->language }}"
                                                                        data-speaking="{{ $knowledge->speaking }}"
                                                                        data-reading="{{ $knowledge->reading }}"
                                                                        data-writing="{{ $knowledge->writing }}"
                                                                        title="Edit">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>
                                                                    <form
                                                                        action="{{ route('language_knowledge.destroy', $knowledge->id) }}"
                                                                        method="POST" style="display: inline;">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                            class="btn text-danger delete-btn"
                                                                            title="Delete"
                                                                            style="background: none; border: none; cursor: pointer;">
                                                                            <i class="fa fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center">No language
                                                                    knowledge found.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Add other content for profile page here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include required JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const languageModal = new bootstrap.Modal(document.getElementById('languageModal'));

            // Handle delete button click
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault(); // Prevent default link behavior

                    const form = this.closest('form');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'No, cancel!',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Submit the form
                            form.submit();
                        }
                    });
                });
            });

            // Handle edit button click
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const language = this.getAttribute('data-language');
                    const speaking = this.getAttribute('data-speaking');
                    const reading = this.getAttribute('data-reading');
                    const writing = this.getAttribute('data-writing');

                    // Set form to edit mode
                    document.getElementById('languageId').value = id;
                    document.getElementById('formMode').value = 'edit';
                    document.getElementById('languageInput').value = language;
                    document.getElementById('speakingInput').value = speaking;
                    document.getElementById('readingInput').value = reading;
                    document.getElementById('writingInput').value = writing;

                    // Update button text
                    document.getElementById('saveButton').textContent = 'Update Language';
                    document.getElementById('languageForm').action = `/language-knowledge/${id}`;

                    // Show the modal
                    languageModal.show();
                });
            });

            // Handle add button click
            document.getElementById('addLanguageBtn').addEventListener('click', function() {
                // Reset form to create mode
                document.getElementById('languageId').value = '';
                document.getElementById('formMode').value = 'create';
                document.getElementById('languageInput').value = '';
                document.getElementById('speakingInput').value = '';
                document.getElementById('readingInput').value = '';
                document.getElementById('writingInput').value = '';

                // Update button text
                document.getElementById('saveButton').textContent = 'Add Language';
                document.getElementById('languageForm').action = '{{ route('language_knowledge.add') }}';

                // Show the modal
                languageModal.show();
            });
        });
    </script>
@endsection
