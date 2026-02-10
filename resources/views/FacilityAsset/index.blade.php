@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page Header --}}
        <div class="d-flex align-items-center mb-4">
            <img src="{{ asset('assets/img/logo-small.png') }}" alt="Logo" style="height: 60px; margin-right: 15px;">
            <h3 class="page-title mb-0">Facility Asset Registry</h3>
        </div>

        <div class="card shadow-lg">
            <div class="card-body">

                {{-- Button add new assets --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">New Facility Asset</h5>
                    <a href="{{ route('facilityAssets.create') }}" class="btn btn-success">
                      <i class="fa-solid fa-plus"></i> Add New Asset
                     </a>
                </div>
                {{-- Filters --}}
                <div class="row mb-3 g-2">
                    <div class="col-md-2">
                        <label>Type</label>
                        <select id="filter-type" class="form-control">
                            <option value="">All</option>
                            @foreach($assets->pluck('type')->unique() as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Status</label>
                        <select id="filter-status" class="form-control">
                            <option value="">All</option>
                            @foreach($assets->pluck('status')->unique() as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Vendor</label>
                        <select id="filter-vendor" class="form-control">
                            <option value="">All</option>
                            @foreach($assets->pluck('vendor')->unique() as $vendor)
                                <option value="{{ $vendor }}">{{ $vendor }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mt-2">
                        <label>Purchase Date</label>
                        <input type="date" id="filter-date-max" class="form-control">
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="assets-table" class="table table-hover table-bordered align-middle">
                        <thead class="table-custom-head text-center">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Model</th>
                                <th>Manufacturer</th>
                                <th>Serial Number</th>
                                <th>Purchase Date</th>
                                <th>Purchase Price</th>
                                <th>Vendor</th>
                                <th>Vendor Contact</th>
                                <th>Facility Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $index => $asset)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $asset->name }}</td>
                                    <td>{{ $asset->type }}</td>
                                    <td>{{ $asset->description }}</td>
                                    <td>{{ $asset->model }}</td>
                                    <td>{{ $asset->manufacturer }}</td>
                                    <td>{{ $asset->serial_number }}</td>
                                    <td>{{ $asset->purchase_date }}</td>
                                    <td>{{ $asset->purchase_price }}</td>
                                    <td>{{ $asset->vendor }}</td>
                                    <td>{{ $asset->vendor_contact }}</td>
                                    <td>{{ $asset->facilityLocation?->name ?? 'No Location Assigned' }}</td>
                                    <td>{{ ucfirst($asset->status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center text-gray-500">No assets found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>

/* Dropdown Filters Styling */
.form-control {
    border: 2px solid #61ce70; /* Company green */
    border-radius: 10px;        /* Rounded corners */
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #4caf50; /* Slightly darker green on focus */
    box-shadow: 0 0 5px rgba(97, 206, 112, 0.5);
    outline: none;
}

/* Table Head */
.table-custom-head {
    background-color: #f5f5f5 !important; /* light grey */
    color: #333;
}

/* Hoverable Rows */
#assets-table tbody tr {
    transition: background-color 0.2s;
}
#assets-table tbody tr:hover {
    background-color: #d1e7fd; /* light hover effect */
}

/* Alternating Row Colors */
#assets-table tbody tr:nth-child(odd) {
    background-color: #f9f9f9;
}
#assets-table tbody tr:nth-child(even) {
    background-color: #ffffff;
}
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(document).ready(function () {
    var logoBase64 = "";
    fetch("{{ asset('assets/img/logo-small.png') }}")
        .then(res => res.blob())
        .then(blob => {
            var reader = new FileReader();
            reader.onloadend = function () { logoBase64 = reader.result; }
            reader.readAsDataURL(blob);
        });

    var table = $('#assets-table').DataTable({
        dom: 'Bfrtip',
        order: [[0, 'desc']], // default descending
        buttons: [
            { extend: 'excelHtml5', title: 'Facility Asset Registry', messageTop: 'Generated on: ' + new Date().toLocaleDateString() },
            { extend: 'csvHtml5', title: 'Facility Asset Registry' },
            { 
                extend: 'pdfHtml5',
                title: 'Facility Asset Registry',
                customize: function (doc) {
                    doc.content.splice(0, 0, {
                        image: logoBase64,
                        width: 60,
                        alignment: 'center',
                        margin: [0, 0, 0, 10]
                    });
                    doc.styles.tableHeader.alignment = 'center';
                }
            },
            { 
                extend: 'print',
                title: 'Facility Asset Registry',
                customize: function (win) {
                    $(win.document.body).prepend(
                        '<div style="text-align:center; margin-bottom:10px;">' +
                        '<img src="{{ asset('assets/img/logo-small.png') }}" alt="Company Logo" class="max-w-[60px] mx-auto"><br>' +
                        '<h3>Facility Asset Registry</h3>' +
                        '</div>'
                    );
                }
            }
        ],
        responsive: true
    });

    // Filters
    $('#filter-type').on('change', function () { table.column(2).search(this.value).draw(); });
    $('#filter-status').on('change', function () { table.column(12).search(this.value).draw(); });
    $('#filter-vendor').on('change', function () { table.column(9).search(this.value).draw(); });
    
    // Date range filter
    $.fn.dataTable.ext.search.push(function(settings, data) {
        var minDate = $('#filter-date-min').val();
        var maxDate = $('#filter-date-max').val();
        var purchaseDate = data[7]; // Purchase Date column
        if (minDate && purchaseDate < minDate) return false;
        if (maxDate && purchaseDate > maxDate) return false;
        return true;
    });
    
    $('#filter-date-min, #filter-date-max').on('change', function () { table.draw(); });
});
</script>
@endpush
