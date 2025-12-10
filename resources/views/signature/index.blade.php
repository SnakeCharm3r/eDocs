@extends('layouts.template2')
@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Signature</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="container mt-5">
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="my-4" style="margin: 0; font-size: 18px;">Step {{ session('current_step') }}: Signature</h2>
                <div class="progress flex-grow-1 ml-3" style="max-width: 70%;">
                    <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar"
                        aria-valuenow="{{ (session('current_step') / 7) * 100 }}" aria-valuemin="0" aria-valuemax="100"
                        style="width: {{ (session('current_step') / 7) * 100 }}%;">
                        Step {{ session('current_step') }} of 7
                    </div>
                </div>
            </div>
            {{-- <small class="form-text text-muted">Please provide your signature using the signature pad below. This signature will be used for official documents.</small> --}}
        </div>

        @include('sweetalert::alert')

        <!-- Success/Error Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="content container-fluid">
            <div class="content-wrapper">
                <style>
                    .content-wrapper {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        padding: 20px;
                    }

                    .signature-container {
                        display: flex;
                        justify-content: space-between;
                        gap: 30px;
                        max-width: 900px;
                        width: 100%;
                        padding: 30px;
                        background-color: #fff;
                        border-radius: 10px;
                        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
                        margin-top: 20px;
                    }

                    .signature-section {
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 15px;
                        padding: 20px;
                    }

                    .signature-section.full-width {
                        width: 100%;
                    }

                    #signature-pad {
                        border: 2px solid #007A33;
                        border-radius: 8px;
                        width: 100%;
                        height: 150px;
                        background-color: #fff;
                        cursor: crosshair;
                        touch-action: none;
                    }

                    #signature-pad:focus {
                        outline: none;
                        border-color: #005a25;
                        box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
                    }

                    .signature-actions {
                        display: flex;
                        justify-content: center;
                        gap: 15px;
                        margin-top: 15px;
                        width: 100%;
                    }

                    .signature-actions button {
                        min-width: 120px;
                        padding: 10px 20px;
                        font-size: 14px;
                        font-weight: 500;
                    }

                    .signature-title {
                        font-size: 18px;
                        font-weight: bold;
                        margin-bottom: 10px;
                        color: #333;
                        text-align: center;
                    }

                    .signature-note {
                        text-align: center;
                        font-size: 14px;
                        color: #666;
                        margin-bottom: 15px;
                        line-height: 1.6;
                    }

                    .saved-signature-container {
                        border: 2px dashed #007A33;
                        border-radius: 8px;
                        padding: 20px;
                        background-color: #f8f9fa;
                        width: 100%;
                        text-align: center;
                    }

                    .saved-signature-container img {
                        max-width: 100%;
                        height: auto;
                        border: 1px solid #ddd;
                        border-radius: 5px;
                        padding: 10px;
                        background-color: #fff;
                    }

                    .button-container {
                        display: flex;
                        justify-content: space-between;
                        width: 100%;
                        margin-top: 20px;
                        gap: 15px;
                    }

                    .button-container .btn {
                        flex: 1;
                        max-width: 200px;
                    }

                    .signature-instructions {
                        background-color: #e7f3ed;
                        border-left: 4px solid #007A33;
                        padding: 15px;
                        border-radius: 5px;
                        margin-bottom: 20px;
                        width: 100%;
                    }

                    .signature-instructions h6 {
                        color: #007A33;
                        font-weight: 600;
                        margin-bottom: 10px;
                    }

                    .signature-instructions ul {
                        margin-bottom: 0;
                        padding-left: 20px;
                    }

                    .signature-instructions li {
                        margin-bottom: 5px;
                        color: #555;
                    }

                    @media (max-width: 768px) {
                        .signature-container {
                            flex-direction: column;
                            gap: 20px;
                        }

                        .signature-section {
                            width: 100%;
                        }
                    }

                    .btn-loading {
                        display: none;
                    }

                    .btn-saving .btn-text {
                        display: none;
                    }

                    .btn-saving .btn-loading {
                        display: inline-block;
                    }
                </style>

                <div class="signature-container">
                    @if ($user->signature)
                        <!-- Saved Signature Section -->
                        <div class="signature-section">
                            <h2 class="signature-title">Your Saved Signature</h2>
                            <p class="signature-note">You have already saved a signature. You can proceed to the next step
                                or overwrite it with a new signature.</p>

                            <div class="saved-signature-container">
                                <img src="data:image/png;base64,{{ $user->signature }}" alt="User Signature"
                                    style="max-width: 100%; height: auto; max-height: 150px;">
                            </div>

                            <div class="button-container">
                                <a href="{{ route('conflict-interest.viewit') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </a>
                                <a href="{{ route('profile.confirm') }}" class="btn btn-primary"
                                    style="background-color: #007A33; border-color: #007A33;">
                                    <i class="fas fa-check"></i> Proceed
                                </a>
                            </div>
                        </div>

                        <!-- Overwrite Signature Section -->
                        <div class="signature-section">
                            <h2 class="signature-title">Overwrite Signature</h2>
                            <p class="signature-note">Use the signature pad below to provide a new signature.</p>

                            <div class="signature-instructions">
                                <h6><i class="fas fa-info-circle"></i> Instructions:</h6>
                                <ul>
                                    <li>Sign using your mouse or touch screen</li>
                                    <li>Click "Clear" to start over</li>
                                    <li>Click "Save" when you're satisfied with your signature</li>
                                </ul>
                            </div>

                            <canvas id="signature-pad"></canvas>
                            <div class="signature-actions">
                                <button type="button" id="clear" class="btn btn-outline-danger">
                                    <i class="fas fa-eraser"></i> Clear
                                </button>
                                <button type="button" id="save" class="btn btn-primary"
                                    style="background-color: #007A33; border-color: #007A33;">
                                    <span class="btn-text">
                                        <i class="fas fa-save"></i> Save
                                    </span>
                                    <span class="btn-loading">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        Saving...
                                    </span>
                                </button>
                            </div>

                            <!-- Form for Saving Signature -->
                            <form id="signature-form" action="{{ route('signature.store') }}" method="POST">
                                @csrf
                                <input type="hidden" id="signature-input" name="signature">
                            </form>
                        </div>
                    @else
                        <!-- New Signature Section -->
                        <div class="signature-section full-width">
                            <h2 class="signature-title">Please Sign Below</h2>
                            <p class="signature-note">Use the signature pad below to provide your signature. This will be
                                used for official documents.</p>

                            <div class="signature-instructions">
                                <h6><i class="fas fa-info-circle"></i> Instructions:</h6>
                                <ul>
                                    <li>Sign using your mouse or touch screen</li>
                                    <li>Click "Clear" to start over</li>
                                    <li>Click "Save" when you're satisfied with your signature</li>
                                </ul>
                            </div>

                            <canvas id="signature-pad"></canvas>
                            <div class="signature-actions">
                                <button type="button" id="clear" class="btn btn-outline-danger">
                                    <i class="fas fa-eraser"></i> Clear
                                </button>
                                <button type="button" id="save" class="btn btn-primary"
                                    style="background-color: #007A33; border-color: #007A33;">
                                    <span class="btn-text">
                                        <i class="fas fa-save"></i> Save Signature
                                    </span>
                                    <span class="btn-loading">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        Saving...
                                    </span>
                                </button>
                            </div>

                            <!-- Form for Saving Signature -->
                            <form id="signature-form" action="{{ route('signature.store') }}" method="POST">
                                @csrf
                                <input type="hidden" id="signature-input" name="signature">
                            </form>

                            <div class="button-container mt-3">
                                <a href="{{ route('conflict-interest.viewit') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
            <script>
                const canvas = document.getElementById('signature-pad');
                if (canvas) {
                    const signaturePad = new SignaturePad(canvas, {
                        penColor: '#0000FF', // Blue color for real signature
                        backgroundColor: '#ffffff',
                        minWidth: 1.5,
                        maxWidth: 3,
                        throttle: 16,
                        minDistance: 5
                    });

                    const clearButton = document.getElementById('clear');
                    const saveButton = document.getElementById('save');
                    const signatureForm = document.getElementById('signature-form');
                    const signatureInput = document.getElementById('signature-input');

                    function resizeCanvas() {
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        const rect = canvas.getBoundingClientRect();

                        canvas.width = rect.width * ratio;
                        canvas.height = rect.height * ratio;
                        canvas.getContext('2d').scale(ratio, ratio);

                        // Clear and redraw if there's existing signature
                        if (!signaturePad.isEmpty()) {
                            const data = signaturePad.toData();
                            signaturePad.clear();
                            signaturePad.fromData(data);
                        }
                    }

                    // Initial resize
                    resizeCanvas();
                    window.addEventListener('resize', resizeCanvas);

                    // Clear button
                    if (clearButton) {
                        clearButton.addEventListener('click', () => {
                            signaturePad.clear();
                        });
                    }

                    // Save button
                    if (saveButton) {
                        saveButton.addEventListener('click', () => {
                            if (signaturePad.isEmpty()) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Signature Required',
                                    text: 'Please provide a signature before saving.',
                                    confirmButtonColor: '#007A33'
                                });
                                return;
                            }

                            // Show loading state
                            saveButton.classList.add('btn-saving');
                            saveButton.disabled = true;

                            // Get signature data
                            signatureInput.value = signaturePad.toDataURL('image/png');

                            // Submit form
                            signatureForm.submit();
                        });
                    }

                    // Prevent scrolling when drawing on touch devices
                    canvas.addEventListener('touchmove', function(e) {
                        e.preventDefault();
                    }, {
                        passive: false
                    });
                }
            </script>
        </div>
    </div>
@endsection
