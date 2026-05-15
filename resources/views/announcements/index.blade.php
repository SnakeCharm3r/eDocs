@extends('layouts.template')

@push('styles')
    {{-- DataTables Bootstrap 5 (same as hec-contracts) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .announcements-page {
            color: #1f2933;
        }

        .announcements-hero {
            background: linear-gradient(135deg, #ffffff 0%, #f5fbf7 100%);
            border: 1px solid #e3eee7;
            border-left: 4px solid #198754;
            border-radius: 8px;
            padding: 1rem 1.15rem;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        }

        .announcements-hero-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            border-radius: 8px;
            color: #198754;
            background: rgba(25, 135, 84, 0.11);
        }

        .announcements-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            color: #17212b;
        }

        .announcements-subtitle {
            margin: 0.2rem 0 0;
            color: #64748b;
            font-size: 0.9rem;
        }

        .announcements-panel {
            border: 1px solid #e5eaee;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .announcements-panel-header {
            padding: 0.9rem 1rem;
            background: #fbfcfd;
            border-bottom: 1px solid #e5eaee;
        }

        .announcements-panel-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #25313f;
        }

        .announcement-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cbd5e1;
            display: inline-block;
        }

        .row-unread .announcement-status-dot {
            background: #198754;
            box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.12);
        }

        .announcement-card {
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .announcement-card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-3px);
        }

        .announcement-header {
            background: #f8f9fa;
            color: #212529;
            padding: 1.25rem;
            border-bottom: 1px solid #dee2e6;
        }

        .announcement-header h5 {
            color: #212529;
            font-weight: 600;
            font-size: 1.1rem;
            line-height: 1.4;
            margin-bottom: 0.5rem;
        }

        .announcement-content {
            max-height: 200px;
            overflow: hidden;
            position: relative;
            line-height: 1.6;
            color: #495057;
        }

        .announcement-content.expanded {
            max-height: none;
        }

        .read-more-btn {
            background: none;
            border: none;
            color: #212529;
            cursor: pointer;
            padding: 0;
            font-weight: 600;
            text-decoration: underline;
            transition: color 0.2s ease;
        }

        .read-more-btn:hover {
            color: #495057;
        }


        .announcements-table thead th {
            font-weight: 600;
            color: #212529;
            border-bottom: 1px solid #dfe6ec;
            white-space: nowrap;
        }

        .announcements-table tbody td {
            vertical-align: middle;
            padding-top: 0.85rem;
            padding-bottom: 0.85rem;
        }

        .announcements-table tbody tr.clickable-row {
            cursor: pointer;
        }

        .announcements-table tbody tr:hover {
            background-color: rgba(0, 122, 51, 0.06);
        }

        .announcements-table tbody tr.row-unread {
            background-color: rgba(25, 135, 84, 0.05);
            border-left: 3px solid #198754;
        }

        .announcements-table tbody tr.row-unread .announcement-title-text {
            font-weight: 500;
        }

        /* Blinking "New" badge – green only (no red); force override any theme/DataTables */
        .badge-new,
        span.badge.badge-new,
        #announcementsTable .badge-new {
            background-color: #198754 !important;
            background: #198754 !important;
            color: #fff !important;
            font-size: 0.7rem;
            padding: 0.35em 0.6em;
            font-weight: 600;
            border: none !important;
            box-shadow: 0 0 0 1px rgba(25, 135, 84, 0.3);
            animation: blink 1s ease-in-out infinite;
        }

        .badge-new:hover,
        span.badge.badge-new:hover,
        #announcementsTable .badge-new:hover {
            background-color: #157347 !important;
            background: #157347 !important;
            color: #fff !important;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .announcement-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .announcement-author-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #eef2f6;
            color: #566474;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .action-buttons .btn {
            transition: all 0.2s ease;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 6px;
        }

        .action-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Announcement view modal: message fits inside modal */
        .view-announcement-modal .announcement-view-modal-body {
            max-height: 75vh;
            overflow-y: auto;
        }

        .view-announcement-modal .modal-content {
            border: 1px solid #e5eaee;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
        }

        .view-announcement-modal .modal-header {
            background: #fff;
            color: #17212b;
            border-bottom: 1px solid #e5eaee;
            padding: 1rem 1.25rem;
        }

        .view-announcement-modal .modal-title {
            font-weight: 700;
            line-height: 1.35;
            color: #17212b;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 1.15rem;
        }

        .view-announcement-modal .modal-title-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 8px;
            background: #eef7f1;
            color: #198754;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .view-announcement-modal .btn-close {
            filter: none;
            opacity: 0.65;
        }

        .view-announcement-modal .btn-close:hover {
            opacity: 1;
        }

        .view-announcement-modal .announcement-view-modal-body {
            padding: 1.25rem;
            background: #fff;
        }

        .announcement-modal-meta {
            background: #f8fafb;
            border: 1px solid #e8eef2;
            border-radius: 8px;
            padding: 0.75rem 0.9rem;
        }

        .announcement-modal-pdf {
            border-top: 1px solid #edf1f4;
            padding-top: 1rem;
        }

        .announcement-modal-pdf .btn {
            border-radius: 6px;
        }

        .view-announcement-modal .announcement-modal-message {
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.75;
            font-size: 1.02rem;
            color: #25313f;
            max-width: 1180px;
        }

        .announcement-text-content p {
            margin: 0 0 0.9rem;
        }

        .announcement-text-content p:last-child {
            margin-bottom: 0;
        }

        /* Modal content: use ql-editor rendering but neutralize editor chrome */
        .announcement-full-content.ql-editor {
            border: none !important;
            padding: 0 !important;
            min-height: unset !important;
            height: auto !important;
            overflow: visible !important;
            cursor: default !important;
            font-size: 1rem;
            line-height: 1.7;
            word-wrap: break-word;
        }

        .announcement-full-content ul,
        .announcement-content ul {
            list-style-type: disc;
            margin: 0.5rem 0 0.75rem 1.5rem;
            padding-left: 0.5rem;
        }

        .announcement-full-content ol,
        .announcement-content ol {
            list-style-type: decimal;
            margin: 0.5rem 0 0.75rem 1.5rem;
            padding-left: 0.5rem;
        }

        .announcement-full-content li,
        .announcement-content li {
            margin-bottom: 0.25rem;
        }

        .announcement-full-content strong,
        .announcement-full-content b,
        .announcement-content strong,
        .announcement-content b {
            font-weight: 700;
        }

        .announcement-full-content em,
        .announcement-full-content i,
        .announcement-content em,
        .announcement-content i {
            font-style: italic;
        }

        .announcement-full-content u,
        .announcement-content u {
            text-decoration: underline;
        }

        .announcement-full-content s,
        .announcement-content s {
            text-decoration: line-through;
        }

        .announcement-full-content a,
        .announcement-content a {
            color: #007A33;
            text-decoration: underline;
        }

        .announcement-full-content a:hover,
        .announcement-content a:hover {
            text-decoration: none;
        }

        .announcement-full-content h1,
        .announcement-content h1 {
            font-size: 1.5rem;
            margin: 1rem 0 0.5rem;
            font-weight: 600;
        }

        .announcement-full-content h2,
        .announcement-content h2 {
            font-size: 1.25rem;
            margin: 0.75rem 0 0.5rem;
            font-weight: 600;
        }

        .announcement-full-content h3,
        .announcement-content h3 {
            font-size: 1.1rem;
            margin: 0.75rem 0 0.5rem;
            font-weight: 600;
        }

        #announcementsTable_wrapper {
            padding: 1rem;
        }

        #announcementsTable_filter input,
        #announcementsTable_length select {
            border-radius: 6px;
            border-color: #d9e2e8;
        }

        #announcementsTable_filter input:focus,
        #announcementsTable_length select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.15rem rgba(25, 135, 84, 0.14);
        }

        @media (max-width: 576px) {
            .announcements-hero {
                padding: 0.9rem;
            }

            .announcements-title {
                font-size: 1.15rem;
            }

            #announcementsTable_wrapper {
                padding: 0.75rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrapper announcements-page">
        <div class="content container-fluid">
            @include('sweetalert::alert')

            {{-- Page Header --}}
            <div class="announcements-hero mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="announcements-hero-icon d-flex align-items-center justify-content-center">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div>
                            <h4 class="announcements-title">Announcements</h4>
                            <p class="announcements-subtitle">Latest staff notices, documents, and operational updates.</p>
                        </div>
                    </div>
                    @if ($canManage ?? false)
                        <a href="{{ route('announcements.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Add Announcement
                        </a>
                    @endif
                </div>
            </div>

            {{-- Announcements Table (newest on top) --}}
            @if ($announcements->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="announcements-hero-icon d-inline-flex align-items-center justify-content-center mb-3">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h5 class="text-dark">No announcements found</h5>
                        <p class="text-muted mb-0">
                            @if ($canManage ?? false)
                                Click "Add Announcement" to create a new announcement.
                            @else
                                No announcements are available at the moment.
                            @endif
                        </p>
                    </div>
                </div>
            @else
                <div class="announcements-panel shadow-sm">
                    <div class="announcements-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="announcements-panel-title">
                            <i class="fas fa-table me-2 text-success"></i>All Announcements
                        </div>
                        @if (($unreadCount ?? 0) > 0)
                            <span class="badge bg-success">{{ $unreadCount }} new</span>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table id="announcementsTable"
                            class="table table-hover align-middle mb-0 announcements-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 3rem;"></th>
                                    <th>Title</th>
                                    <th>Posted by</th>
                                    <th>Date</th>
                                    <th class="text-end" style="min-width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($announcements as $index => $announcement)
                                    <tr class="clickable-row {{ !in_array($announcement->id, $viewedAnnouncementIds ?? []) ? 'row-unread' : '' }}"
                                        data-announcement-id="{{ $announcement->id }}"
                                        data-modal-target="#viewModal{{ $announcement->id }}">
                                        <td class="text-center"><span class="announcement-status-dot"></span></td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <div>
                                                    <span class="announcement-title-text">{{ Str::limit($announcement->title, 80) }}</span>
                                                    @if (!in_array($announcement->id, $viewedAnnouncementIds ?? []))
                                                        <span class="badge bg-success badge-new announcement-new-badge ms-1">New</span>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-inline-flex align-items-center gap-2">
                                                    @if ($announcement->pdf_path)
                                                        <span><i class="fas fa-file-pdf text-danger me-1"></i>PDF attached</span>
                                                    @endif
                                                    <span><i class="fas fa-eye text-success me-1"></i><span class="announcement-view-count" data-announcement-id="{{ $announcement->id }}">{{ $announcement->view_count ?? 0 }}</span> views</span>
                                                </small>
                                            </div>
                                        </td>
                                        <td class="text-muted">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="announcement-author-avatar">
                                                    {{ strtoupper(substr($announcement->user->username ?? 'U', 0, 1)) }}
                                                </span>
                                                <span>{{ $announcement->user->username ?? 'Unknown' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-muted"
                                            data-order="{{ $announcement->created_at->format('Y-m-d H:i:s') }}">
                                            <span title="{{ $announcement->created_at->format('d M Y, h:i A') }}">
                                                {{ $announcement->created_at->format('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="text-end" onclick="event.stopPropagation()">
                                            <div class="action-buttons d-flex gap-1 justify-content-end flex-wrap">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-success view-announcement-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewModal{{ $announcement->id }}"
                                                    data-announcement-id="{{ $announcement->id }}" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if ($canManage ?? false)
                                                    @if ($announcement->userId == Auth::id() || Auth::user()->hasAnyRole(['super-admin', 'Super-Admin']))
                                                    <a href="{{ route('announcements.edit', $announcement->id) }}"
                                                        class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal{{ $announcement->id }}"
                                                        title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- View Modal --}}
                                    <div class="modal fade view-announcement-modal" id="viewModal{{ $announcement->id }}"
                                        tabindex="-1" aria-labelledby="viewModalLabel{{ $announcement->id }}"
                                        aria-hidden="true" data-announcement-id="{{ $announcement->id }}"
                                        data-record-view-url="{{ route('announcements.record-view', ['id' => $announcement->id]) }}">
                                        <div class="modal-dialog modal-dialog-scrollable modal-xl">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewModalLabel{{ $announcement->id }}">
                                                        <span class="modal-title-icon"><i class="fas fa-bullhorn"></i></span>
                                                        <span>{{ $announcement->title }}</span>
                                                    </h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body announcement-view-modal-body">
                                                    <div class="announcement-modal-meta mb-3 d-flex flex-wrap gap-3 align-items-center">
                                                        <small class="text-muted">
                                                            <i class="fas fa-user me-1 text-success"></i>
                                                            Posted by
                                                            <strong>{{ $announcement->user->username ?? 'Unknown' }}</strong>
                                                            on {{ $announcement->created_at->format('d M Y, h:i A') }}
                                                        </small>
                                                        <small class="text-muted">
                                                            <i class="fas fa-eye me-1 text-success"></i>
                                                            <span class="announcement-view-count-modal"
                                                                data-announcement-id="{{ $announcement->id }}">{{ $announcement->view_count ?? 0 }}</span>
                                                            views
                                                        </small>
                                                    </div>
                                                    <div class="announcement-full-content announcement-modal-message ql-editor">
                                                        @php
                                                            $rawContent = trim((string) $announcement->content);
                                                            $plainContent = trim(strip_tags($rawContent));
                                                            $isPlainContent = $rawContent !== '' && $plainContent === $rawContent;
                                                            $paragraphs = [];

                                                            if ($isPlainContent) {
                                                                $paragraphs = preg_split('/\R{2,}/', $plainContent) ?: [];

                                                                if (count($paragraphs) === 1 && strlen($plainContent) > 360) {
                                                                    $sentences = preg_split('/(?<=[.!?])\s+/', $plainContent, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                                                                    $paragraphs = [];
                                                                    $currentParagraph = '';

                                                                    foreach ($sentences as $sentence) {
                                                                        $candidate = trim($currentParagraph . ' ' . $sentence);
                                                                        if ($currentParagraph !== '' && strlen($candidate) > 420) {
                                                                            $paragraphs[] = $currentParagraph;
                                                                            $currentParagraph = $sentence;
                                                                        } else {
                                                                            $currentParagraph = $candidate;
                                                                        }
                                                                    }

                                                                    if ($currentParagraph !== '') {
                                                                        $paragraphs[] = $currentParagraph;
                                                                    }
                                                                }
                                                            }
                                                        @endphp

                                                        @if ($isPlainContent)
                                                            <div class="announcement-text-content">
                                                                @foreach ($paragraphs as $paragraph)
                                                                    <p>{{ $paragraph }}</p>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            {!! $announcement->content !!}
                                                        @endif
                                                    </div>
                                                    @if ($announcement->pdf_path)
                                                        <div class="announcement-modal-pdf mt-3">
                                                            <a href="{{ Storage::url($announcement->pdf_path) }}"
                                                                class="btn btn-outline-success" target="_blank">
                                                                <i class="fas fa-file-pdf me-1"></i>View PDF Document
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Delete Modal --}}
                                    @if (($canManage ?? false) && ($announcement->userId == Auth::id() || Auth::user()->hasAnyRole(['super-admin', 'Super-Admin'])))
                                        <div class="modal fade" id="deleteModal{{ $announcement->id }}" tabindex="-1"
                                            aria-labelledby="deleteModalLabel{{ $announcement->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title"
                                                            id="deleteModalLabel{{ $announcement->id }}">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>Delete
                                                            Announcement
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white"
                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete this announcement?</p>
                                                        <div class="alert alert-warning">
                                                            <strong>{{ $announcement->title }}</strong>
                                                        </div>
                                                        <p class="text-muted small">This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <form
                                                            action="{{ route('announcements.destroy', $announcement->id) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-trash-alt me-1"></i>Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleContent(id) {
            const content = document.getElementById('content-' + id);
            const toggle = document.getElementById('toggle-' + id);
            const button = toggle.closest('button');
            const fullContent = button.getAttribute('data-full-content');
            const shortContent = button.getAttribute('data-short-content');

            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                content.innerHTML = shortContent;
                toggle.textContent = 'Read More';
            } else {
                content.classList.add('expanded');
                content.innerHTML = fullContent;
                toggle.textContent = 'Read Less';
            }
        }

        // Clickable row — open view modal
        document.querySelectorAll('.clickable-row[data-modal-target]').forEach(function(row) {
            row.addEventListener('click', function() {
                var target = this.getAttribute('data-modal-target');
                if (!target) return;
                var modalEl = document.querySelector(target);
                if (!modalEl) return;
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            });
        });

        // Record view when View modal is shown (staff viewing announcement)
        document.querySelectorAll('.view-announcement-modal').forEach(function(modalEl) {
            modalEl.addEventListener('show.bs.modal', function() {
                var id = this.getAttribute('data-announcement-id');
                var url = this.getAttribute('data-record-view-url');
                if (!id || !url) return;
                var token = document.querySelector('meta[name="csrf-token"]') && document.querySelector(
                    'meta[name="csrf-token"]').getAttribute('content');
                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        var count = data.view_count !== undefined ? data.view_count : 0;
                        document.querySelectorAll('.announcement-view-count[data-announcement-id="' +
                            id + '"]').forEach(function(el) {
                            el.textContent = count;
                        });
                        document.querySelectorAll(
                                '.announcement-view-count-modal[data-announcement-id="' + id + '"]')
                            .forEach(function(el) {
                                el.textContent = count;
                            });
                        var row = document.querySelector('tr[data-announcement-id="' + id + '"]');
                        if (row) {
                            var badge = row.querySelector('.announcement-new-badge');
                            if (badge) badge.remove();
                            row.classList.remove('row-unread');
                        }
                    })
                    .catch(function() {});
            });
        });

        // DataTables (same style as hec-contracts)
        $(document).ready(function() {
            if ($('#announcementsTable').length && $('#announcementsTable tbody tr').length > 0) {
                $('#announcementsTable').DataTable({
                    pageLength: 20,
                    lengthChange: true,
                    lengthMenu: [
                        [10, 20, 50, 100],
                        [10, 20, 50, 100]
                    ],
                    order: [
                        [3, 'desc']
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: [0, 4]
                    }],
                    language: {
                        search: 'Search:',
                        lengthMenu: 'Show _MENU_ announcements per page',
                        info: 'Showing _START_ to _END_ of _TOTAL_ announcements',
                        infoEmpty: 'Showing 0 to 0 of 0 announcements',
                        paginate: {
                            previous: '&laquo;',
                            next: '&raquo;'
                        }
                    }
                });
            }
        });
    </script>
@endpush
