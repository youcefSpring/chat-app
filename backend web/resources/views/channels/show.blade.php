@extends('layouts.app')

@section('title', $channel->name . ' - Channel Details')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Channel Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-{{ $channel->type === 'private' ? 'lock' : 'hash' }} fs-2 me-3 text-{{ $channel->type === 'private' ? 'warning' : 'primary' }}"></i>
                            <div>
                                <h3 class="mb-1">{{ $channel->name }}</h3>
                                <p class="text-muted mb-0">
                                    {{ ucfirst($channel->type) }} channel •
                                    {{ $channel->members->count() }} member{{ $channel->members->count() !== 1 ? 's' : '' }} •
                                    Created {{ $channel->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <a href="{{ route('dashboard', ['channel' => $channel->id]) }}" class="btn btn-primary me-2">
                                <i class="bi bi-chat me-1"></i>Open Chat
                            </a>
                            @if(auth()->user()->role === 'admin' || $channel->created_by === auth()->id())
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="editChannel()"><i class="bi bi-pencil me-2"></i>Edit Channel</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteChannel()"><i class="bi bi-trash me-2"></i>Delete Channel</a></li>
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($channel->description)
                        <div class="mt-3">
                            <h6>Description</h6>
                            <p class="text-muted">{{ $channel->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Channel Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-people fs-1 text-primary mb-2"></i>
                            <h4 class="mb-0">{{ $channel->members->count() }}</h4>
                            <small class="text-muted">Members</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-chat-dots fs-1 text-success mb-2"></i>
                            <h4 class="mb-0">{{ $channel->messages->count() }}</h4>
                            <small class="text-muted">Messages</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-paperclip fs-1 text-info mb-2"></i>
                            <h4 class="mb-0">{{ $channel->attachments->count() }}</h4>
                            <small class="text-muted">Files</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-telephone fs-1 text-warning mb-2"></i>
                            <h4 class="mb-0">{{ $channel->calls->count() }}</h4>
                            <small class="text-muted">Calls</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if($recentMessages->count() > 0)
                        @foreach($recentMessages as $message)
                            <div class="d-flex mb-3">
                                <div class="position-relative me-2">
                                    <i class="bi bi-person-circle fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <strong class="me-2">{{ $message->user->name }}</strong>
                                        <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="text-muted">
                                        {{ Str::limit($message->content, 100) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="text-center">
                            <a href="{{ route('dashboard', ['channel' => $channel->id]) }}" class="btn btn-outline-primary">
                                View All Messages
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-chat-dots fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No messages yet. Start the conversation!</p>
                            <a href="{{ route('dashboard', ['channel' => $channel->id]) }}" class="btn btn-primary">
                                Send First Message
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Members -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Members ({{ $channel->members->count() }})</h5>
                    @if(auth()->user()->role === 'admin' || $channel->created_by === auth()->id())
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMembersModal">
                            <i class="bi bi-person-plus"></i>
                        </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($channel->members as $member)
                            <div class="list-group-item d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-2">
                                        <i class="bi bi-person-circle fs-4"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle p-1
                                            @if($member->presence_status === 'online') bg-success
                                            @elseif($member->presence_status === 'away') bg-warning
                                            @elseif($member->presence_status === 'dnd') bg-danger
                                            @else bg-secondary @endif
                                            border border-light rounded-circle">
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-medium">{{ $member->name }}</div>
                                        <small class="text-muted">{{ ucfirst($member->presence_status ?? 'offline') }}</small>
                                        @if($member->id === $channel->created_by)
                                            <span class="badge bg-primary ms-1">Creator</span>
                                        @endif
                                        @if($member->role === 'admin')
                                            <span class="badge bg-warning ms-1">Admin</span>
                                        @endif
                                    </div>
                                </div>
                                @if((auth()->user()->role === 'admin' || $channel->created_by === auth()->id()) && $member->id !== auth()->id())
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="startDirectMessage({{ $member->id }})"><i class="bi bi-chat me-2"></i>Message</a></li>
                                            @if($channel->type !== 'public')
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="removeMember({{ $member->id }})"><i class="bi bi-person-dash me-2"></i>Remove</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Files -->
            @if($channel->attachments->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Files</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($channel->attachments->take(5) as $attachment)
                                <div class="list-group-item d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark fs-4 me-2 text-muted"></i>
                                        <div>
                                            <div class="fw-medium">{{ $attachment->original_name }}</div>
                                            <small class="text-muted">
                                                {{ $attachment->size_formatted }} •
                                                {{ $attachment->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    </div>
                                    <a href="{{ route('attachments.download', $attachment) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Add Members Modal -->
@if(auth()->user()->role === 'admin' || $channel->created_by === auth()->id())
<div class="modal fade" id="addMembersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Members</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addMembersForm">
                @csrf
                <div class="modal-body">
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        @foreach($availableMembers as $member)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="members[]" value="{{ $member->id }}" id="addMember{{ $member->id }}">
                                <label class="form-check-label" for="addMember{{ $member->id }}">
                                    {{ $member->name }} <small class="text-muted">({{ $member->email }})</small>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @if($availableMembers->isEmpty())
                        <p class="text-muted mb-0">All organization members are already in this channel.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" {{ $availableMembers->isEmpty() ? 'disabled' : '' }}>Add Members</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    @if(auth()->user()->role === 'admin' || $channel->created_by === auth()->id())
    // Handle add members form
    document.getElementById('addMembersForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
        submitBtn.disabled = true;

        fetch(`/api/channels/{{ $channel->id }}/members`, {
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
                alert('Error adding members: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error adding members:', error);
            alert('Error adding members');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    @endif

    function editChannel() {
        // Redirect to edit page or open edit modal
        window.location.href = '/channels/{{ $channel->id }}/edit';
    }

    function deleteChannel() {
        if (confirm('Are you sure you want to delete this channel? This action cannot be undone.')) {
            fetch(`/api/channels/{{ $channel->id }}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/channels';
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

    function removeMember(memberId) {
        if (confirm('Are you sure you want to remove this member from the channel?')) {
            fetch(`/api/channels/{{ $channel->id }}/members/${memberId}`, {
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
                    alert('Error removing member: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error removing member:', error);
                alert('Error removing member');
            });
        }
    }

    function startDirectMessage(userId) {
        // Create or find direct message channel and redirect
        fetch(`/api/direct-messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `/dashboard?channel=${data.channel.id}`;
            }
        })
        .catch(error => {
            console.error('Error starting direct message:', error);
        });
    }
</script>
@endsection