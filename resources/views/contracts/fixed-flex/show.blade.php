// resources/views/contracts/fixed-flex/show.blade.php
@extends('layouts.template')

@section('content')
    <div class="container">
        <h1>Contract Details</h1>

        <div class="card">
            <div class="card-header">
                Contract for {{ $contract->user->fullName() }}
            </div>
            <div class="card-body">
                <pre>{{ $document }}</pre>
            </div>
            <div class="card-footer">
                <a href="{{ route('contracts.fixed-flex.download', $contract) }}" class="btn btn-primary">
                    Download as PDF
                </a>
            </div>
        </div>
    </div>
@endsection
