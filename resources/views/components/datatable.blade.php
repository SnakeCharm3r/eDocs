@php
    // $id is required. $options (array) optional to override defaults.
    $jsonOptions = json_encode($options ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

@push('styles')
<style>
    /* Table header and body unified padding */
    #{{ $id }} thead th,
    #{{ $id }} tbody td {
        padding: 0.75rem 1rem;
        vertical-align: middle;
        color: #212529;
    }

    /* Table header styling */
    #{{ $id }} thead th {
        background-color: #ffffff;
        font-weight: 600;
    }

    /* Highlight row on hover */
    #{{ $id }} tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    /* Smooth fade-in for table rows */
    #{{ $id }} tbody tr {
        display: none;
    }

    /* Pagination styling */
    .dataTables_paginate .paginate_button {
        margin: 0 2px;
        padding: 6px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: white;
        color: #007bff;
        cursor: pointer;
    }

    .dataTables_paginate .paginate_button.current {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }

    .dataTables_paginate .paginate_button:hover {
        background: #e9ecef;
        border-color: #dee2e6;
    }

    /* Search box styling */
    .dataTables_filter input {
        padding: 6px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        margin-left: 10px;
    }

    /* Info text styling */
    .dataTables_info {
        padding: 8px 0;
        color: #6c757d;
    }
</style>
@endpush

<div class="table-responsive">
    <table id="{{ $id }}" class="table table-hover table-striped align-middle w-100">
        <thead>
            {{ $thead ?? '' }}
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById(@json($id));
    if (!el) return;

    // Prevent double initialization
    var alreadyInit =
        (window.jQuery && $.fn.dataTable && $.fn.dataTable.isDataTable && $.fn.dataTable.isDataTable(el)) ||
        (window.DataTable && typeof DataTable.get === 'function' && DataTable.get(el)) ||
        el.dataset.dtInitialized === '1';

    if (alreadyInit) return;

    // Default config
    var defaults = {
        pageLength: 10,
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
        paging: true,
        searching: true,
        ordering: true,
        autoWidth: false,
        fixedHeader: true,
        responsive: true,
        layout: {
            topStart: {
                buttons: []
            },
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging'
        },
        language: {
            search: "",
            searchPlaceholder: "Search records...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        initComplete: function(settings, json) {
            console.log('DataTable initialized with', json.length, 'records');
        },
        drawCallback: function () {
            // Animate rows on draw
            var rows = el.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                row.style.display = 'none';
                $(row).fadeIn(400);
            });

            // Update table information after load
            var info = this.api().page.info();
            console.log('Table updated. Page', info.page, 'of', info.pages, '- Showing', info.recordsDisplay, 'records');
        }
    };

    var custom = {!! $jsonOptions !!} || {};
    var cfg = Object.assign({}, defaults, custom);

    // Initialize DataTable
    var dt = new DataTable(el, cfg);
    el.dataset.dtInitialized = '1';

    // Add event listeners for search and pagination
    dt.on('draw', function() {
        console.log('Table redrawn - search and pagination updated');
    });

    // Expose the DataTable instance globally for debugging
    window['dt_' + @json($id)] = dt;
});
</script>
@endpush