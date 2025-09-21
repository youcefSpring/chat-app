@extends('layouts.app')

@section('title', 'Notification Settings')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            @include('settings.sidebar')
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Notification Settings</h5>
                </div>
                <div class="card-body">
                    <form id="notificationForm">
                        @csrf
                        @method('PATCH')

                        <!-- Email Notifications -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">Email Notifications</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_messages" name="email_messages"
                                               {{ $user->notification_settings['email_messages'] ?? true ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_messages">
                                            <strong>New Messages</strong>
                                            <div class="small text-muted">Get notified when you receive new direct messages</div>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_mentions" name="email_mentions"
                                               {{ $user->notification_settings['email_mentions'] ?? true ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_mentions">
                                            <strong>Mentions</strong>
                                            <div class="small text-muted">Get notified when someone mentions you in a channel</div>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_channel_invites" name="email_channel_invites"
                                               {{ $user->notification_settings['email_channel_invites'] ?? true ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_channel_invites">
                                            <strong>Channel Invitations</strong>
                                            <div class="small text-muted">Get notified when you're invited to join a channel</div>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_file_shares" name="email_file_shares"
                                               {{ $user->notification_settings['email_file_shares'] ?? false ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_file_shares">
                                            <strong>File Shares</strong>
                                            <div class="small text-muted">Get notified when files are shared with you</div>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_digest" name="email_digest"
                                               {{ $user->notification_settings['email_digest'] ?? true ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_digest">
                                            <strong>Daily Digest</strong>
                                            <div class="small text-muted">Get a daily summary of activity</div>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="email_security" name="email_security"
                                               {{ $user->notification_settings['email_security'] ?? true ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_security">
                                            <strong>Security Alerts</strong>
                                            <div class="small text-muted">Get notified about security-related events</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Push Notifications -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">Push Notifications</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="push_messages" name="push_messages"
                                               {{ $user->notification_settings['push_messages'] ?? true ? 'checked' : '' }}>
                                        <label class="form-check-label" for="push_messages">
                                            <strong>New Messages</strong>
                                            <div class="small text-muted">Browser notifications for new messages</div>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="push_mentions" name="push_mentions"
                                               {{ $user->notification_settings['push_mentions'] ?? true ? 'checked' : '' }}>
                                        <label class="form-check-label" for="push_mentions">
                                            <strong>Mentions</strong>
                                            <div class="small text-muted">Browser notifications when mentioned</div>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="push_calls" name="push_calls"
                                               {{ $user->notification_settings['push_calls'] ?? true ? 'checked' : '' }}>
                                        <label class="form-check-label" for="push_calls">
                                            <strong>Incoming Calls</strong>
                                            <div class="small text-muted">Browser notifications for incoming calls</div>
                                        </label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="push_sound" name="push_sound"
                                               {{ $user->notification_settings['push_sound'] ?? true ? 'checked' : '' }}>
                                        <label class="form-check-label" for="push_sound">
                                            <strong>Sound Notifications</strong>
                                            <div class="small text-muted">Play sounds for notifications</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Do Not Disturb -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">Do Not Disturb</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="dnd_start" class="form-label">Start Time</label>
                                        <input type="time" class="form-control" id="dnd_start" name="dnd_start"
                                               value="{{ $user->notification_settings['dnd_start'] ?? '22:00' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="dnd_end" class="form-label">End Time</label>
                                        <input type="time" class="form-control" id="dnd_end" name="dnd_end"
                                               value="{{ $user->notification_settings['dnd_end'] ?? '08:00' }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="dnd_weekends" name="dnd_weekends"
                                       {{ $user->notification_settings['dnd_weekends'] ?? false ? 'checked' : '' }}>
                                <label class="form-check-label" for="dnd_weekends">
                                    <strong>Weekend Do Not Disturb</strong>
                                    <div class="small text-muted">Automatically enable DND on weekends</div>
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Reset</button>
                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function resetForm() {
        if (confirm('Are you sure you want to reset all notification settings to default?')) {
            location.reload();
        }
    }

    // Handle notification form submission
    document.getElementById('notificationForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        submitBtn.disabled = true;

        fetch('/settings/notifications', {
            method: 'PATCH',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Notification preferences saved successfully!');
            } else {
                showAlert('danger', data.message || 'Error saving preferences');
            }
        })
        .catch(error => {
            console.error('Error saving notifications:', error);
            showAlert('danger', 'Error saving preferences. Please try again.');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        const form = document.getElementById('notificationForm');
        form.insertAdjacentHTML('afterbegin', alertHtml);

        setTimeout(() => {
            const alert = form.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
</script>
@endsection