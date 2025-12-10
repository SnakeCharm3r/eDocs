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
                            <h3 class="page-title mb-0">IT Requests</h3>
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
                            <div class="it-requests-container">

                                {{-- ICT Access Form --}}
                                <a href="{{ route('form.index') }}" class="it-card" aria-label="Open ICT Access Form">
                                    <div class="it-card__icon">
                                        <i class="fas fa-desktop" aria-hidden="true"></i>
                                    </div>
                                    <div class="it-card__content">
                                        <h3 class="it-card__title">ICT Access</h3>
                                        {{-- <p class="it-card__subtitle">Request user accounts, email, network & app access.</p> --}}
                                    </div>
                                    <span class="it-card__cta">Open</span>
                                </a>

                                {{-- Change Request --}}
                                <a href="{{ route('change_request.create') }}" class="it-card"
                                    aria-label="Open Change Request Form">
                                    <div class="it-card__icon">
                                        <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                    </div>
                                    <div class="it-card__content">
                                        <h3 class="it-card__title">Change Request</h3>
                                        {{-- <p class="it-card__subtitle">Propose system, config, or process changes.</p> --}}
                                    </div>
                                    <span class="it-card__cta">Open</span>
                                </a>

                                {{-- ID Card Request --}}
                                <a href="{{ route('IDCard.create') }}" class="it-card"
                                    aria-label="Open ID Card Request Form">
                                    <div class="it-card__icon">
                                        <i class="fas fa-id-badge" aria-hidden="true"></i>
                                    </div>
                                    <div class="it-card__content">
                                        <h3 class="it-card__title">ID Card Request</h3>
                                        {{-- <p class="it-card__subtitle">Request a new, replacement, or renewal ID card.</p> --}}
                                    </div>
                                    <span class="it-card__cta">Open</span>
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

        .it-requests-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        /* Fully clickable card */
        .it-card {
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

        .it-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08);
            border-color: rgba(97, 206, 112, 0.45);
        }

        .it-card:focus,
        .it-card:focus-visible {
            outline: none;
            box-shadow: var(--focus);
            border-color: var(--brand);
        }

        .it-card__icon i {
            font-size: 1.75rem;
            color: var(--brand);
        }

        .it-card__content {
            min-width: 0;
        }

        .it-card__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 400;
            color: var(--text);
            line-height: 1.25;
        }

        .it-card__subtitle {
            margin: 2px 0 0 0;
            font-size: .9rem;
            color: var(--muted);
            line-height: 1.35;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .it-card__cta {
            font-weight: 600;
            color: var(--brand);
            border: 1px solid rgba(97, 206, 112, 0.35);
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .85rem;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
            white-space: nowrap;
        }

        .it-card:hover .it-card__cta {
            background: var(--brand);
            color: #fff;
            border-color: var(--brand);
        }

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
