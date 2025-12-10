<!DOCTYPE html>
<html>
@include('includes.head')

<body class="hold-transition skin-blue sidebar-mini">
    {{-- <div class="main-wrapper slide-nav"> --}}
    <div class="main-wrapper">
    @include('sweetalert::alert')
    @include('partials.header2')
    {{-- @include('partials.sidebar') --}}

    <div class="content-wrapper">
        @yield('breadcrumb')

        <section class="content">
            @yield('content')
        </section>

    </div>
    @include('partials.footer2')
    </div>

    @include('includes.scripts')

</body>

</html>
