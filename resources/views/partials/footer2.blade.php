<footer>
    <p>
        <span style="text-transform: capitalize;">Copyright © {{ date('Y') }}. CCBRT. [Version No. 1.0]</span>
        {{-- <span style="text-transform: capitalize;"><a href="{{ url('/privacy-policy') }}" target="_blank">Privacy Policy</a> --}}
        </span>
</footer>

<style>
    .content-wrapper {
        padding-bottom: 50px;
        min-height: 50vh;
    }

    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        background-color: #f8f9fa;
    }

    @media (max-width: 768px) {
        .footer {
            padding: 10px 0;
        }
    }
</style>
