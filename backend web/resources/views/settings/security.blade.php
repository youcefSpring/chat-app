@extends('layouts.app')

@section('title', 'Security Settings')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            @include('settings.sidebar')
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Security Settings</h5>
                </div>
                <div class="card-body">
                    <!-- Change Password -->
                    <form id="passwordForm" class="mb-5">
                        @csrf
                        @method('PATCH')

                        <h6 class="border-bottom pb-2 mb-3">Change Password</h6>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Password must be at least 8 characters long</div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </form>

                    <!-- Two-Factor Authentication -->
                    <div class="mb-5">
                        <h6 class="border-bottom pb-2 mb-3">Two-Factor Authentication</h6>

                        @if($user->two_factor_secret)
                            <div class="alert alert-success">
                                <i class="bi bi-shield-check me-2"></i>
                                Two-factor authentication is enabled for your account.
                            </div>

                            <form id="disableTwoFactorForm">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">Disable Two-Factor Authentication</button>
                            </form>
                        @else
                            <div class="alert alert-warning">
                                <i class="bi bi-shield-exclamation me-2"></i>
                                Two-factor authentication is not enabled. Enable it for better security.
                            </div>

                            <a href="{{ route('settings.two-factor') }}" class="btn btn-success">
                                <i class="bi bi-shield-plus me-2"></i>Enable Two-Factor Authentication
                            </a>
                        @endif
                    </div>

                    <!-- Active Sessions -->
                    <div class="mb-5">
                        <h6 class="border-bottom pb-2 mb-3">Active Sessions</h6>

                        <div class="list-group">
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Current Session</strong>
                                        <div class="small text-muted">
                                            <i class="bi bi-geo-alt me-1"></i>{{ request()->ip() }} •
                                            {{ request()->userAgent() }}
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Login History -->
                    <div>
                        <h6 class="border-bottom pb-2 mb-3">Recent Login Activity</h6>

                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>IP Address</th>
                                        <th>Device</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ now()->format('M d, Y H:i') }}</td>
                                        <td>{{ request()->ip() }}</td>
                                        <td>{{ request()->userAgent() }}</td>
                                        <td><span class="badge bg-success">Success</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Handle password form submission
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        submitBtn.disabled = true;

        fetch('/settings/security/password', {
            method: 'PATCH',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.reset();
                showAlert('success', 'Password updated successfully!');
            } else {
                showAlert('danger', data.message || 'Error updating password');
            }
        })
        .catch(error => {
            console.error('Error updating password:', error);
            showAlert('danger', 'Error updating password. Please try again.');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });

    // Handle disable two-factor form
    const disableTwoFactorForm = document.getElementById('disableTwoFactorForm');
    if (disableTwoFactorForm) {
        disableTwoFactorForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (confirm('Are you sure you want to disable two-factor authentication?')) {
                const formData = new FormData(this);

                fetch('/settings/two-factor/disable', {
                    method: 'DELETE',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        showAlert('danger', data.message || 'Error disabling two-factor authentication');
                    }
                })
                .catch(error => {
                    console.error('Error disabling two-factor:', error);
                    showAlert('danger', 'Error disabling two-factor authentication');
                });
            }
        });
    }

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        const container = document.querySelector('.card-body');
        container.insertAdjacentHTML('afterbegin', alertHtml);

        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
</script>
@endsection