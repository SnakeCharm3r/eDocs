@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">BackupChecklist</h3>
                        </div>
                    </div>
                </div>
            </div>  

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="Pre-Restoration">

                <ul>
                   <li> <a href="#Pre-restoration"><p>Pre-Restoration Checklist</p></a> 
                            <div class="table-responsive">
                                   <table class="table border-0 star-student table-hover table-center mb-0 datatable table-striped">
                                    <thead class="student-thread">
                                 <tr>
                                <th>ID</th>
                                <th>Name</th>
                                {{-- <th>Language</th> --}}
                                <th>Department</th>
                                <th>Class</th>
                                {{-- <th>Type</th> --}}
                                <th>Status</th>
                                <th class="text-end">Action</th>
                                </tr>
                                  </thead>
                                <tbody>
                                <tr>
                                <td>PRE2309</td>
                                <td>
                                <h2>
                                <a>Acoustics</a>
                                </h2>
                                </td>
                                <td>English</td>
                                <td>Science</td>
                                <td>10</td>
                                <td>Book</td>
                                <td>
                                <span class="badge badge-success">In Stock</span>
                                </td>
                                <td class="text-end">
                                <div class="actions">
                                <a href="javascript:;" class="btn btn-sm bg-success-light me-2">
                                <i class="feather-eye"></i>
                                </a>
                                <a href="edit-books.html" class="btn btn-sm bg-danger-light">
                                <i class="feather-edit"></i>
                                </a>
                                </div>
                                </td>
                                </tr>
                                <tr>
                                <td>PRE2209</td>
                                <td>
                                <h2>
                                <a>Acoustics</a>
                                </h2>
                                </td>
                                <td>Geometry</td>
                                <td>Science</td>
                                <td>8</td>
                                <td>Book</td>
                                <td>
                                <span class="badge badge-success">In Stock</span>
                                </td>
                                <td class="text-end">
                                <div class="actions">
                                <a href="javascript:;" class="btn btn-sm bg-success-light me-2">
                                <i class="feather-eye"></i>
                                </a>
                                <a href="edit-books.html" class="btn btn-sm bg-danger-light">
                                <i class="feather-edit"></i>
                                </a>
                                </div>
                                </td>
                                </tr>
                                <tr>
                                <td>PRE2213</td>
                                <td>
                                <h2>
                                <a>Games</a>
                                </h2>
                                </td>
                                <td>English</td>
                                <td>General</td>
                                <td>9</td>
                                <td>Book</td>
                                <td>
                                <span class="badge badge-success">In Stock</span>
                                </td>
                                <td class="text-end">
                                <div class="actions">
                                <a href="javascript:;" class="btn btn-sm bg-success-light me-2">
                                <i class="feather-eye"></i>
                                </a>
                                <a href="edit-books.html" class="btn btn-sm bg-danger-light">
                                <i class="feather-edit"></i>
                                </a>
                                </div>
                                </td>
                                </tr>
                                <tr>
                                <td>PRE2143</td>
                                <td>
                                <h2>
                                <a>Chess</a>
                                </h2>
                                </td>
                                <td>English</td>
                                <td>General</td>
                                <td>7</td>
                                <td>Book</td>
                                <td>
                                <span class="badge badge-danger">Out of Stock</span>
                                </td>
                                <td class="text-end">
                                <div class="actions">
                                <a href="javascript:;" class="btn btn-sm bg-success-light me-2">
                                <i class="feather-eye"></i>
                                </a>
                                <a href="edit-books.html" class="btn btn-sm bg-danger-light">
                                <i class="feather-edit"></i>
                                </a>
                                </div>
                                </td>
                                </tr>
                                <tr>
                                <td>PRE2009</td>
                                <td>
                                <h2>
                                <a>Calculus</a>
                                </h2>
                                </td>
                                <td>English</td>
                                <td>Mathematics</td>
                                <td>9</td>
                                <td>Book</td>
                                <td>
                                <span class="badge badge-success">In Stock</span>
                                </td>
                                <td class="text-end">
                                <div class="actions">
                                <a href="javascript:;" class="btn btn-sm bg-success-light me-2">
                                <i class="feather-eye"></i>
                                </a>
                                <a href="edit-books.html" class="btn btn-sm bg-danger-light">
                                <i class="feather-edit"></i>
                                </a>
                                </div>
                                </td>
                                </tr>
                                <tr>
                                <td>PRE2431</td>
                                <td>
                                <h2>
                                <a>Visual Basic</a>
                                </h2>
                                </td>
                                <td>English</td>
                                <td>Computer Science</td>
                                <td>11</td>
                                <td>Book</td>
                                <td>
                                
                                <tr>
                                <span class="badge badge-danger">Out of Stock</span>
                                </td>
                                <td class="text-end">
                                <div class="actions">
                                <a href="javascript:;" class="btn btn-sm bg-success-light me-2">
                                <i class="feather-eye"></i>
                                </a>
                                <a href="edit-books.html" class="btn btn-sm bg-danger-light">
                                <i class="feather-edit"></i>
                                </a>
                                </div>
                                </td>
                                </tr>
                                </tbody>
                                </table>
                            </div>
                        </li>
                        {{-- code goes here --}}
                        <li> <a href="#Pre-restoration"><p>Post-Restoration Checklist</p></a> 
                            <div class="table-responsive">
                                   <table class="table border-0 star-student table-hover table-center mb-0 datatable table-striped">
                                    <thead class="student-thread">
                                 <tr>
                                <th>ID</th>
                                <th>Name</th>
                                {{-- <th>Language</th> --}}
                                <th>Department</th>
                                <th>Class</th>
                                {{-- <th>Type</th> --}}
                                <th>Status</th>
                                <th class="text-end">Action</th>
                                </tr>
                                  </thead>
                                <tbody>
                                <tr>
                                <td>PRE2309</td>
                                <td>
                                <h2>
                                <a>Acoustics</a>
                                </h2>
                                </td>
                                <td>English</td>
                                <td>Science</td>
                                <td>10</td>
                                <td>Book</td>
                                <td>
                         </li>
                    </ul>

                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('content')
@endsection
