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
                        <h3 class="page-title">Create Contract Template</h3>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('contract-templates.store') }}" method="POST"
                                enctype="multipart/form-data" id="contract-form">
                                @csrf

                                <!-- Basic Info Fields -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Template Name *</label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type">Template Type *</label>
                                            <select class="form-control" id="type" name="type" required>
                                                <option value="Fix-Flex">Fix-Flex Contract</option>
                                                <option value="Permanent">Permanent Contract</option>
                                                <option value="Temporary">Temporary Contract</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Word Document Import -->
                                <div class="form-group">
                                    <label for="word_file">Import Word Document</label>
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="word_file" name="word_file"
                                            accept=".doc,.docx">
                                        <button type="button" class="btn btn-primary" id="import_word">
                                            <i class="fas fa-file-word"></i> Import with Exact Formatting
                                        </button>
                                    </div>
                                    <small class="text-muted">Upload a Word document to import with identical formatting</small>
                                </div>

                                <!-- Template Tools -->
                                <div class="template-tools mb-3 p-3 bg-light rounded">
                                    <h5>Dynamic Fields:</h5>
                                    @php
                                        $placeholders = [
                                            'employee_name',
                                            'job_title',
                                            'start_date',
                                            'end_date',
                                            'salary',
                                            'department',
                                            'duty_station',
                                            'probation_period',
                                        ];
                                    @endphp

                                    @foreach ($placeholders as $field)
                                        <span class="badge bg-info me-2 mb-2 cursor-pointer"
                                            onclick="insertPlaceholder('{{ $field }}')">
                                            {{ $field }}
                                        </span>
                                    @endforeach
                                </div>

                                <!-- Rich Text Editor -->
                                <div class="form-group">
                                    <label>Template Content *</label>
                                    <div id="summernote"></div>
                                    <textarea id="content" name="content" style="display:none;"></textarea>
                                    <small class="text-muted">
                                        Use square brackets for dynamic fields like <span
                                            style="font-family: monospace;">[employee_name]</span>
                                    </small>
                                </div>

                                <div class="form-group text-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Template
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

    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        .note-editor {
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .note-editable {
            min-height: 500px;
            padding: 15px;
            background-color: white;
            font-family: inherit;
            font-size: inherit;
        }

        /* List styling */
        .note-editable ul {
            list-style-type: disc !important;
            padding-left: 40px !important;
            margin: 10px 0 !important;
        }

        .note-editable ol {
            list-style-type: decimal !important;
            padding-left: 40px !important;
            margin: 10px 0 !important;
        }

        /* Table styling */
        .note-editable table {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 15px 0;
            border: 1px solid #dee2e6;
        }

        .note-editable td, 
        .note-editable th {
            border: 1px solid #dee2e6 !important;
            padding: 8px !important;
            vertical-align: top;
        }

        .note-editable th {
            background-color: #f8f9fa;
        }

        .template-tools {
            background: #f8f9fa;
        }

        .cursor-pointer {
            cursor: pointer;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mammoth@1.4.8/mammoth.browser.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Summernote with custom settings
            $('#summernote').summernote({
                placeholder: 'Write your contract template here...',
                height: 500,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'hr']],
                    ['view', ['codeview', 'help']]
                ],
                codeviewFilter: false,
                codeviewIframeFilter: false,
                callbacks: {
                    onChange: function(contents) {
                        $('#content').val(contents);
                    },
                    onInit: function() {
                        $('.note-editable').css('cssText', 
                            'font-family: inherit !important; ' +
                            'font-size: inherit !important; ' +
                            'line-height: inherit !important;'
                        );
                    }
                }
            });

            // Form submission handler
            $('#contract-form').on('submit', function(e) {
                e.preventDefault();
                $('#content').val($('#summernote').summernote('code'));
                this.submit();
            });

            // Insert placeholder
            window.insertPlaceholder = function(field) {
                const placeholder = '[' + field + ']';
                $('#summernote').summernote('editor.insertText', placeholder);
                $('#summernote').summernote('editor.focus');
            };

            // Word document import handler
            $('#import_word').on('click', function() {
                const fileInput = document.getElementById('word_file');
                if (!fileInput.files.length) {
                    alert('Please select a Word document first');
                    return;
                }

                const file = fileInput.files[0];
                const reader = new FileReader();

                reader.onload = function(event) {
                    mammoth.convertToHtml({
                            arrayBuffer: event.target.result
                        }, {
                            styleMap: [
                                "p[style-name='Heading 1'] => h1:fresh",
                                "p[style-name='Heading 2'] => h2:fresh",
                                "p[style-name='Heading 3'] => h3:fresh",
                                "p[style-name='List Paragraph'] => ul > li:fresh",
                                "r[style-name='List Paragraph Char'] => span",
                                "table => table",
                                "p[style-name='*'] => p:fresh",
                                "r[style-name='*'] => span"
                            ],
                            preserveDocumentStyles: true,
                            includeEmbeddedStyleMap: true,
                            includeDefaultStyleMap: true
                        })
                        .then(function(result) {
                            let html = result.value;
                            
                            // Preserve Word's list structure
                            html = html.replace(/<p[^>]*>•/g, '<ul><li>');
                            html = html.replace(/<\/p>\s*<p[^>]*>•/g, '</li><li>');
                            html = html.replace(/<\/p>/g, '</li></ul>');
                            
                            // Insert into editor
                            $('#summernote').summernote('code', html);
                            
                            // Force list styling
                            $('.note-editable ul').css({
                                'list-style-type': 'disc',
                                'padding-left': '40px'
                            });
                        })
                        .catch(function(error) {
                            console.error('Conversion error:', error);
                            alert('Error converting document: ' + error.message);
                        });
                };

                reader.readAsArrayBuffer(file);
            });
        });
    </script>
@endsection