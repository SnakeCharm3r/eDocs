<!DOCTYPE html>
<html>
@include('includes.head')

<body class="hold-transition skin-blue sidebar-mini">
    <div class="main-wrapper">
        @include('sweetalert::alert')
        @include('partials.header')
        @include('partials.sidebar')

        <div class="content-wrapper">
            @yield('breadcrumb')

            <section class="content">
                @yield('content')
            </section>
        </div>

        @include('partials.footer')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>

    {{-- <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script> --}}
</body>

</html>
