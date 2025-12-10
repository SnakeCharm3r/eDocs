@extends('layouts.template')
@include('sweetalert::alert')

<style>
    .select2-container {
        width: 100% !important;
        min-width: 300px;
    }

    .select2-selection--multiple {
        min-height: 70px !important;
        overflow-y: auto;
        border: 1px solid #ced4da !important;
        padding: 5px;
    }

    .select2-selection__choice {
        background-color: #e9ecef !important;
        border: 1px solid #ced4da !important;
        margin: 2px;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
    }

    .select2-selection__choice__remove {
        display: none !important;
    }

    select[multiple] {
        height: 120px;
        width: 100%;
        min-width: 300px;
    }
</style>

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <!-- Admin Roles Section: Visible to hr, super-admin, Admin -->
                            @if (Auth::check() && Auth::user()->hasAnyRole('hr', 'super-admin', 'Admin', 'coo', 'cfo', 'cms'))
                                <div class="d-flex align-items-center mb-3">
                                    <!-- Add Policy to All Users Button -->
                                    <a href="{{ route('policies.create') }}" class="btn btn-primary me-2"
                                        style="background-color: #61ce70; border-color: #70c97c; padding: 0.5rem 1rem; font-size: 0.95rem; font-weight: 500; transition: all 0.3s ease;"
                                        onmouseover="this.style.backgroundColor='#0c6b32'; this.style.borderColor='#0c6b32'; this.style.boxShadow='0 2px 8px rgba(15, 129, 60, 0.3)'; this.style.transform='scale(1.02)';"
                                        onmouseout="this.style.backgroundColor='#61ce70'; this.style.borderColor='#70c97c'; this.style.boxShadow='none'; this.style.transform='scale(1)';">
                                        <i class="fas fa-plus me-2"></i> Add Policy to All Departments
                                    </a>
                                    <!-- Add Policy to Department Button -->
                                    <a href="{{ route('policies.create-department') }}" class="btn btn-secondary"
                                        style="background-color: #6c757d; border-color: #6c757d; padding: 0.5rem 1rem; font-size: 0.95rem; font-weight: 500; transition: all 0.3s ease;"
                                        onmouseover="this.style.backgroundColor='#5a6268'; this.style.borderColor='#5a6268'; this.style.boxShadow='0 2px 8px rgba(108, 117, 125, 0.3)'; this.style.transform='scale(1.02)';"
                                        onmouseout="this.style.backgroundColor='#6c757d'; this.style.borderColor='#6c757d'; this.style.boxShadow='none'; this.style.transform='scale(1)';">
                                        <i class="fas fa-plus me-2"></i> Add to Department
                                    </a>
                                </div>
                                <style>
                                    @media (max-width: 576px) {
                                        .d-flex {
                                            flex-direction: column;
                                            align-items: flex-start;
                                        }

                                        .btn-primary,
                                        .btn-secondary {
                                            width: 100%;
                                            margin-bottom: 0.5rem;
                                            margin-right: 0;
                                        }
                                    }
                                </style>
                            @endif

                            <!-- Department Filter Dropdown -->
                            <div class="mb-3">
                                <form action="{{ route('policies.index') }}" method="GET">
                                    <label for="department_id" class="form-label">Filter by Department:</label>
                                    <select name="department_id" id="department_id" class="form-select"
                                        onchange="this.form.submit()">
                                        <option value="">All Departments</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->dept_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                            <!-- Department Filter Buttons -->
                            {{-- <div class="mb-3">
                                <label class="form-label">Filter by Department:</label>
                                <div class="d-flex flex-wrap gap-1">
                                    <!-- All Departments Button -->
                                    <a href="{{ route('policies.index') }}"
                                        class="btn btn-outline-primary btn-xs {{ !request('department_id') ? 'active' : '' }}"
                                        style="font-size: 0.75rem; padding: 0.2rem 0.5rem; line-height: 1.2;">
                                        All Departments
                                    </a>
                                    <!-- Department Buttons -->
                                    @foreach ($departments as $department)
                                        <a href="{{ route('policies.index') }}?department_id={{ $department->id }}"
                                            class="btn btn-outline-secondary btn-xs {{ request('department_id') == $department->id ? 'active' : '' }}"
                                            style="font-size: 0.75rem; padding: 0.2rem 0.5rem; line-height: 1.2;">
                                            {{ $department->dept_name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div> --}}

                            <style>
                                .btn-xs {
                                    border-radius: 3px;
                                    transition: all 0.2s ease;
                                }

                                .btn-outline-primary,
                                .btn-outline-secondary {
                                    border-width: 1px;
                                }

                                .btn-outline-primary:hover,
                                .btn-outline-secondary:hover {
                                    transform: scale(1.05);
                                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                                }

                                .btn-outline-primary.active {
                                    background-color: #007bff;
                                    color: #fff;
                                    border-color: #007bff;
                                }

                                .btn-outline-secondary.active {
                                    background-color: #6c757d;
                                    color: #fff;
                                    border-color: #6c757d;
                                }

                                @media (max-width: 576px) {
                                    .d-flex.flex-wrap {
                                        flex-direction: column;
                                        align-items: flex-start;
                                    }

                                    .btn-xs {
                                        width: 100%;
                                        margin-bottom: 0.3rem;
                                    }
                                }
                            </style>
                        </div>

                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Title</th>
                                        <th>Department</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Organization-Wide Policies -->
                                    @foreach ($policies as $policy)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $policy->title }}</td>
                                            <td><span class="badge bg-secondary">All Departments</span>
                                            </td>
                                            <td class="text-center" style="width: 140px;">
                                                <!-- View Icon -->
                                                <a href="javascript:void(0);"
                                                    onclick="viewDescription('{{ $policy->title }}', '{{ urlencode($policy->content) }}')"
                                                    class="btn btn-sm p-0" title="View Description">
                                                    <i class="fas fa-eye text-info"></i>
                                                </a>

                                                @if (Auth::check() && Auth::user()->hasAnyRole('hr', 'super-admin', 'Admin', 'coo'))
                                                    <!-- Edit Icon -->
                                                    <a href="{{ route('policies.edit', $policy->id) }}"
                                                        class="btn btn-sm p-0 mx-1" title="Edit">
                                                        <i class="fas fa-edit text-success"></i>
                                                    </a>

                                                    <!-- Delete Icon -->
                                                    <form action="{{ route('policies.destroy', $policy->id) }}"
                                                        method="POST" style="display:inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this policy?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm p-0"
                                                            style="border: none; background: none;" title="Delete">
                                                            <i class="fas fa-trash-alt text-danger"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                    <!-- Department-Specific Policies -->
                                    @foreach ($groupedPolicies as $index => $policy)
                                        <tr>
                                            <td>{{ $loop->iteration + $policies->count() }}</td>
                                            <!-- Continue numbering after org-wide policies -->
                                            <td>{{ $policy->title }}</td>
                                            <td>
                                                @if ($policy->departments->isNotEmpty())
                                                    @foreach ($policy->departments as $dept)
                                                        <span class="badge bg-success">{{ $dept }}</span>
                                                    @endforeach
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="text-center" style="width: 140px;">
                                                <!-- View Icon -->
                                                <a href="javascript:void(0);"
                                                    onclick="viewDescription('{{ $policy->title }}', '{{ urlencode($policy->content) }}')"
                                                    class="btn btn-sm p-0" title="View Description">
                                                    <i class="fas fa-eye text-info"></i>
                                                </a>

                                                @if (Auth::check() && Auth::user()->hasAnyRole('hr', 'super-admin', 'admin', 'coo'))
                                                    <!-- Edit Icon -->
                                                    <a href="{{ route('policies.edit-department', $policy->id) }}"
                                                        class="btn btn-sm p-0 mx-1" title="Edit">
                                                        <i class="fas fa-edit text-success"></i>
                                                    </a>

                                                    <!-- Delete Icon -->
                                                    <form
                                                        action="{{ route('department-policies.destroy-department', $policy->id) }}"
                                                        method="POST" style="display:inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this department policy?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm p-0"
                                                            style="border: none; background: none;" title="Delete">
                                                            <i class="fas fa-trash-alt text-danger"></i>
                                                        </button>
                                                    </form>
                                                @endif
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

    <!-- Modal for Viewing Policy Description -->
    <style>
        .modal-content {
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .policy-details {
            margin-bottom: 1.5rem;
        }

        .policy-details p {
            margin: 0.5rem 0;
            font-size: 1rem;
            color: #4a4a4a;
        }

        .policy-content {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            font-size: 1rem;
            line-height: 1.6;
        }

        .modal-footer {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .btn-copy,
        .btn-print {
            margin-right: 0.5rem;
        }
    </style>

    <div class="modal fade" id="descriptionModal" tabindex="-1" role="dialog" aria-labelledby="descriptionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="descriptionModalLabel">Policy Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="policy-details">
                        <h6>Title</h6>
                        <p id="policyTitle" class="fw-bold"></p>
                        <h6 id="departmentsLabel" style="display: none;">Departments</h6>
                        <p id="policyDepartments" style="display: none;"></p>
                        <h6 id="creatorLabel" style="display: none;">Created By</h6>
                        <p id="policyCreator" style="display: none;"></p>
                    </div>
                    <div class="policy-content" id="policyContent">
                        <!-- Policy content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    {{-- <button type="button" class="btn btn-outline-secondary btn-copy" onclick="copyPolicyContent()">
                    <i class="fas fa-copy me-2"></i>Copy Content
                </button>
                <button type="button" class="btn btn-outline-info btn-print" onclick="printPolicyContent()">
                    <i class="fas fa-print me-2"></i>Print
                </button> --}}
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewDescription(title, content, departments, creator) {
            // Decode content
            let decodedContent = decodeURIComponent(content).replace(/\+/g, ' ');

            // Set modal content
            document.getElementById('policyTitle').textContent = title;
            document.getElementById('policyContent').innerHTML = decodedContent;

            // Handle departments (for department-specific policies)
            if (departments && departments !== 'N/A') {
                document.getElementById('policyDepartments').textContent = departments;
                document.getElementById('departmentsLabel').style.display = 'block';
                document.getElementById('policyDepartments').style.display = 'block';
            } else {
                document.getElementById('departmentsLabel').style.display = 'none';
                document.getElementById('policyDepartments').style.display = 'none';
            }

            // Handle creator (if available)
            if (creator && creator !== 'N/A') {
                document.getElementById('policyCreator').textContent = creator;
                document.getElementById('creatorLabel').style.display = 'block';
                document.getElementById('policyCreator').style.display = 'block';
            } else {
                document.getElementById('creatorLabel').style.display = 'none';
                document.getElementById('policyCreator').style.display = 'none';
            }

            // Show modal
            var descriptionModal = new bootstrap.Modal(document.getElementById('descriptionModal'), {
                keyboard: true,
                focus: true
            });
            descriptionModal.show();
        }

        function copyPolicyContent() {
            const content = document.getElementById('policyContent').innerText;
            navigator.clipboard.writeText(content).then(() => {
                alert('Policy content copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
                alert('Failed to copy content.');
            });
        }

        function printPolicyContent() {
            const title = document.getElementById('policyTitle').innerText;
            const departments = document.getElementById('policyDepartments').innerText;
            const creator = document.getElementById('policyCreator').innerText;
            const content = document.getElementById('policyContent').innerHTML;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
            <html>
                <head>
                    <title>${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { font-size: 24px; }
                        h6 { font-size: 14px; margin-top: 10px; }
                        p { font-size: 16px; line-height: 1.6; }
                        .content { margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <h1>${title}</h1>
                    ${departments && departments !== 'N/A' ? `<h6>Departments</h6><p>${departments}</p>` : ''}
                    ${creator && creator !== 'N/A' ? `<h6>Created By</h6><p>${creator}</p>` : ''}
                    <div class="content">${content}</div>
                </body>
            </html>
        `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
@endsection
