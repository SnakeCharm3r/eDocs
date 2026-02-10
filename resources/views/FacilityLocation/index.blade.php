@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Facilities Section</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class ="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            
            <div class="it-requests-container">
                <div class="it-request-card" role="region" aria-label="Assets">
                    <i class="fa-solid fa-compass-drafting" aria-hidden="true"></i>
                    <h3>Assets</h3>
                    <a href="{{ route('facilityAssets.index') }}" title="View Registered Assets">Registered Assets</a><br>
                </div>
                <div class="it-request-card" role="region" aria-label="Facility Locations">
                    <i class="fa-solid fa-building" aria-hidden="true"></i>
                    <h3>Facility Locations</h3>
                    <a href="{{ route('facilityLocations.index') }}" title="View Facility Locations">View Locations</a>
                </div>
            </div>
            </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .it-requests-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .it-request-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.2s;
            border: 1px solid #e9ecef;
        }

        .it-request-card:hover {
            transform: translateY(-5px);
        }

        .it-request-card i {
            font-size: 2.5rem;
            color: #61ce70;
            margin-bottom: 10px;
        }

        .it-request-card h3 {
            font-size: 1.2rem;
            margin: 10px 0;
            color: #333;
        }

        .it-request-card a {
            text-decoration: none;
            color: #61ce70;
            font-weight: bold;
        }

        .it-request-card a:hover {
            color: #4caf50;
        }
    </style>
@endsection
