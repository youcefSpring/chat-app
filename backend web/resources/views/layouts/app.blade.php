<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Chat App') }} @hasSection('title') - @yield('title') @endif</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --bs-primary: #4f46e5;
            --bs-secondary: #6b7280;
            --chat-bg: #f8fafc;
            --sidebar-bg: #1f2937;
            --message-bg: #ffffff;
            --online-color: #10b981;
            --away-color: #f59e0b;
            --offline-color: #6b7280;
            --dnd-color: #ef4444;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--chat-bg);
        }

        .chat-layout {
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            background-color: var(--sidebar-bg);
            width: 280px;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .status-online { color: var(--online-color); }
        .status-away { color: var(--away-color); }
        .status-offline { color: var(--offline-color); }
        .status-dnd { color: var(--dnd-color); }

        .message-item {
            background: var(--message-bg);
            border-radius: 0.75rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .typing-indicator {
            font-style: italic;
            color: var(--bs-secondary);
            font-size: 0.875rem;
        }

        .channel-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .unread-badge {
            background-color: var(--bs-primary);
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            min-width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .navbar-brand {
            font-weight: 600;
        }

        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
    </style>

    @yield('styles')
</head>

<body>
    @auth
        @include('layouts.navigation')
    @endauth

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show mb-0" role="alert">
            <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>{{ config('app.name', 'Teacher Portfolio') }}</h5>
                    <p class="text-light mb-0">Sharing knowledge through teaching, research, and development.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="mb-2">
                        <a href="{{ route('contact.show') }}" class="text-light text-decoration-none me-3">
                            <i class="bi bi-envelope"></i> Contact
                        </a>
                        <a href="{{ route('download-cv') }}" class="text-light text-decoration-none">
                            <i class="bi bi-download"></i> Download CV
                        </a>
                    </div>
                    <small class="text-light">
                        © {{ date('Y') }} {{ config('app.name', 'Teacher Portfolio') }}. All rights reserved.
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Auto-dismiss alerts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert && !alert.classList.contains('show')) return;
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>

    @yield('scripts')
</body>
</html>