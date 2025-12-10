@props(['id', 'class' => ''])

<table id="{{ $id }}" class="{{ $class }}">
    @if(isset($thead))
        <thead>
            {{ $thead }}
        </thead>
    @endif
    <tbody>
        {{ $slot }}
    </tbody>
</table>

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

    // Default configuration for vendor table
    var defaults = {
        pageLength: 10,
        lengthMenu: [[10, 20, 50, 100, -1], [10, 20, 50, 100, 'All']],
        paging: true,
        searching: true,
        ordering: true,
        autoWidth: false,
        order: [[0, 'desc']], // Sort by ID descending by default
        layout: {
            topStart: ['pageLength', {}],
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging'
        }
    };

    // Initialize DataTable
    var dt = new DataTable(el, defaults);
    el.dataset.dtInitialized = '1';
});
</script>
@endpush

