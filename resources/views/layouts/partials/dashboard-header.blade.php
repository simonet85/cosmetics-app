<header class="dashboard-header bg-white border-bottom position-fixed top-0 start-0 end-0" style="height: 70px; z-index: 1001;">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between h-100 px-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('admin.dashboard') }}" class="text-decoration-none">
                    <img src="{{ asset('images/others/logo.png') }}" alt="{{ config('app.name') }}" height="30">
                </a>
                <span class="ms-3 text-muted">Admin Panel</span>
            </div>

            <div class="d-flex align-items-center gap-4">
                <a href="{{ route('home') }}" class="text-decoration-none text-body" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i> View Site
                </a>

                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="{{ auth()->user()->avatar ?? asset('images/avatar-default.png') }}" alt="Avatar" class="rounded-circle me-2" width="35" height="35">
                        <span>{{ auth()->user()->full_name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('account.profile') }}">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>
