@extends('nav.app')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <div class="page-sub-header">
                    <h3 class="page-title">Next Of Kins Panel</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="students.html">Next Of Kins</a></li>
                        <li class="breadcrumb-item active">Register Your Next Of Kins</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="card comman-shadow">
                <div class="card-body">
                    <form method="POST" action="{{ route('nextOfKins.addNextOfKins') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user_id }}">
                        <div class="row">
                            <div class="col-12">
                                <h5 class="form-title employee-info">Employee Information <span><a href="javascript:;"><i class="feather-more-vertical"></i></a></span></h5>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="form-group local-forms">
                                    <label>Full Name <span class="login-danger">*</span></label>
                                    <input class="form-control" type="text" name="full_name">
                                </div>
                            </div>

                            <div class="col-12 col-sm-4">
                                <div class="form-group local-forms">
                                    <label>Relation <span class="login-danger">*</span></label>
                                    <input class="form-control" type="text" name="relationship">
                                </div>
                            </div>

                            <div class="col-12 col-sm-4">
                                <div class="form-group local-forms">
                                    <label>Address <span class="login-danger">*</span></label>
                                    <input class="form-control" type="text" name="address">
                                </div>
                            </div>
                            
                            <div class="col-12 col-sm-4">
                                <div class="form-group local-forms">
                                    <label>Mobile <span class="login-danger">*</span></label>
                                    <input class="form-control" type="text" name="mobile">
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="form-group local-forms">
                                    <label>Email <span class="login-danger">*</span></label>
                                    <input class="form-control" type="text" name="email">
                                </div>
                            </div>
  
                            <div class="col-12 col-sm-4">
                                <div class="form-group local-forms">
                                    <label>Occupation <span class="login-danger">*</span></label>
                                    <input class="form-control" type="text" name="occupation">
                                </div>
                            </div>              
                  
                            <div class="col-12 col-sm-4">
                                <div class="form-group local-forms">
                                    <label>Phone </label>
                                    <input class="form-control" type="text" name="mobile">
                                </div>
                            </div>
                           
                            <div class="col-12">
                                <div class="student-submit">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
