<div class="app-brand demo">

    <a href="index.html" class="app-brand-link">
        <span class="app-brand-text demo menu-text fw-bold">Administration</span>
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
            <div data-i18n="Nadra Excel Upload">Nadra Excel Upload</div>
        </a>
    </li>

    <!-- Google Calender & Google Meet -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="Meetings">Meetings</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'calendar' || request()->segment(1) == 'permissions' ? 'active' : ''}}">
        <a href="{{route('calendar.view')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-calendar"></i>
            <div data-i18n="Events">Events</div>
        </a>
    </li>

    <!-- Google Gmail -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="Gmail">Gmail</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'gmail' || request()->segment(1) == 'permissions' ? 'active' : ''}}">
        <a href="{{route('gmail.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-mail"></i>
            <div data-i18n="Emails">Emails</div>
        </a>
    </li>


    <!-- Storage -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="Storage">Storage</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'permissions' ? 'active' : '' }}">
        <a href="{{route('drive.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-brand-google-drive"></i>
            <div data-i18n="Google Drive">Google Drive</div>
        </a>
    </li>

    <!-- YouTube -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="YouTube">YouTube</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'permissions' ? 'active' : '' }}">
        <a href="{{route('youtube.upload')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-brand-youtube"></i>
            <div data-i18n="YouTube Upload">YouTube Upload</div>
        </a>
    </li>

        <!-- WhatsApp -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="WhatsApp">WhatsApp</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'whatsapp' || request()->segment(1) == 'permissions' ? 'active' : ''}}">
        <a href="{{route('whatsapp.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-brand-whatsapp"></i>
            <div data-i18n="WhatsApp">WhatsApp</div>
        </a>
    </li>

    <!-- User Management -->
    <li class="menu-header small text-uppercase">
        <span class="menu-header-text" data-i18n="User Management">User Management</span>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'roles' || request()->segment(1) == 'permissions' ? 'active' : ''}}">
        <a href="{{route('roles.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-settings"></i>
            <div data-i18n="Roles & Permissions">Roles & Permissions</div>
        </a>
    </li>
    <li class="menu-item {{ request()->segment(1) == 'users' ? 'active' : ''}}">
        <a href="{{route('users.index')}}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-user"></i>
            <div data-i18n="Users">Users</div>
        </a>
    </li>
</ul>
