@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <style>
            .certificate-template-card { border:0; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.07); }
            .certificate-template-card .card-header { background:#f8fdf9; border-bottom:1px solid #d1e7dd; }
            .certificate-template-preview-note code { color:#166534; background:#eef8ef; padding:.1rem .35rem; border-radius:.25rem; }
        </style>

        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Certificate Template</h3>
                    <p class="text-muted small mb-0">Manage the logo and text used in the downloadable certificate PDF.</p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('certificate-of-service.create') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Create
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="card certificate-template-card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-file-signature me-2 text-success"></i>PDF Template Settings</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-light border certificate-template-preview-note">
                    Available placeholders: <code>{name}</code> <code>{staff_code}</code> <code>{position}</code> <code>{department}</code> <code>{start_date}</code> <code>{end_date}</code> <code>{issue_date}</code>
                </div>

                <form action="{{ route('certificate-of-service.template.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Template Logo</label>
                            <input type="file" name="certificate_logo" class="form-control @error('certificate_logo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
                            @error('certificate_logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Optional. Upload a new logo to replace the current template logo.</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <img src="{{ $certificateLogoSrc }}" alt="Certificate Logo" style="max-height:80px;max-width:100%;object-fit:contain;border:1px solid #dee2e6;border-radius:8px;padding:.5rem;background:#fff;">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Official Stamp / Seal</label>
                            <input type="file" name="certificate_stamp" class="form-control @error('certificate_stamp') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
                            @error('certificate_stamp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Optional. PNG with transparent background recommended.</div>

                            <div class="mt-2">
                                <label class="form-label small fw-semibold mb-1">Stamp Position</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="stamp_position" id="stampRight" value="right" {{ ($stampPosition ?? 'right') === 'right' ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="stampRight">
                                            <i class="fas fa-align-right me-1 text-muted"></i>Right side
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="stamp_position" id="stampCenter" value="center" {{ ($stampPosition ?? 'right') === 'center' ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="stampCenter">
                                            <i class="fas fa-align-center me-1 text-muted"></i>Center
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            @if($certificateStampSrc)
                                <img src="{{ $certificateStampSrc }}" alt="Certificate Stamp" style="max-height:80px;max-width:100%;object-fit:contain;border:1px solid #dee2e6;border-radius:8px;padding:.5rem;background:#fff;">
                            @else
                                <div style="height:80px;width:80px;border:1px dashed #dee2e6;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f8f9fa;">
                                    <span class="text-muted" style="font-size:.72rem;text-align:center;">No stamp<br>uploaded</span>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Title</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $templateSettings['title']) }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Subtitle</label>
                            <input type="text" name="subtitle" class="form-control @error('subtitle') is-invalid @enderror" value="{{ old('subtitle', $templateSettings['subtitle']) }}" required>
                            @error('subtitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Start Date Label</label>
                            <input type="text" name="start_date_label" class="form-control @error('start_date_label') is-invalid @enderror" value="{{ old('start_date_label', $templateSettings['start_date_label']) }}" required>
                            @error('start_date_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Position Label</label>
                            <input type="text" name="position_label" class="form-control @error('position_label') is-invalid @enderror" value="{{ old('position_label', $templateSettings['position_label']) }}" required>
                            @error('position_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">End Date Label</label>
                            <input type="text" name="end_date_label" class="form-control @error('end_date_label') is-invalid @enderror" value="{{ old('end_date_label', $templateSettings['end_date_label']) }}" required>
                            @error('end_date_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Intro Text</label>
                            <textarea name="intro_text" rows="3" class="form-control @error('intro_text') is-invalid @enderror" required>{{ old('intro_text', $templateSettings['intro_text']) }}</textarea>
                            @error('intro_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Closing Text</label>
                            <textarea name="closing_text" rows="3" class="form-control @error('closing_text') is-invalid @enderror" required>{{ old('closing_text', $templateSettings['closing_text']) }}</textarea>
                            @error('closing_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Signer Name Override</label>
                            <input type="text" name="signer_name" class="form-control @error('signer_name') is-invalid @enderror" value="{{ old('signer_name', $templateSettings['signer_name']) }}" placeholder="Leave blank to use current COO">
                            @error('signer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Signer Title</label>
                            <input type="text" name="signer_title" class="form-control @error('signer_title') is-invalid @enderror" value="{{ old('signer_title', $templateSettings['signer_title']) }}" required>
                            @error('signer_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2 flex-wrap justify-content-end">
                        <a href="{{ route('certificate-of-service.create') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Save Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection