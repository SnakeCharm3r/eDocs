@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @php
            $resolveSignatureSrc = static function (?string $signature): ?string {
                if (!$signature) {
                    return null;
                }

                if (str_starts_with($signature, 'data:image')) {
                    return $signature;
                }

                if (file_exists(storage_path('app/public/' . $signature))) {
                    return asset('storage/' . $signature);
                }

                return 'data:image/png;base64,' . $signature;
            };

            $cooSignatureSrc = $resolveSignatureSrc($certificate->coo_signature_path);
            $approverSignatureSrc = $resolveSignatureSrc($certificate->approver_signature_path);
            $renderTemplateText = static function (string $text) use ($certificate): string {
                return strtr($text, [
                    '{name}' => '<strong>' . e(trim(($certificate->staff?->fname ?? '') . ' ' . ($certificate->staff?->lname ?? ''))) . '</strong>',
                    '{staff_code}' => (string) ($certificate->staff?->ccbrt_code ?? ''),
                    '{position}' => (string) ($certificate->position_held ?? ''),
                    '{department}' => (string) ($certificate->department ?? ''),
                    '{start_date}' => (string) ($certificate->date_of_joining?->format('jS F Y') ?? ''),
                    '{end_date}' => (string) ($certificate->last_working_day?->format('jS F Y') ?? ''),
                    '{issue_date}' => (string) ($certificate->issue_date?->format('jS F Y') ?? ''),
                ]);
            };
            $signerName  = $templateSettings['signer_name']  ?: ($coo?->fname ? trim($coo->fname . ' ' . $coo->lname) : 'Authorised Signatory');
            $signerTitle = $templateSettings['signer_title'] ?: ($coo?->jobTitle?->job_title ?? 'Chief Operating Officer');
        @endphp
        <style>
            .certificate-show-header {
                max-width: 860px;
                margin: 0 auto 1rem;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 1rem;
            }

            .certificate-show-heading-copy {
                min-width: 0;
            }

            .certificate-show-page-title {
                margin: 0;
                font-size: 1.2rem;
                font-weight: 700;
                color: #1a2f21;
                line-height: 1.2;
            }

            .certificate-show-page-meta {
                display: inline-flex;
                align-items: center;
                margin-top: .35rem;
                padding: .2rem .55rem;
                border-radius: 999px;
                background: #eef7ef;
                color: #2f6b3f;
                font-size: .76rem;
                font-weight: 600;
                letter-spacing: .04em;
            }

            .certificate-show-sheet {
                max-width: 860px;
                margin: 0 auto;
                background: #fff;
                border: 3px solid #2f7d3a;
                box-shadow: 0 10px 30px rgba(19, 41, 28, 0.08);
            }

            .certificate-show-body {
                padding: 1.75rem 2.85rem 3.5rem;
            }

            .certificate-show-heading {
                text-align: center;
                margin-bottom: 2.9rem;
            }

            .certificate-show-heading img {
                height: 88px;
                width: auto;
                object-fit: contain;
                margin-bottom: 1rem;
            }

            .certificate-show-strip-wrap {
                border: 1px solid #b6c5b6;
                padding: 4px;
                margin-bottom: 3rem;
            }

            .certificate-show-strip {
                height: 22px;
                display: grid;
                grid-template-columns: 1.4fr .38fr 2.5fr 1.9fr .7fr 1.45fr .75fr .28fr;
            }

            .certificate-show-strip span:nth-child(1) { background: #0f6f2f; }
            .certificate-show-strip span:nth-child(2) { background: #1d7d35; }
            .certificate-show-strip span:nth-child(3) { background: #3d8f42; }
            .certificate-show-strip span:nth-child(4) { background: #79bf3c; }
            .certificate-show-strip span:nth-child(5) { background: #9ec347; }
            .certificate-show-strip span:nth-child(6) { background: #b7c945; }
            .certificate-show-strip span:nth-child(7) { background: #b8d83e; }
            .certificate-show-strip span:nth-child(8) { background: #8bc34a; }

            .certificate-show-title,
            .certificate-show-subtitle {
                text-align: center;
                font-weight: 700;
                text-transform: uppercase;
                color: #151515;
            }

            .certificate-show-title {
                font-size: 1.05rem;
                margin-bottom: .45rem;
            }

            .certificate-show-subtitle {
                font-size: .94rem;
                margin-bottom: 1rem;
            }

            .certificate-show-copy,
            .certificate-show-details,
            .certificate-show-signoff,
            .certificate-show-date-row {
                font-size: .96rem;
                color: #141414;
                line-height: 1.65;
            }

            .certificate-show-copy {
                margin-bottom: .2rem;
            }

            .certificate-show-details {
                margin: 0 0 1.5rem;
                padding: 0;
                list-style: none;
            }

            .certificate-show-details li {
                margin-bottom: .05rem;
            }

            .certificate-show-signoff-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                gap: 2rem;
                margin-top: 2.2rem;
            }

            .certificate-show-signoff {
                width: 310px;
            }

            .certificate-show-signature {
                min-height: 52px;
                margin-bottom: .25rem;
                display: flex;
                align-items: end;
            }

            .certificate-show-signature img {
                max-height: 52px;
                max-width: 220px;
                object-fit: contain;
            }

            .certificate-show-line {
                width: 120px;
                border-top: 2px dotted #222;
                margin-bottom: .7rem;
            }

            .certificate-show-signer {
                font-weight: 700;
                line-height: 1.4;
            }

            .certificate-show-role {
                line-height: 1.4;
            }

            .certificate-show-date-row {
                margin-bottom: .2rem;
                white-space: nowrap;
            }

            .certificate-show-actions {
                max-width: 860px;
                margin: 1rem auto 0;
                display: flex;
                justify-content: flex-end;
                gap: .5rem;
                flex-wrap: wrap;
            }

            .certificate-show-actions .btn {
                padding: .38rem .75rem;
                font-size: .82rem;
                border-radius: .45rem;
            }

            @media (max-width: 767.98px) {
                .certificate-show-header {
                    display: block;
                }

                .certificate-show-body {
                    padding: 1.2rem 1rem 2rem;
                }

                .certificate-show-signoff-row {
                    display: block;
                }

                .certificate-show-date-row {
                    margin-top: 1.25rem;
                }

                .certificate-show-actions {
                    justify-content: flex-start;
                }
            }
        </style>
        <div class="certificate-show-header">
            <div class="certificate-show-heading-copy">
                <h3 class="certificate-show-page-title">Certificate of Service</h3>
                <div class="certificate-show-page-meta">{{ $certificate->certificate_number }}</div>
                @if ($certificate->isInitialized())
                    <div class="mt-1" style="font-size:.76rem;color:#155724;">
                        <i class="fas fa-envelope-open-text me-1"></i>
                        COO notified on {{ $certificate->initialized_at->format('d M Y, H:i') }}
                        @if ($certificate->initializer)
                            by {{ trim(($certificate->initializer->fname ?? '') . ' ' . ($certificate->initializer->lname ?? '')) }}
                        @endif
                    </div>
                @endif
            </div>
            @if ($isHR && !$certificate->isInitialized())
                <div>
                    <form method="POST" action="{{ route('certificate-of-service.initialize', $certificate) }}"
                          onsubmit="return confirm('Send this Certificate of Service to the COO for review?\n\nAn email notification will be sent to the COO.')">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" style="font-size:.84rem;">
                            <i class="fas fa-paper-plane me-1"></i> Initialize Certificate of Service
                        </button>
                    </form>
                </div>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="certificate-show-sheet">
            <div class="certificate-show-body">
                <div class="certificate-show-heading">
                    <img src="{{ $certificateLogoSrc }}" alt="Certificate Logo" onerror="this.style.display='none'">
                    <div class="certificate-show-strip-wrap">
                        <div class="certificate-show-strip">
                            <span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
                        </div>
                    </div>
                    <div class="certificate-show-title">{{ $templateSettings['title'] }}</div>
                    <div class="certificate-show-subtitle">{{ $templateSettings['subtitle'] }}</div>
                </div>

                <p class="certificate-show-copy">
                    {!! $renderTemplateText($templateSettings['intro_text']) !!}
                </p>

                <ul class="certificate-show-details">
                    <li>{{ $templateSettings['start_date_label'] }}: {{ $certificate->date_of_joining?->format('jS F Y') }}</li>
                    <li>{{ $templateSettings['position_label'] }}: {{ $certificate->position_held }}</li>
                    <li>{{ $templateSettings['end_date_label'] }}: {{ $certificate->last_working_day?->format('jS F Y') }}</li>
                </ul>

                @if($certificate->remarks)
                    <p class="certificate-show-copy" style="margin-bottom: 1rem;">
                        {{ $certificate->remarks }}
                    </p>
                @endif

                <p class="certificate-show-copy mb-0">
                    {{ $renderTemplateText($templateSettings['closing_text']) }}
                </p>

                @php $isStampCenter = !empty($certificateStampSrc) && ($stampPosition ?? 'right') === 'center'; @endphp

                @if($isStampCenter)
                {{-- Stamp beside signature, date far right --}}
                <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-top:2.2rem;">
                    <div style="display:flex;align-items:flex-start;gap:1.5rem;">
                        <div style="width:auto;">
                            <div class="certificate-show-signature">
                                @if($cooSignatureSrc)
                                    <img src="{{ $cooSignatureSrc }}" alt="COO Signature" onerror="this.style.display='none'">
                                @elseif($approverSignatureSrc)
                                    <img src="{{ $approverSignatureSrc }}" alt="Approver Signature" onerror="this.style.display='none'">
                                @endif
                            </div>
                            <div class="certificate-show-line"></div>
                            <div class="certificate-show-signer">{{ $signerName }}</div>
                            <div class="certificate-show-role">{{ $signerTitle }}</div>
                        </div>
                        <img src="{{ $certificateStampSrc }}" alt="Official Stamp" style="max-height:85px;max-width:90px;object-fit:contain;margin-top:-.5rem;" onerror="this.style.display='none'">
                    </div>
                    <div class="certificate-show-date-row">Date of Issue: {{ $certificate->issue_date?->format('jS F Y') }}</div>
                </div>
                @else
                {{-- 2-column: signature | stamp+date on right --}}
                <div class="certificate-show-signoff-row">
                    <div class="certificate-show-signoff">
                        <div class="certificate-show-signature">
                            @if($cooSignatureSrc)
                                <img src="{{ $cooSignatureSrc }}" alt="COO Signature" onerror="this.style.display='none'">
                            @elseif($approverSignatureSrc)
                                <img src="{{ $approverSignatureSrc }}" alt="Approver Signature" onerror="this.style.display='none'">
                            @endif
                        </div>
                        <div class="certificate-show-line"></div>
                        <div class="certificate-show-signer">{{ $signerName }}</div>
                        <div class="certificate-show-role">{{ $signerTitle }}</div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.5rem;">
                        @if(!empty($certificateStampSrc))
                            <img src="{{ $certificateStampSrc }}" alt="Official Stamp" style="max-height:90px;max-width:120px;object-fit:contain;" onerror="this.style.display='none'">
                        @endif
                        <div class="certificate-show-date-row">Date of Issue: {{ $certificate->issue_date?->format('jS F Y') }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="certificate-show-actions">
            <a href="{{ route('certificate-of-service.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <a href="{{ route('certificate-of-service.download', $certificate) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-pdf me-1"></i> Download PDF
            </a>
            @if ($canManage)
                <form method="POST" action="{{ route('certificate-of-service.destroy', $certificate) }}"
                      onsubmit="return confirm('Delete this certificate? This cannot be undone.')" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
