@extends('layouts.template_noscripts')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Edit Contract Template</h3>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('contract-templates.update', $template->id) }}" method="POST" enctype="multipart/form-data" id="contract-form">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Template Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $template->name) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type">Template Type *</label>
                                            <select class="form-control" id="type" name="type" required>
                                                <option value="Fix-Flex" {{ old('type', $template->type) == 'Fix-Flex' ? 'selected' : '' }}>Fix-Flex Contract</option>
                                                <option value="Permanent" {{ old('type', $template->type) == 'Permanent' ? 'selected' : '' }}>Permanent Contract</option>
                                                <option value="Temporary" {{ old('type', $template->type) == 'Temporary' ? 'selected' : '' }}>Temporary Contract</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="template-tools mb-3 p-3 bg-light rounded">
                                    <h5>Dynamic Fields:</h5>
                                    @php
                                        $placeholders = ['employee_name','job_title','start_date','end_date','salary','department','duty_station','probation_period'];
                                    @endphp

                                    @foreach ($placeholders as $field)
                                        <span class="badge bg-info me-2 mb-2 cursor-pointer" onclick="insertPlaceholder('{{ $field }}')">
                                            {{ $field }}
                                        </span>
                                    @endforeach
                                </div>

                                <div class="form-group">
                                    <label>Template Content *</label>
                                    <div id="summernote">{!! old('content', $template->content) !!}</div>
                                    <textarea id="content" name="content" style="display:none;">{!! old('content', $template->content) !!}</textarea>
                                    <small class="text-muted">
                                        Use square brackets for dynamic fields like <span style="font-family: monospace;">[employee_name]</span>
                                    </small>
                                </div>

                                <div class="form-group text-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Template
                                    </button>
                                    <a href="{{ route('contract-templates.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                placeholder: 'Edit contract template...',
                height: 500,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'hr']],
                    ['view', ['codeview', 'help']]
                ],
                callbacks: {
                    onChange: function(contents) {
                        $('#content').val(contents);
                    }
                }
            });

            window.insertPlaceholder = function(field) {
                const placeholder = '[' + field + ']';
                $('#summernote').summernote('editor.insertText', placeholder);
                $('#summernote').summernote('editor.focus');
            };

            $('#contract-form').on('submit', function(e) {
                e.preventDefault();
                $('#content').val($('#summernote').summernote('code'));
                this.submit();
            });
        });
    </script>
@endsection
