@extends('layouts.template')
@include('sweetalert::alert')

@section('content')
    <!-- Include Quill's CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Edit Policy</h5>
                        </div>
                        <br>
                        <div class="container">
                            <form action="{{ route('policies.update', $policy->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-group mb-3">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        value="{{ old('title', $policy->title) }}" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="content">Description</label>
                                    <textarea id="content" class="form-control" name="content">{{ old('content', $policy->content) }}</textarea>
                                </div>
                                <div class="form-group mb-3">
                                    <button type="submit" class="btn btn-primary">Update Policy</button>
                                    <a href="{{ route('policies.index') }}" class="btn btn-secondary">Back</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include CKEditor Script -->
    <script src="https://cdn.ckeditor.com/ckeditor5/38.0.1/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#content'))
            .catch(error => console.error(error));
    </script>
@endsection
