@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container">
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h4 class="mb-0">Create Notice</h4>
                        </div>

                        <div class="card-body">
                            <!-- Announcement Creation Form -->
                            <form action="{{ route('announcements.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <!-- Title -->
                                <div class="form-group mb-3">
                                    <label for="title" class="form-label">Title <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title" class="form-control" required
                                        placeholder="Enter the title of the notice board">
                                </div>

                                <!-- Content (Enhanced Rich Text Editor) -->
                                <div class="form-group mb-3">
                                    <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                                    <textarea name="content" id="content" class="form-control" rows="12" required
                                        placeholder="Write your notice board here...">{{ old('content') }}</textarea>
                                    <small class="form-text text-muted">
                                        Use the toolbar above to format your text with bold, italic, underline, colors, lists, tables, and more.
                                    </small>
                                </div>

                                <!-- PDF Upload -->
                                <div class="form-group mb-3">
                                    <label for="pdf" class="form-label">Upload PDF (Optional)</label>
                                    <input type="file" name="pdf" id="pdf" class="form-control" accept=".pdf"
                                        onchange="validateFile()">
                                    <small id="fileError" class="d-block mt-1 text-danger" style="display: none;"></small>
                                    <small class="form-text text-muted">
                                        Maximum file size: 5MB. Only PDF files are allowed.
                                    </small>
                                </div>

                                <!-- Buttons -->
                                <div class="d-flex justify-content-start">
                                    <a href="{{ route('announcements.index') }}" class="btn btn-secondary me-2">
                                        <i class="fas fa-arrow-left me-1"></i> Back
                                    </a>

                                    <button type="submit" class="btn btn-primary" style="background-color: #007A33; border-color: #007A33;">
                                        <i class="fas fa-paper-plane me-1"></i> Submit
                                    </button>
                                </div>

                            </form>
                        </div> <!-- End Card Body -->
                    </div> <!-- End Card -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <style>
        .note-editor.note-frame {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }
        .note-toolbar {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .note-editable {
            min-height: 400px;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
@endpush

@section('scripts')
    <!-- Summernote JS -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        // Wait for jQuery and Summernote to be loaded
        function initializeSummernote() {
            if (typeof jQuery === 'undefined') {
                console.error('jQuery is not loaded');
                setTimeout(initializeSummernote, 100);
                return;
            }

            if (typeof jQuery.fn.summernote === 'undefined') {
                console.error('Summernote is not loaded. Retrying...');
                setTimeout(initializeSummernote, 100);
                return;
            }

            // Enhanced toolbar with all formatting options
            const enhancedToolbar = [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color', 'backcolor']], // Text color and highlight/background color
                ['para', ['ul', 'ol', 'paragraph', 'height']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ];

            jQuery('#content').summernote({
                height: 400,
                toolbar: enhancedToolbar,
                placeholder: 'Write your notice board here...',
                popover: {
                    image: [
                        ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
                        ['float', ['floatLeft', 'floatRight', 'floatNone']],
                        ['remove', ['removeMedia']]
                    ],
                    link: [
                        ['link', ['linkDialogShow', 'unlink']]
                    ],
                    table: [
                        ['add', ['addRowDown', 'addRowUp', 'addColLeft', 'addColRight']],
                        ['delete', ['deleteRow', 'deleteCol', 'deleteTable']]
                    ]
                },
                callbacks: {
                    onBlur: function() {
                        // Ensure content is set when editor loses focus
                        jQuery('#content').val(jQuery('#content').summernote('code'));
                    },
                    onInit: function() {
                        // Set initial content if exists
                        var content = jQuery('#content').val();
                        if (content) {
                            jQuery('#content').summernote('code', content);
                        }
                    }
                }
            });
        }

        // Initialize when document is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeSummernote);
        } else {
            initializeSummernote();
        }

        // Also try with jQuery ready as backup
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(function() {
                initializeSummernote();
            });
        }

        // PDF Validation Script
        function validateFile() {
            const fileInput = document.getElementById('pdf');
            const file = fileInput.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedType = "application/pdf";
            const errorElement = document.getElementById('fileError');

            if (file) {
                if (file.type !== allowedType) {
                    showError("Please upload a valid PDF file.");
                    return false;
                } else if (file.size > maxSize) {
                    showError("File size should not exceed 5MB.");
                    return false;
                } else {
                    hideError();
                    return true;
                }
            }
            return true;
        }

        function showError(message) {
            const errorElement = document.getElementById('fileError');
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            errorElement.style.color = '#dc3545';
            document.getElementById('pdf').value = '';
        }

        function hideError() {
            document.getElementById('fileError').style.display = 'none';
        }

        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            // Sync Summernote content before validation
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.summernote !== 'undefined') {
                jQuery('#content').val(jQuery('#content').summernote('code'));
            }

            const pdfInput = document.getElementById('pdf');
            if (pdfInput.files.length > 0 && !validateFile()) {
                e.preventDefault();
                return false;
            }

            // Validate content is not empty
            const content = document.getElementById('content').value;
            if (!content || content.trim() === '' || content === '<p><br></p>') {
                e.preventDefault();
                alert('Please enter some content for the announcement.');
                return false;
            }
        });
    </script>
@endsection
