@extends('layouts.app')

@section('title', 'Channel Management')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Channel Management</h2>
                @if(auth()->user()->role === 'admin')
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createChannelModal">
                        <i class="bi bi-plus-circle me-2"></i>Create Channel
                    </button>
                @endif
            </div>

            <!-- Channels List -->
            <div class="row">
                @foreach($channels as $channel)
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-{{ $channel->type === 'private' ? 'lock' : 'hash' }} fs-4 me-2 text-{{ $channel->type === 'private' ? 'warning' : 'primary' }}"></i>
                                    <h5 class="card-title mb-0">{{ $channel->name }}</h5>
                                </div>

                                @if($channel->description)
                                    <p class="card-text text-muted">{{ Str::limit($channel->description, 100) }}</p>
                                @endif

                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="small text-muted">Members</div>
                                        <div class="fw-bold">{{ $channel->members->count() }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Messages</div>
                                        <div class="fw-bold">{{ $channel->messages->count() }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Type</div>
                                        <div class="fw-bold">{{ ucfirst($channel->type) }}</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Created {{ $channel->created_at->diffForHumans() }}
                                    </small>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('dashboard', ['channel' => $channel->id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-chat"></i>
                                        </a>
                                        @if(auth()->user()->role === 'admin' || $channel->created_by === auth()->id())
                                            <button class="btn btn-sm btn-outline-secondary" onclick="editChannel({{ $channel->id }})">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteChannel({{ $channel->id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($channels->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots display-1 text-muted mb-3"></i>
                    <h4 class="text-muted">No channels found</h4>
                    <p class="text-muted">Create your first channel to get started.</p>
                    @if(auth()->user()->role === 'admin')
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createChannelModal">
                            <i class="bi bi-plus-circle me-2"></i>Create Channel
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create Channel Modal -->
@if(auth()->user()->role === 'admin')
<div class="modal fade" id="createChannelModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Channel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createChannelForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="channelName" class="form-label">Channel Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="channelName" name="name" required>
                                <div class="form-text">Use lowercase letters, numbers, and hyphens</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="channelType" class="form-label">Channel Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="channelType" name="type" required>
                                    <option value="public">Public - Anyone in the organization can join</option>
                                    <option value="private">Private - Invite only</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="channelDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="channelDescription" name="description" rows="3" placeholder="What's this channel about?"></textarea>
                    </div>

                    <div class="mb-3" id="membersSection" style="display: none;">
                        <label class="form-label">Add Members</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            @foreach($organizationMembers as $member)
                                @if($member->id !== auth()->id())
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="members[]" value="{{ $member->id }}" id="member{{ $member->id }}">
                                        <label class="form-check-label" for="member{{ $member->id }}">
                                            {{ $member->name }} <small class="text-muted">({{ $member->email }})</small>
                                        </label>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Channel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Channel Modal -->
<div class="modal fade" id="editChannelModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Channel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editChannelForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="editChannelId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editChannelName" class="form-label">Channel Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editChannelName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editChannelType" class="form-label">Channel Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="editChannelType" name="type" required>
                                    <option value="public">Public</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editChannelDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editChannelDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Channel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    // Show/hide members section based on channel type
    document.getElementById('channelType').addEventListener('change', function() {
        const membersSection = document.getElementById('membersSection');
        if (this.value === 'private') {
            membersSection.style.display = 'block';
        } else {
            membersSection.style.display = 'none';
        }
    });

    // Handle create channel form
    @if(auth()->user()->role === 'admin')
    document.getElementById('createChannelForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
        submitBtn.disabled = true;

        fetch('/api/channels', {
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
                location.reload();
            } else {
                alert('Error creating channel: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error creating channel:', error);
            alert('Error creating channel');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });

    // Handle edit channel form
    document.getElementById('editChannelForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const channelId = document.getElementById('editChannelId').value;
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        submitBtn.disabled = true;

        fetch(`/api/channels/${channelId}`, {
            method: 'PUT',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating channel: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating channel:', error);
            alert('Error updating channel');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    @endif

    function editChannel(channelId) {
        // Fetch channel details and populate the edit form
        fetch(`/api/channels/${channelId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const channel = data.channel;
                document.getElementById('editChannelId').value = channel.id;
                document.getElementById('editChannelName').value = channel.name;
                document.getElementById('editChannelType').value = channel.type;
                document.getElementById('editChannelDescription').value = channel.description || '';

                const editModal = new bootstrap.Modal(document.getElementById('editChannelModal'));
                editModal.show();
            }
        })
        .catch(error => {
            console.error('Error fetching channel details:', error);
        });
    }

    function deleteChannel(channelId) {
        if (confirm('Are you sure you want to delete this channel? This action cannot be undone.')) {
            fetch(`/api/channels/${channelId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting channel: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting channel:', error);
                alert('Error deleting channel');
            });
        }
    }
</script>
@endsection