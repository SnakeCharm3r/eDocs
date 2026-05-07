@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12 mb-3">
                        <h3 class="page-title">Create Change Request</h3>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm" style="border: 1px solid #d3d3d3;">
                        <div class="card-body">
                            <form action="{{ route('change_request.store') }}" method="POST" enctype="multipart/form-data"
                                id="changeRequestForm">
                                @csrf

                                <!-- Change Type Selection -->
                                <h4 class="mt-4 mb-3"
                                    style="color: #007A33; border-bottom: 2px solid #007A33; padding-bottom: 5px;">
                                    Change Type</h4>
                                <table class="table table-bordered" style="background-color: #f5f5f5;">
                                    <tr>
                                        <th style="width: 30%; background-color: #e9ecef;">Type of Change</th>
                                        <td>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="change_type"
                                                    id="nonPriceChange" value="non_price" checked required>
                                                <label class="form-check-label" for="nonPriceChange">
                                                    <strong>Non-Price Change</strong> (System Features, Policies, SOPs,
                                                    etc.)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="change_type"
                                                    id="priceChange" value="price" required>
                                                <label class="form-check-label" for="priceChange">
                                                    <strong>Price Change</strong> (Tariff, Service Prices, Medicine Prices,
                                                    etc.)
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Price Change Sections -->
                                <div id="priceChangeSections" style="display: none;">
                                    <!-- Price Change Type Selection (Tariff/Service/Price Change) -->
                                    <div id="priceChangeTypeSection" style="display: none;">
                                        <h4 class="mt-4 mb-3"
                                            style="color: #007A33; border-bottom: 2px solid #007A33; padding-bottom: 5px;">
                                            What Type of Price Change?</h4>
                                        <table class="table table-bordered" style="background-color: #f5f5f5;">
                                            <tr>
                                                <th style="width: 30%; background-color: #e9ecef;">Change Type</th>
                                                <td>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="radio"
                                                            name="price_change_type" id="tariffType" value="tariff">
                                                        <label class="form-check-label" for="tariffType">
                                                            <strong>Tariff</strong>
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="radio"
                                                            name="price_change_type" id="serviceType" value="service">
                                                        <label class="form-check-label" for="serviceType">
                                                            <strong>Service</strong>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                            name="price_change_type" id="priceChangeType"
                                                            value="price_change">
                                                        <label class="form-check-label" for="priceChangeType">
                                                            <strong>Price Change</strong> (Edit existing price)
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Tariff Section -->
                                    <div id="tariffSection" style="display: none;">
                                        <h4 class="mt-4 mb-3"
                                            style="color: #007A33; border-bottom: 2px solid #007A33; padding-bottom: 5px;">
                                            Tariff Details</h4>
                                        <table class="table table-bordered" style="background-color: #f5f5f5;">
                                            <tr>
                                                <th style="width: 30%; background-color: #e9ecef;">Tariff Action</th>
                                                <td>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="radio" name="tariff_type"
                                                            id="newTariff" value="new_tariff">
                                                        <label class="form-check-label" for="newTariff">
                                                            <strong>New Tariff</strong>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="tariff_type"
                                                            id="editTariff" value="edit_tariff">
                                                        <label class="form-check-label" for="editTariff">
                                                            <strong>Edit Tariff</strong>
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <!-- New Tariff Fields -->
                                            <tr id="newTariffFields" style="display: none;">
                                                <th style="background-color: #e9ecef;">Tariff Name</th>
                                                <td>
                                                    <input type="text" name="tariff_name" id="tariff_name"
                                                        class="form-control" placeholder="Enter tariff name">
                                                </td>
                                            </tr>
                                            <tr id="newTariffCategoryRow" style="display: none;">
                                                <th style="background-color: #e9ecef;">Tariff Category</th>
                                                <td>
                                                    <select name="tariff_category_id" id="tariff_category_id"
                                                        class="form-control">
                                                        <option value="">-- Select Category --</option>
                                                        @foreach ($tariffCategories as $category)
                                                            <option value="{{ $category->id }}">{{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                            <!-- Edit Tariff Fields -->
                                            <tr id="editTariffFields" style="display: none;">
                                                <th style="background-color: #e9ecef;">Current Tariff Name</th>
                                                <td>
                                                    <input type="text" name="current_tariff_name"
                                                        id="current_tariff_name" class="form-control"
                                                        placeholder="Enter current tariff name">
                                                </td>
                                            </tr>
                                            <tr id="editTariffCategoryRow" style="display: none;">
                                                <th style="background-color: #e9ecef;">Tariff Category</th>
                                                <td>
                                                    <select name="tariff_category_id_edit" id="tariff_category_id_edit"
                                                        class="form-control">
                                                        <option value="">-- Select Category --</option>
                                                        @foreach ($tariffCategories as $category)
                                                            <option value="{{ $category->id }}">{{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr id="editTariffNewNameRow" style="display: none;">
                                                <th style="background-color: #e9ecef;">New Tariff Name</th>
                                                <td>
                                                    <input type="text" name="tariff_name_edit" id="tariff_name_edit"
                                                        class="form-control" placeholder="Enter new tariff name">
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Service Section -->
                                    <div id="serviceSection" style="display: none;">
                                        <h4 class="mt-4 mb-3"
                                            style="color: #007A33; border-bottom: 2px solid #007A33; padding-bottom: 5px;">
                                            Service Details</h4>
                                        <table class="table table-bordered" style="background-color: #f5f5f5;">
                                            <tr>
                                                <th style="width: 30%; background-color: #e9ecef;">Service Action</th>
                                                <td>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="radio"
                                                            name="service_action_type" id="newService"
                                                            value="new_service">
                                                        <label class="form-check-label" for="newService">
                                                            <strong>New Service</strong>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                            name="service_action_type" id="editService"
                                                            value="edit_service">
                                                        <label class="form-check-label" for="editService">
                                                            <strong>Edit Service</strong>
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <!-- New Service Fields -->
                                            <tr id="newServiceFields" style="display: none;">
                                                <th style="background-color: #e9ecef;">Service Name</th>
                                                <td>
                                                    <input type="text" name="service_name" id="service_name"
                                                        class="form-control" placeholder="Enter service name">
                                                </td>
                                            </tr>
                                            <tr id="newServiceCategoryRow" style="display: none;">
                                                <th style="background-color: #e9ecef;">Service Category</th>
                                                <td>
                                                    <select name="service_category_id" id="service_category_id"
                                                        class="form-control">
                                                        <option value="">-- Select Category --</option>
                                                        @foreach ($serviceCategories as $category)
                                                            <option value="{{ $category->id }}"
                                                                data-payment-types="{{ json_encode($category->payment_types ?? []) }}">
                                                                {{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                            <!-- Service Prices (Dynamic based on category) -->
                                            <tr id="servicePricesRow" style="display: none;">
                                                <th style="background-color: #e9ecef;">Prices</th>
                                                <td>
                                                    <div id="servicePricesContainer"></div>
                                                </td>
                                            </tr>
                                            <!-- Edit Service Fields -->
                                            <tr id="editServiceFields" style="display: none;">
                                                <th style="background-color: #e9ecef;">Service Name</th>
                                                <td>
                                                    <input type="text" name="current_service_name"
                                                        id="current_service_name" class="form-control"
                                                        placeholder="Enter service name to edit">
                                                </td>
                                            </tr>
                                            <tr id="editServiceCurrentPriceRow" style="display: none;">
                                                <th style="background-color: #e9ecef;">Current Price</th>
                                                <td>
                                                    <input type="number" name="current_price" id="current_price"
                                                        class="form-control" step="0.01" placeholder="0.00">
                                                </td>
                                            </tr>
                                            <tr id="editServiceNewPriceRow" style="display: none;">
                                                <th style="background-color: #e9ecef;">New Price</th>
                                                <td>
                                                    <input type="number" name="new_price" id="new_price"
                                                        class="form-control" step="0.01" placeholder="0.00">
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Price Change Section -->
                                    <div id="priceChangeSection" style="display: none;">
                                        <h4 class="mt-4 mb-3"
                                            style="color: #007A33; border-bottom: 2px solid #007A33; padding-bottom: 5px;">
                                            Price Change Details</h4>
                                        <table class="table table-bordered" style="background-color: #f5f5f5;">
                                            <tr>
                                                <th style="width: 30%; background-color: #e9ecef;">Service Name</th>
                                                <td>
                                                    <input type="text" name="price_item_name" id="price_item_name"
                                                        class="form-control" placeholder="Enter service name">
                                                </td>
                                            </tr>
                                        </table>

                                        <h5 class="mt-4 mb-3" style="color: #007A33;">
                                            Price Details
                                            {{-- <a href="{{ route('payment-types.index') }}" class="btn btn-sm btn-outline-info float-end" target="_blank" title="Manage Payment Types">
                                                <i class="fas fa-cog"></i> Manage Payment Types
                                            </a> --}}
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" style="background-color: #f5f5f5;">
                                                <thead style="background-color: #e9ecef;">
                                                    <tr>
                                                        <th style="width: 20%;">Payment Type</th>
                                                        <th style="width: 40%;">Current Price</th>
                                                        <th style="width: 40%;">New Price</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($paymentTypes as $paymentType)
                                                        @php
                                                            $paymentKey = strtolower(str_replace(' ', '_', $paymentType->name));
                                                        @endphp
                                                        <tr>
                                                            <td><strong>{{ $paymentType->name }}</strong></td>
                                                            <td>
                                                                <input type="number" name="price_current[{{ $paymentKey }}]"
                                                                    class="form-control" step="0.01" placeholder="0.00">
                                                            </td>
                                                            <td>
                                                                <input type="number" name="price_new[{{ $paymentKey }}]"
                                                                    class="form-control" step="0.01" placeholder="0.00">
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3" class="text-center text-muted">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                No payment types found. <a href="{{ route('payment-types.create') }}">Create one</a> first.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        <table class="table table-bordered mt-3" style="background-color: #f5f5f5;">
                                            <tr>
                                                <th style="width: 30%; background-color: #e9ecef;">Reason for Change</th>
                                                <td>
                                                    <textarea name="price_change_reason" id="price_change_reason" class="form-control" rows="3"
                                                        placeholder="Enter reason for price change"></textarea>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <!-- Description of Change -->
                                <h4 class="mt-4 mb-3"
                                    style="color: #007A33; border-bottom: 2px solid #007A33; padding-bottom: 5px;">
                                    Description of Change</h4>
                                <table class="table table-bordered" style="background-color: #f5f5f5;">
                                    <tr>
                                        <th style="width: 30%; background-color: #e9ecef;">Description</th>
                                        <td>
                                            <textarea name="description_of_change" class="form-control" rows="4" required></textarea>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Reason for Change -->
                                <h4 class="mt-4 mb-3"
                                    style="color: #007A33; border-bottom: 2px solid #007A33; padding-bottom: 5px;">
                                    Reason for Change</h4>
                                <table class="table table-bordered" style="background-color: #f5f5f5;">
                                    <tr>
                                        <th style="width: 30%; background-color: #e9ecef;">Reason</th>
                                        <td>
                                            <textarea name="reason_for_change" class="form-control" rows="4" required></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="background-color: #e9ecef;">Priority</th>
                                        <td>
                                            <select name="priority" class="form-control" required>
                                                <option value="P1-High">P1-High</option>
                                                <option value="P2-Medium">P2-Medium</option>
                                                <option value="P3-Low">P3-Low</option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Implementation Notes -->
                                <h4 class="mt-4 mb-3"
                                    style="color: #007A33; border-bottom: 2px solid #007A33; padding-bottom: 5px;">
                                    Implementation Notes</h4>
                                <table class="table table-bordered" style="background-color: #f5f5f5;">
                                    <tr>
                                        <th style="width: 30%; background-color: #e9ecef;">Notes</th>
                                        <td>
                                            <textarea name="implementation_notes" class="form-control" rows="3"
                                                placeholder="Any additional notes for implementation"></textarea>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Supporting Document -->
                                <h4 class="mt-4 mb-3"
                                    style="color: #007A33; border-bottom: 2px solid #007A33; padding-bottom: 5px;">
                                    Supporting Document</h4>
                                <table class="table table-bordered" style="background-color: #f5f5f5;">
                                    <tr>
                                        <th style="width: 30%; background-color: #e9ecef;">Document</th>
                                        <td>
                                            <input type="file" name="supporting_document" class="form-control"
                                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                            <small class="text-muted">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max:
                                                2MB)</small>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Submit Button -->
                                <div class="text-center mt-4 mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn"
                                        style="background-color: #007A33; border-color: #007A33;">
                                        <span class="btn-text">
                                            <i class="fas fa-paper-plane"></i> Submit Request
                                        </span>
                                        <span class="btn-loading" style="display: none;">
                                            <span class="spinner-border spinner-border-sm" role="status"
                                                aria-hidden="true"></span>
                                            Submitting...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <style>
        /* Enhanced Summernote toolbar styling */
        .note-toolbar {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 8px;
        }

        .note-btn-group {
            margin-right: 5px;
        }

        .note-btn-group .note-btn {
            padding: 5px 10px;
            border: 1px solid #ced4da;
            background-color: #fff;
            color: #495057;
        }

        .note-btn-group .note-btn:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        .note-btn-group .note-btn.active {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }

        .note-color-palette {
            max-width: 200px;
        }

        .note-editable {
            min-height: 150px;
        }
    </style>
@endpush

@push('scripts')
    <!-- Summernote JS -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

    <script>
        // Wait for both jQuery and Summernote to be loaded
        function initializeSummernote() {
            if (typeof jQuery === 'undefined') {
                console.error('jQuery is not loaded');
                return;
            }

            if (typeof jQuery.fn.summernote === 'undefined') {
                console.error('Summernote is not loaded. Retrying...');
                setTimeout(initializeSummernote, 100);
                return;
            }

            // Initialize Summernote for rich text editing with enhanced toolbar
            // Includes: style, font formatting, font family, font size, colors (text & highlight),
            // lists, tables, media insertion, and view options
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

            jQuery('#description_of_change').summernote({
                height: 200,
                toolbar: enhancedToolbar,
                placeholder: 'Enter description of change...',
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
                        jQuery('#description_of_change').val(jQuery('#description_of_change').summernote(
                            'code'));
                    }
                }
            });

            jQuery('#reason_for_change').summernote({
                height: 200,
                toolbar: enhancedToolbar,
                placeholder: 'Enter reason for change...',
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
                        jQuery('#reason_for_change').val(jQuery('#reason_for_change').summernote('code'));
                    }
                }
            });

            jQuery('#implementation_notes').summernote({
                height: 150,
                toolbar: enhancedToolbar,
                placeholder: 'Any additional notes for implementation...',
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
                        jQuery('#implementation_notes').val(jQuery('#implementation_notes').summernote('code'));
                    }
                }
            });
        }

        // Initialize when document is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeSummernote);
        } else {
            // DOM is already ready
            initializeSummernote();
        }

        // Also try with jQuery ready as backup
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(function() {
                initializeSummernote();
            });
        }

        // Rest of the form logic - wrapped in DOMContentLoaded to ensure DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const changeTypeRadios = document.querySelectorAll('input[name="change_type"]');
            const priceChangeSections = document.getElementById('priceChangeSections');
            const priceChangeTypeSection = document.getElementById('priceChangeTypeSection');
            const tariffSection = document.getElementById('tariffSection');
            const serviceSection = document.getElementById('serviceSection');
            const priceChangeSection = document.getElementById('priceChangeSection');
            const serviceCategorySelect = document.getElementById('service_category_id');
            const servicePricesContainer = document.getElementById('servicePricesContainer');
            const servicePricesRow = document.getElementById('servicePricesRow');

            // Handle change type selection
            if (changeTypeRadios && changeTypeRadios.length > 0) {
                changeTypeRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.value === 'price') {
                            if (priceChangeSections) priceChangeSections.style.display = 'block';
                            if (priceChangeTypeSection) priceChangeTypeSection.style.display =
                                'block';
                        } else {
                            if (priceChangeSections) priceChangeSections.style.display = 'none';
                            if (priceChangeTypeSection) priceChangeTypeSection.style.display =
                                'none';
                            if (tariffSection) tariffSection.style.display = 'none';
                            if (serviceSection) serviceSection.style.display = 'none';
                            if (priceChangeSection) priceChangeSection.style.display = 'none';
                        }
                    });
                });
            }

            // Handle price change type selection
            const priceChangeTypeRadios = document.querySelectorAll('input[name="price_change_type"]');
            if (priceChangeTypeRadios && priceChangeTypeRadios.length > 0) {
                priceChangeTypeRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (tariffSection) tariffSection.style.display = 'none';
                        if (serviceSection) serviceSection.style.display = 'none';
                        if (priceChangeSection) priceChangeSection.style.display = 'none';

                        if (this.value === 'tariff' && tariffSection) {
                            tariffSection.style.display = 'block';
                        } else if (this.value === 'service' && serviceSection) {
                            serviceSection.style.display = 'block';
                        } else if (this.value === 'price_change' && priceChangeSection) {
                            priceChangeSection.style.display = 'block';
                        }
                    });
                });
            }

            // Handle tariff type selection
            const tariffTypeRadios = document.querySelectorAll('input[name="tariff_type"]');
            if (tariffTypeRadios && tariffTypeRadios.length > 0) {
                tariffTypeRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.value === 'new_tariff') {
                            const newTariffFields = document.getElementById('newTariffFields');
                            const newTariffCategoryRow = document.getElementById(
                                'newTariffCategoryRow');
                            const editTariffFields = document.getElementById('editTariffFields');
                            const editTariffCategoryRow = document.getElementById(
                                'editTariffCategoryRow');
                            const editTariffNewNameRow = document.getElementById(
                                'editTariffNewNameRow');

                            if (newTariffFields) newTariffFields.style.display = '';
                            if (newTariffCategoryRow) newTariffCategoryRow.style.display = '';
                            if (editTariffFields) editTariffFields.style.display = 'none';
                            if (editTariffCategoryRow) editTariffCategoryRow.style.display = 'none';
                            if (editTariffNewNameRow) editTariffNewNameRow.style.display = 'none';
                        } else {
                            const newTariffFields = document.getElementById('newTariffFields');
                            const newTariffCategoryRow = document.getElementById(
                                'newTariffCategoryRow');
                            const editTariffFields = document.getElementById('editTariffFields');
                            const editTariffCategoryRow = document.getElementById(
                                'editTariffCategoryRow');
                            const editTariffNewNameRow = document.getElementById(
                                'editTariffNewNameRow');

                            if (newTariffFields) newTariffFields.style.display = 'none';
                            if (newTariffCategoryRow) newTariffCategoryRow.style.display = 'none';
                            if (editTariffFields) editTariffFields.style.display = '';
                            if (editTariffCategoryRow) editTariffCategoryRow.style.display = '';
                            if (editTariffNewNameRow) editTariffNewNameRow.style.display = '';
                        }
                    });
                });
            }

            // Handle service action type selection
            const serviceActionRadios = document.querySelectorAll('input[name="service_action_type"]');
            if (serviceActionRadios && serviceActionRadios.length > 0) {
                serviceActionRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.value === 'new_service') {
                            const newServiceFields = document.getElementById('newServiceFields');
                            const newServiceCategoryRow = document.getElementById(
                                'newServiceCategoryRow');
                            const editServiceFields = document.getElementById('editServiceFields');
                            const editServiceCurrentPriceRow = document.getElementById(
                                'editServiceCurrentPriceRow');
                            const editServiceNewPriceRow = document.getElementById(
                                'editServiceNewPriceRow');

                            if (newServiceFields) newServiceFields.style.display = '';
                            if (newServiceCategoryRow) newServiceCategoryRow.style.display = '';
                            if (editServiceFields) editServiceFields.style.display = 'none';
                            if (editServiceCurrentPriceRow) editServiceCurrentPriceRow.style
                                .display = 'none';
                            if (editServiceNewPriceRow) editServiceNewPriceRow.style.display =
                                'none';
                            if (servicePricesRow) servicePricesRow.style.display = 'none';
                        } else {
                            const newServiceFields = document.getElementById('newServiceFields');
                            const newServiceCategoryRow = document.getElementById(
                                'newServiceCategoryRow');
                            const editServiceFields = document.getElementById('editServiceFields');
                            const editServiceCurrentPriceRow = document.getElementById(
                                'editServiceCurrentPriceRow');
                            const editServiceNewPriceRow = document.getElementById(
                                'editServiceNewPriceRow');

                            if (newServiceFields) newServiceFields.style.display = 'none';
                            if (newServiceCategoryRow) newServiceCategoryRow.style.display = 'none';
                            if (editServiceFields) editServiceFields.style.display = '';
                            if (editServiceCurrentPriceRow) editServiceCurrentPriceRow.style
                                .display = '';
                            if (editServiceNewPriceRow) editServiceNewPriceRow.style.display = '';
                            if (servicePricesRow) servicePricesRow.style.display = 'none';
                        }
                    });
                });
            }

            // Handle service category selection for new service
            if (serviceCategorySelect) {
                serviceCategorySelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const paymentTypes = JSON.parse(selectedOption.getAttribute('data-payment-types') ||
                        '[]');

                    if (paymentTypes.length > 0 && document.querySelector(
                            'input[name="service_action_type"]:checked')?.value === 'new_service') {
                        if (servicePricesContainer) {
                            servicePricesContainer.innerHTML = '';
                            paymentTypes.forEach(type => {
                                const div = document.createElement('div');
                                div.className = 'mb-2';
                                div.innerHTML = `
                                    <label class="form-label">${type} Price</label>
                                    <input type="number" name="service_prices[${type}]" class="form-control" step="0.01" placeholder="0.00">
                                `;
                                servicePricesContainer.appendChild(div);
                            });
                        }
                        if (servicePricesRow) servicePricesRow.style.display = '';
                    } else {
                        if (servicePricesContainer) servicePricesContainer.innerHTML = '';
                        if (servicePricesRow) servicePricesRow.style.display = 'none';
                    }
                });
            }

            // Form submission with loading animation and validation
            const changeRequestForm = document.getElementById('changeRequestForm');
            if (changeRequestForm) {
                changeRequestForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const submitBtn = document.getElementById('submitBtn');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnLoading = submitBtn.querySelector('.btn-loading');
                    const form = this;
                    const changeType = document.querySelector('input[name="change_type"]:checked').value;

                    // Remove required from hidden fields
                    const allSections = [priceChangeSections, tariffSection, serviceSection,
                        priceChangeSection
                    ];
                    allSections.forEach(section => {
                        if (section && section.style.display === 'none') {
                            const hiddenFields = section.querySelectorAll('[required]');
                            hiddenFields.forEach(field => field.removeAttribute('required'));
                        }
                    });

                    // Validate based on change type
                    let isValid = true;
                    let errorMessage = '';

                    if (changeType === 'price') {
                        const priceChangeType = document.querySelector(
                            'input[name="price_change_type"]:checked');
                        if (!priceChangeType) {
                            isValid = false;
                            errorMessage =
                                'Please select a price change type (Tariff, Service, or Price Change).';
                        } else if (priceChangeType.value === 'tariff') {
                            const tariffType = document.querySelector('input[name="tariff_type"]:checked');
                            if (!tariffType) {
                                isValid = false;
                                errorMessage = 'Please select tariff action (New Tariff or Edit Tariff).';
                            }
                        } else if (priceChangeType.value === 'service') {
                            const serviceAction = document.querySelector(
                                'input[name="service_action_type"]:checked');
                            if (!serviceAction) {
                                isValid = false;
                                errorMessage =
                                    'Please select service action (New Service or Edit Service).';
                            }
                        }
                    }

                    if (!isValid) {
                        alert(errorMessage);
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline-block';
                        btnLoading.style.display = 'none';
                        return false;
                    }

                    // Sync Summernote content before validation
                    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.summernote !== 'undefined') {
                        jQuery('#description_of_change').val(jQuery('#description_of_change').summernote(
                            'code'));
                        jQuery('#reason_for_change').val(jQuery('#reason_for_change').summernote('code'));
                        jQuery('#implementation_notes').val(jQuery('#implementation_notes').summernote(
                            'code'));
                    }

                    // Check HTML5 validation
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline-block';
                        btnLoading.style.display = 'none';
                        return false;
                    }

                    // Show loading animation
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoading.style.display = 'inline-block';

                    // Collect service prices for new service
                    if (changeType === 'price' && document.querySelector(
                            'input[name="price_change_type"]:checked')?.value === 'service' &&
                        document.querySelector('input[name="service_action_type"]:checked')?.value ===
                        'new_service') {
                        const servicePrices = {};
                        const priceInputs = servicePricesContainer.querySelectorAll(
                            'input[name^="service_prices"]');
                        priceInputs.forEach(input => {
                            const match = input.name.match(/service_prices\[(.+)\]/);
                            if (match && input.value) {
                                servicePrices[match[1]] = parseFloat(input.value);
                            }
                        });
                        // Add hidden input for service prices
                        let hiddenInput = document.getElementById('service_prices_json');
                        if (!hiddenInput) {
                            hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.id = 'service_prices_json';
                            hiddenInput.name = 'service_prices_json';
                            form.appendChild(hiddenInput);
                        }
                        hiddenInput.value = JSON.stringify(servicePrices);
                    }

                    // Handle tariff category for edit
                    if (changeType === 'price' && document.querySelector(
                            'input[name="price_change_type"]:checked')?.value === 'tariff' &&
                        document.querySelector('input[name="tariff_type"]:checked')?.value === 'edit_tariff'
                    ) {
                        const editCategorySelect = document.getElementById('tariff_category_id_edit');
                        const mainCategorySelect = document.getElementById('tariff_category_id');
                        if (editCategorySelect && editCategorySelect.value) {
                            if (!mainCategorySelect) {
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = 'tariff_category_id';
                                hiddenInput.value = editCategorySelect.value;
                                form.appendChild(hiddenInput);
                            } else {
                                mainCategorySelect.value = editCategorySelect.value;
                            }
                        }
                        const editNameInput = document.getElementById('tariff_name_edit');
                        const mainNameInput = document.getElementById('tariff_name');
                        if (editNameInput && editNameInput.value) {
                            if (!mainNameInput) {
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = 'tariff_name';
                                hiddenInput.value = editNameInput.value;
                                form.appendChild(hiddenInput);
                            } else {
                                mainNameInput.value = editNameInput.value;
                            }
                        }
                    }

                    // Submit the form
                    form.submit();
                });
            }
        }); // End of DOMContentLoaded
    </script>
@endpush
