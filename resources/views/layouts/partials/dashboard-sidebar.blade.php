<aside class="dashboard-sidebar bg-white border-end position-fixed top-0 start-0 h-100" style="width: 250px; z-index: 1000; margin-top: 70px;">
    <div class="p-4">
        <nav class="nav flex-column">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>

            <hr class="my-3">

            <h6 class="text-uppercase text-muted small mb-3">Products</h6>
            <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam me-2"></i> All Products
            </a>
            <a href="{{ route('admin.products.create') }}" class="nav-link">
                <i class="bi bi-plus-circle me-2"></i> Add Product
            </a>
            <a href="{{ route('admin.categories.index') }}" class="nav-link">
                <i class="bi bi-folder me-2"></i> Categories
            </a>

            <hr class="my-3">

            <h6 class="text-uppercase text-muted small mb-3">Orders</h6>
            <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check me-2"></i> All Orders
            </a>

            <hr class="my-3">

            <h6 class="text-uppercase text-muted small mb-3">Customers</h6>
            <a href="{{ route('admin.customers.index') }}" class="nav-link">
                <i class="bi bi-people me-2"></i> All Customers
            </a>
            <a href="{{ route('admin.reviews.index') }}" class="nav-link">
                <i class="bi bi-star me-2"></i> Reviews
            </a>

            <hr class="my-3">

            <h6 class="text-uppercase text-muted small mb-3">Marketing</h6>
            <a href="{{ route('admin.coupons.index') }}" class="nav-link">
                <i class="bi bi-tag me-2"></i> Coupons
            </a>
            <a href="{{ route('admin.banners.index') }}" class="nav-link">
                <i class="bi bi-image me-2"></i> Banners
            </a>
            <a href="{{ route('admin.newsletter.index') }}" class="nav-link">
                <i class="bi bi-envelope me-2"></i> Newsletter
            </a>

            <hr class="my-3">

            <h6 class="text-uppercase text-muted small mb-3">Settings</h6>
            <a href="{{ route('admin.settings.index') }}" class="nav-link">
                <i class="bi bi-gear me-2"></i> Site Settings
            </a>
        </nav>
    </div>
</aside>
