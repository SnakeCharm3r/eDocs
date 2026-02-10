<!DOCTYPE html>
<html lang="en">

@include('includes.head')
<body class="hold-transition skin-blue sidebar-mini" style="background-color: #eff8f3; overflow-x: hidden;">


    @include('partials.header2')

    <div class="content-wrapper">
        @yield('breadcrumb')

        <section class="content">
            @yield('content')
        </section>
    </div>

    <div class="footer fixed-bottom">
        @include('partials.footer2')
    </div>

    @include('includes.scripts')
</body>

</html>
