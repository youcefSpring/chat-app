@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <div class="text-muted">
            <i class="bi bi-building me-1"></i>{{ auth()->user()->organization->name }}
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-people fs-1 text-primary mb-2"></i>
                    <h3 class="mb-0">{{ $stats['total_users'] }}</h3>
                    <small class="text-muted">Total Users</small>
                    <div class="small text-success mt-1">
                        <i class="bi bi-arrow-up"></i>{{ $stats['active_users'] }} active
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-hash fs-1 text-success mb-2"></i>
                    <h3 class="mb-0">{{ $stats['total_channels'] }}</h3>
                    <small class="text-muted">Channels</small>
                    <div class="small text-info mt-1">
                        <i class="bi bi-lock"></i>{{ $stats['private_channels'] }} private
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-chat-dots fs-1 text-info mb-2"></i>
                    <h3 class="mb-0">{{ $stats['total_messages'] }}</h3>
                    <small class="text-muted">Messages</small>
                    <div class="small text-primary mt-1">
                        <i class="bi bi-calendar"></i>{{ $stats['messages_today'] }} today
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-cloud-arrow-up fs-1 text-warning mb-2"></i>
                    <h3 class="mb-0">{{ $stats['storage_used'] }}</h3>
                    <small class="text-muted">Storage Used</small>
                    <div class="small text-warning mt-1">
                        <i class="bi bi-file-earmark"></i>{{ $stats['total_files'] }} files
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="row">
        <div class="col-lg-8">
            <!-- Recent Activity -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activity</h5>
                    <a href="{{ route('admin.audit-logs') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($recentActivity->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentActivity as $activity)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative me-3">
                                            <i class="bi bi-person-circle fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong>{{ $activity->user->name }}</strong>
                                                    <span class="text-muted">{{ $activity->description }}</span>
                                                </div>
                                                <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                            </div>
                                            @if($activity->properties)
                                                <small class="text-muted">{{ $activity->properties['details'] ?? '' }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-activity fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- System Health -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Health</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Database Connection</span>
                                <span class="badge bg-success">Healthy</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Cache System</span>
                                <span class="badge bg-success">Running</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Queue System</span>
                                <span class="badge bg-success">Processing</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>WebSocket Server</span>
                                <span class="badge bg-success">Connected</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>File Storage</span>
                                <span class="badge bg-warning">{{ round($stats['storage_percentage'], 1) }}% Used</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Mail Service</span>
                                <span class="badge bg-success">Operational</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.users') }}" class="btn btn-outline-primary">
                            <i class="bi bi-people me-2"></i>Manage Users
                        </a>
                        <a href="{{ route('admin.channels') }}" class="btn btn-outline-success">
                            <i class="bi bi-hash me-2"></i>Manage Channels
                        </a>
                        <a href="{{ route('admin.settings') }}" class="btn btn-outline-info">
                            <i class="bi bi-gear me-2"></i>Organization Settings
                        </a>
                        <a href="{{ route('admin.audit-logs') }}" class="btn btn-outline-warning">
                            <i class="bi bi-journal-text me-2"></i>Audit Logs
                        </a>
                    </div>
                </div>
            </div>

            <!-- Online Users -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Online Users</h5>
                    <span class="badge bg-success">{{ $onlineUsers->count() }}</span>
                </div>
                <div class="card-body">
                    @if($onlineUsers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($onlineUsers->take(10) as $user)
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative me-2">
                                            <i class="bi bi-person-circle"></i>
                                            <span class="position-absolute top-0 start-100 translate-middle p-1
                                                @if($user->presence_status === 'online') bg-success
                                                @elseif($user->presence_status === 'away') bg-warning
                                                @elseif($user->presence_status === 'dnd') bg-danger
                                                @else bg-secondary @endif
                                                border border-light rounded-circle">
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-medium">{{ $user->name }}</div>
                                            <small class="text-muted">{{ ucfirst($user->presence_status) }}</small>
                                        </div>
                                        @if($user->role === 'admin')
                                            <span class="badge bg-warning">Admin</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($onlineUsers->count() > 10)
                            <div class="text-center mt-2">
                                <a href="{{ route('admin.users') }}" class="text-decoration-none small">
                                    View all {{ $onlineUsers->count() }} online users
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-3">
                            <i class="bi bi-person-x fs-4 text-muted mb-2"></i>
                            <p class="text-muted mb-0">No users online</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Auto-refresh dashboard data every 30 seconds
    setInterval(function() {
        fetch('/api/admin/dashboard-stats', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update stats without full page reload
                updateDashboardStats(data.stats);
            }
        })
        .catch(error => {
            console.error('Error refreshing dashboard:', error);
        });
    }, 30000);

    function updateDashboardStats(stats) {
        // Update the stats cards with new data
        const statElements = {
            'total_users': document.querySelector('.card-body h3:nth-of-type(1)'),
            'total_channels': document.querySelector('.card-body h3:nth-of-type(2)'),
            'total_messages': document.querySelector('.card-body h3:nth-of-type(3)'),
            'storage_used': document.querySelector('.card-body h3:nth-of-type(4)')
        };

        for (const [key, element] of Object.entries(statElements)) {
            if (element && stats[key] !== undefined) {
                element.textContent = stats[key];
            }
        }
    }
</script>
@endsection