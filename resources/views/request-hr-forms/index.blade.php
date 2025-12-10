@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex align-items-center justify-content-between">
                            <h3 class="page-title mb-0">HR Forms</h3>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="hr-requests-container">

                                {{-- Bank Details Form --}}
                                <a href="{{ route('bank-details.index') }}" class="hr-card"
                                    aria-label="Open Bank Details Form">
                                    <div class="hr-card__icon">
                                        <i class="fas fa-university" aria-hidden="true"></i>
                                    </div>
                                    <div class="hr-card__content">
                                        <h3 class="hr-card__title">Bank Details Form</h3>
                                    </div>
                                    <span class="hr-card__cta">Open</span>
                                </a>

                                {{-- HESLB Form --}}
                                <a href="{{ route('loan-declarations.index') }}" class="hr-card"
                                    aria-label="Open HESLB Form">
                                    <div class="hr-card__icon">
                                        <i class="fas fa-file-invoice" aria-hidden="true"></i>
                                    </div>
                                    <div class="hr-card__content">
                                        <h3 class="hr-card__title">HESLB Form</h3>
                                    </div>
                                    <span class="hr-card__cta">Open</span>
                                </a>

                                {{-- NHIF Registration --}}
                                <a href="{{ route('nhif_registration.index') }}" class="hr-card"
                                    aria-label="Open NHIF Registration Form">
                                    <div class="hr-card__icon">
                                        <i class="fas fa-id-card" aria-hidden="true"></i>
                                    </div>
                                    <div class="hr-card__content">
                                        <h3 class="hr-card__title">NHIF Registration</h3>
                                    </div>
                                    <span class="hr-card__cta">Open</span>
                                </a>

                                {{-- Clearance Form --}}
                                <a href="{{ route('clearance.index') }}" class="hr-card" aria-label="Open Clearance Form">
                                    <div class="hr-card__icon">
                                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                                    </div>
                                    <div class="hr-card__content">
                                        <h3 class="hr-card__title">Clearance Form</h3>
                                    </div>
                                    <span class="hr-card__cta">Open</span>
                                </a>

                                {{-- Locum Claim --}}
                                <a href="{{ route('locum-requests.index') }}" class="hr-card"
                                    aria-label="Open Locum Claim Form">
                                    <div class="hr-card__icon">
                                        <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                                    </div>
                                    <div class="hr-card__content">
                                        <h3 class="hr-card__title">Locum Claim</h3>
                                    </div>
                                    <span class="hr-card__cta">Open</span>
                                </a>

                                {{-- On-Call Claim --}}
                                <a href="{{ route('oncall_requests.index') }}" class="hr-card"
                                    aria-label="Open On-Call Claim Form">
                                    <div class="hr-card__icon">
                                        <i class="fas fa-phone-alt" aria-hidden="true"></i>
                                    </div>
                                    <div class="hr-card__content">
                                        <h3 class="hr-card__title">On-Call Claim</h3>
                                    </div>
                                    <span class="hr-card__cta">Open</span>
                                </a>

                                {{-- Requisitions -- Only visible to Line Managers and HEC Members --}}
                                @hasanyrole('line-manager|coo|cms|cfo|crhdo|chief_accountant')
                                    <a href="{{ route('requisitions.index') }}" class="hr-card"
                                        aria-label="Open Recruitment Requisition Form">
                                        <div class="hr-card__icon">
                                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                                        </div>
                                        <div class="hr-card__content">
                                            <h3 class="hr-card__title">Recruitment Requisition</h3>
                                        </div>
                                        <span class="hr-card__cta">Open</span>
                                    </a>
                                @endhasanyrole

                                {{-- Other Documents --}}
                                <a href="{{ route('HrDocuments.index') }}" class="hr-card"
                                    aria-label="Open Other Documents Form">
                                    <div class="hr-card__icon">
                                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                                    </div>
                                    <div class="hr-card__content">
                                        <h3 class="hr-card__title">Other Documents</h3>
                                    </div>
                                    <span class="hr-card__cta">Open</span>
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> {{-- /.content --}}
    </div> {{-- /.page-wrapper --}}

    <style>
        /* Palette */
        :root {
            --brand: #61ce70;
            --brand-600: #4fbb5d;
            --text: #2f2f2f;
            --muted: #6c757d;
            --card-border: #e9ecef;
            --card-bg: #ffffff;
            --focus: 0 0 0 0.25rem rgba(97, 206, 112, 0.35);
        }

        .hr-requests-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        /* Fully clickable card */
        .hr-card {
            display: grid;
            grid-template-columns: 56px 1fr auto;
            align-items: center;
            gap: 14px;
            padding: 18px 16px;
            text-decoration: none;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(16, 24, 40, 0.04);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            color: inherit;
        }

        .hr-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08);
            border-color: rgba(97, 206, 112, 0.45);
        }

        .hr-card:focus,
        .hr-card:focus-visible {
            outline: none;
            box-shadow: var(--focus);
            border-color: var(--brand);
        }

        .hr-card__icon i {
            font-size: 1.75rem;
            color: var(--brand);
        }

        .hr-card__content {
            min-width: 0;
        }

        .hr-card__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 400;
            color: var(--text);
            line-height: 1.25;
        }

        .hr-card__subtitle {
            margin: 2px 0 0 0;
            font-size: .9rem;
            color: var(--muted);
            line-height: 1.35;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .hr-card__cta {
            font-weight: 600;
            color: var(--brand);
            border: 1px solid rgba(97, 206, 112, 0.35);
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .85rem;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
            white-space: nowrap;
        }

        .hr-card:hover .hr-card__cta {
            background: var(--brand);
            color: #fff;
            border-color: var(--brand);
        }

        /* Small tweaks on container/card */
        .card-body {
            padding: 20px;
        }

        @media (min-width: 992px) {
            .card-body {
                padding: 24px;
            }
        }
    </style>
@endsection
