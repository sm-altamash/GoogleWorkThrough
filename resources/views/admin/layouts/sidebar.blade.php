<div class="app-brand demo">
    <a href="index.html" class="app-brand-link">
        <span class="app-brand-logo demo">
            <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z"
                    fill="#7367F0" />
                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"
                    d="M7.69824 16.4364L12.5199 3.23696L16.5541 7.25596L7.69824 16.4364Z" fill="#161616" />
                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"
                    d="M8.07751 15.9175L13.9419 4.63989L16.5849 7.28475L8.07751 15.9175Z" fill="#161616" />
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M7.77295 16.3566L23.6563 0H32V6.88383C32 6.88383 31.8262 9.17836 30.6591 10.4057L19.7824 22H13.6938L7.77295 16.3566Z"
                    fill="#7367F0" />
            </svg>
        </span>
        <span class="app-brand-text demo menu-text fw-bold">Vuexy</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
        <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
        <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
</div>

<div class="menu-inner-shadow"></div>

<ul class="menu-inner py-1">
    <!-- Dashboards -->
    <li class="menu-item {{ request()->segment(1) == 'dashboard' ? 'active' : ''}}">
        <a href="{{route('dashboard')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-smart-home"></i>
            <div data-i18n="Dashboard">Dashboard</div>
        </a>
    </li>

    <!-- Nadra Importst -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="Nadra Imports">Nadra Imports</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'roles' || request()->segment(1) == 'permissions' ? 'active' : ''}}">
        <a href="{{route('nadra.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-devices-bolt"></i>
            <div>Nadra Excel Upload</div>
        </a>
    </li>

    <!-- User Management -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="User Management">User Management</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'roles' || request()->segment(1) == 'permissions' ? 'active' : ''}}">
        <a href="{{route('roles.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-settings"></i>
            <div>Roles & Permissions</div>
        </a>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'users' ? 'active' : ''}}">
        <a href="{{route('users.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-user"></i>
            <div>Users</div>
        </a>
    </li>
</ul>
