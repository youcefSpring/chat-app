@extends('layouts.app')

@section('title', 'Profile Settings')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            @include('settings.sidebar')
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profile Settings</h5>
                </div>
                <div class="card-body">
                    <form id="profileForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Profile Picture -->
                        <div class="mb-4">
                            <label class="form-label">Profile Picture</label>
                            <div class="d-flex align-items-center">
                                <div class="position-relative me-3">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ auth()->user()->avatar_url }}" alt="Profile" class="rounded-circle" width="80" height="80" id="avatarPreview">
                                    @else
                                        <i class="bi bi-person-circle display-4" id="avatarIcon"></i>
                                    @endif
                                </div>
                                <div>
                                    <input type="file" class="form-control mb-2" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(event)">
                                    <small class="text-muted">JPG, PNG or GIF (max 2MB)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ auth()->user()->name }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ auth()->user()->email }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="{{ auth()->user()->phone }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <option value="">Select timezone</option>
                                        <option value="UTC" {{ auth()->user()->timezone === 'UTC' ? 'selected' : '' }}>UTC</option>
                                        <option value="America/New_York" {{ auth()->user()->timezone === 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                        <option value="America/Chicago" {{ auth()->user()->timezone === 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                        <option value="America/Denver" {{ auth()->user()->timezone === 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                                        <option value="America/Los_Angeles" {{ auth()->user()->timezone === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                                        <option value="Europe/London" {{ auth()->user()->timezone === 'Europe/London' ? 'selected' : '' }}>London</option>
                                        <option value="Europe/Paris" {{ auth()->user()->timezone === 'Europe/Paris' ? 'selected' : '' }}>Paris</option>
                                        <option value="Asia/Tokyo" {{ auth()->user()->timezone === 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Status and Bio -->
                        <div class="mb-3">
                            <label for="status_message" class="form-label">Status Message</label>
                            <input type="text" class="form-control" id="status_message" name="status_message"
                                   value="{{ auth()->user()->status_message }}" placeholder="What's your current status?">
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell us about yourself">{{ auth()->user()->bio }}</textarea>
                        </div>

                        <!-- Presence Status -->
                        <div class="mb-4">
                            <label class="form-label">Presence Status</label>
                            <div class="row">
                                <div class="col-sm-6 col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="presence_status" id="online" value="online"
                                               {{ auth()->user()->presence_status === 'online' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="online">
                                            <span class="status-online">●</span> Online
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="presence_status" id="away" value="away"
                                               {{ auth()->user()->presence_status === 'away' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="away">
                                            <span class="status-away">●</span> Away
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="presence_status" id="dnd" value="dnd"
                                               {{ auth()->user()->presence_status === 'dnd' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="dnd">
                                            <span class="status-dnd">●</span> Do Not Disturb
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="presence_status" id="offline" value="offline"
                                               {{ auth()->user()->presence_status === 'offline' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="offline">
                                            <span class="status-offline">●</span> Offline
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Reset</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
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
    function previewAvatar(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const avatarPreview = document.getElementById('avatarPreview');
                const avatarIcon = document.getElementById('avatarIcon');

                if (avatarPreview) {
                    avatarPreview.src = e.target.result;
                } else {
                    // Create new image element if none exists
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Profile';
                    img.className = 'rounded-circle';
                    img.width = 80;
                    img.height = 80;
                    img.id = 'avatarPreview';

                    if (avatarIcon) {
                        avatarIcon.parentNode.replaceChild(img, avatarIcon);
                    }
                }
            };
            reader.readAsDataURL(file);
        }
    }

    function resetForm() {
        if (confirm('Are you sure you want to reset all changes?')) {
            document.getElementById('profileForm').reset();
            // Reset avatar preview if needed
            location.reload();
        }
    }

    // Handle profile form submission
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        submitBtn.disabled = true;

        fetch('/api/user/profile', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="bi bi-check-circle me-2"></i>Profile updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                this.insertBefore(alert, this.firstChild);

                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    if (alert && alert.parentNode) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 3000);
            } else {
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>Error updating profile: ${data.message || 'Unknown error'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                this.insertBefore(alert, this.firstChild);
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                <i class="bi bi-exclamation-triangle me-2"></i>Error updating profile. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            this.insertBefore(alert, this.firstChild);
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
</script>
@endsection