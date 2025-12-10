@extends('layouts.template')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Standard Operating Procedures (SOPs)</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Department</th>
                                        <th class="text-center" style="width: 100px;">View</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sops as $sop)
                                        <tr>
                                            <td>{{ $sop->title }}</td>
                                            <td>{{ $sop->dept_name }}</td>
                                            <td class="text-center">
                                                @if ($sop->pdf_path)
                                                    <a href="{{ asset('storage/' . $sop->pdf_path) }}" target="_blank"
                                                        class="btn btn-warning btn-sm" title="View PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
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
@endsection
