<div class="header">

    <!-- Logo Area -->
    <div class="header-left">
        <a href="#" class="logo">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo">
        </a>
        <a href="#" class="logo logo-small">
            <img src="{{ asset('assets/img/logo-small.png') }}" alt="Logo" width="30" height="30">
        </a>
    </div>

    <!-- Sidebar Toggle -->
    <div class="menu-toggle">
        <a href="javascript:void(0);" id="toggle_btn">
            <i class="fas fa-bars" style="background-color: #61ce70; padding: 12px; border-radius: 5px;"></i>
        </a>
    </div>

    <!-- Mobile Menu Toggle -->
    <a class="mobile_btn" id="mobile_btn">
        <i class="fas fa-bars" style="background-color: #61ce70; padding: 12px; border-radius: 5px;"></i>
    </a>

    <!-- Right Side Menu -->
    <ul class="nav user-menu">

        <!-- Zoom Screen Icon -->
        <li class="nav-item zoom-screen me-2">
            <a href="#" class="nav-link header-nav-list">
                <img src="{{ asset('assets/img/icons/header-icon-04.svg') }}" alt="">
            </a>
        </li>



        <!-- BioTime Details Button -->
        @if (Auth::check())
            <li class="nav-item me-2">
                <a href="{{ route('biotime.details') }}" class="dropdown-toggle nav-link" title="BioTime Details">
                    <i class="fas fa-clock"style="color: #61ce70;"></i>

                </a>
            </li>
        @endif
        {{--        @if (Auth::check()) --}}
        {{--            <li class="nav-item me-2"> --}}
        {{--                <a href="#" class="dropdown-toggle nav-link" title="BioTime Details"> --}}
        {{--                    <i class="fas fa-clock"style="color: #61ce70;"></i> --}}

        {{--                </a> --}}
        {{--            </li> --}}
        {{--        @endif --}}

        <!-- ⏰ Digital Clock Display -->
        <li class="nav-item d-flex align-items-center me-3">
            <span id="header-date" class="text-muted fs-6"></span>
        </li>


        <script>
            function updateDate() {
                const dateElement = document.getElementById('header-date');

                const now = new Date();

                // Format the date
                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                const day = now.getDate();
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                dateElement.textContent = `${month} ${day}, ${year}`;
            }

            updateDate(); // Set the date immediately
        </script>



        <!-- 👤 User Profile Dropdown -->
        <li class="nav-item dropdown has-arrow new-user-menus">
            <a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                <span class="user-img">
                    @if (Auth::check() && auth()->user() && auth()->user()->profile_picture)
                        <img class="rounded-circle" src="{{ asset('storage/' . auth()->user()->profile_picture) }}"
                            width="31" alt="User">
                    @else
                        <img class="rounded-circle" src="{{ asset('assets/img/icon.png') }}" alt="Default User Icon"
                            style="max-width: 160px; height: 38px; padding: 1px; object-fit: cover;">
                    @endif
                    <div class="user-text">
                        @if (Auth::check() && auth()->user())
                            <h6>{{ Auth::user()->fname }} {{ Auth::user()->lname }}</h6>
                            <p class="text-muted mb-0">{{ Auth::user()->jobTitle?->job_title ?? 'No job title' }}</p>
                        @else
                            <h6>Guest</h6>
                            <p class="text-muted mb-0">Not logged in</p>
                        @endif
                    </div>
                </span>
            </a>
            <div class="dropdown-menu">
                @if (Auth::check())
                    <a class="dropdown-item" href="{{ route('profile.index') }}">My Profile</a>
                    <a class="dropdown-item" href="{{ route('logout') }}">Logout</a>
                @else
                    <a class="dropdown-item" href="{{ route('login') }}">Login</a>
                @endif
            </div>
        </li>

    </ul>
</div>
{{-- <script>
    function updateHeaderClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timeString = `${hours}:${minutes}:${seconds}`;
        document.getElementById('header-clock').textContent = timeString;
    }

    setInterval(updateHeaderClock, 1000);
    updateHeaderClock(); // Run once immediately
</script> --}}
