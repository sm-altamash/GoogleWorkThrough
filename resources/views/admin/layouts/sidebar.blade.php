<div class="app-brand demo">
    <a href="index.html" class="app-brand-link">
        <span class="app-brand-text demo menu-text fw-bold">Nadra Records</span>
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

    <!-- Nadra Import -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="Nadra Imports">Nadra Imports</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'nadra' || request()->segment(1) == 'permissions' ? 'active' : ''}}">
        <a href="{{route('nadra.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-devices-bolt"></i>
            <div>Nadra Excel Upload</div>
        </a>
    </li>

    <!-- Google Calender -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="Nadra Imports">Nadra Imports</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'calendar' || request()->segment(1) == 'permissions' ? 'active' : ''}}">
        <a href="{{route('calendar.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-calendar"></i>
            <div>Google Calendar</div>
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
