@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container">
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <!-- Card Header -->
                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <h4 class="mb-0">Notice Board</h4>
                            @if(Auth::user()->hasAnyRole(['hr', 'line-manager', 'cfo', 'cms', 'coo', 'super-admin', 'Super-Admin']))
                                <a href="{{ route('announcements.create') }}" class="btn btn-primary" style="background-color: #007A33; border-color: #007A33;">
                                    <i class="fas fa-plus me-1"></i> Add Notice
                                </a>
                            @endif
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            @if ($announcements->isEmpty())
                                <div class="text-center py-5">
                                    <p class="lead text-muted">No Notice available at the moment.</p>
                                </div>
                            @else
                                <div class="accordion" id="announcementAccordion">
                                    @foreach ($announcements as $index => $announcement)
                                        <div class="accordion-item border-0 mb-2 shadow-sm">
                                            <!-- Accordion Header -->
                                            <h2 class="accordion-header" id="heading{{ $announcement->id }}">
                                                <button class="accordion-button collapsed bg-white" type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#collapse{{ $announcement->id }}" aria-expanded="false"
                                                        aria-controls="collapse{{ $announcement->id }}">
                                                    <div class="w-100 d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-success me-3">{{ $index + 1 }}</span>
                                                            <span class="fs-5 text-dark">{{ $announcement->title }}</span>
                                                        </div>
                                                        <small class="text-muted text-end">
                                                            Posted by
                                                            <strong>{{ $announcement->user->username ?? 'Unknown' }}</strong>,
                                                            {{ $announcement->created_at->diffForHumans() }}
                                                        </small>
                                                    </div>
                                                </button>
                                            </h2>

                                            <!-- Accordion Body -->
                                            <div id="collapse{{ $announcement->id }}" class="accordion-collapse collapse"
                                                 aria-labelledby="heading{{ $announcement->id }}"
                                                 data-bs-parent="#announcementAccordion">
                                                <div class="accordion-body">
                                                    <!-- Announcement Content -->
                                                    <div class="mb-4" style="font-size: 1.1rem; color: #333; line-height: 1.6;">
                                                        {!! $announcement->content !!}
                                                    </div>

                                                    <!-- Action Buttons -->
                                                    <div class="d-flex gap-2">
                                                        @if ($announcement->pdf_path)
                                                            <a href="{{ Storage::url($announcement->pdf_path) }}"
                                                               class="btn btn-outline-success btn-sm" target="_blank">
                                                                <i class="fas fa-file-pdf me-1"></i> View PDF
                                                            </a>
                                                        @endif

                                                        @if(Auth::user()->hasAnyRole(['hr', 'line-manager', 'cfo', 'cms', 'coo', 'super-admin', 'Super-Admin']))
                                                            <a href="{{ route('announcements.edit', $announcement->id) }}"
                                                               class="btn btn-outline-warning btn-sm">
                                                                <i class="fas fa-pencil-alt me-1"></i> Edit
                                                            </a>

                                                            <form
                                                                action="{{ route('announcements.destroy', $announcement->id) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                                                        onclick="return confirm('Are you sure you want to delete this announcement?')">
                                                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .accordion-button:not(.collapsed) {
            background-color: #f8f9fa;
            color: #007A33;
        }
        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(0, 122, 51, 0.25);
        }
        .accordion-body {
            background-color: #ffffff;
        }
        .accordion-item {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
    </style>
@endpush
