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

    <script src="{{ asset('assets/plugins/sweetalert/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>

    {{-- <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script> --}}
</body>

</html>
