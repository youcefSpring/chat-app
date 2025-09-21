<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Settings</h6>
    </div>
    <div class="list-group list-group-flush">
        <a href="{{ route('settings.profile') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.profile') ? 'active' : '' }}">
            <i class="bi bi-person me-2"></i>Profile
        </a>
        <a href="{{ route('settings.preferences') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.preferences') ? 'active' : '' }}">
            <i class="bi bi-gear me-2"></i>Preferences
        </a>
        <a href="{{ route('settings.notifications') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.notifications') ? 'active' : '' }}">
            <i class="bi bi-bell me-2"></i>Notifications
        </a>
        <a href="{{ route('settings.security') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.security') ? 'active' : '' }}">
            <i class="bi bi-shield-lock me-2"></i>Security
        </a>
    </div>
</div>