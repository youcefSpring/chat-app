@extends('layouts.app')

@section('title', $channel->name . ' - Members')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('channels.show', $channel) }}" class="btn btn-outline-secondary btn-sm me-3">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                            <div>
                                <h5 class="mb-0">
                                    <i class="bi bi-{{ $channel->type === 'private' ? 'lock' : 'hash' }} me-2"></i>
                                    {{ $channel->name }} - Members
                                </h5>
                                <small class="text-muted">{{ $members->count() }} member{{ $members->count() !== 1 ? 's' : '' }}</small>
                            </div>
                        </div>
                        @if($canManageMembers)
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMembersModal">
                                <i class="bi bi-person-plus me-1"></i>Add Members
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($members as $member)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="position-relative me-3">
                                                    <i class="bi bi-person-circle fs-2"></i>
                                                    <span class="position-absolute top-0 start-100 translate-middle p-1
                                                        @if($member->presence_status === 'online') bg-success
                                                        @elseif($member->presence_status === 'away') bg-warning
                                                        @elseif($member->presence_status === 'dnd') bg-danger
                                                        @else bg-secondary @endif
                                                        border border-light rounded-circle">
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $member->name }}</h6>
                                                    <small class="text-muted">{{ $member->email }}</small>
                                                    <div class="small">
                                                        <span class="badge bg-light text-dark">{{ ucfirst($member->presence_status ?? 'offline') }}</span>
                                                        @if($member->id === $channel->created_by)
                                                            <span class="badge bg-primary">Creator</span>
                                                        @endif
                                                        @if($member->role === 'admin')
                                                            <span class="badge bg-warning">Admin</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @if($canManageMembers && $member->id !== auth()->id())
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="startDirectMessage({{ $member->id }})">
                                                                <i class="bi bi-chat me-2"></i>Send Message
                                                            </a>
                                                        </li>
                                                        @if($channel->type !== 'public')
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="#" onclick="removeMember({{ $member->id }}, '{{ $member->name }}')">
                                                                    <i class="bi bi-person-dash me-2"></i>Remove from Channel
                                                                </a>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>

                                        @if($member->status_message)
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-chat-quote me-1"></i>{{ $member->status_message }}
                                                </small>
                                            </div>
                                        @endif

                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Joined {{ $member->pivot->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($members->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-people fs-1 text-muted mb-3"></i>
                            <h5 class="text-muted">No members found</h5>
                            <p class="text-muted">This channel doesn't have any members yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Members Modal -->
@if($canManageMembers)
<div class="modal fade" id="addMembersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Members to {{ $channel->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addMembersForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="memberSearch" placeholder="Search for members...">
                    </div>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;" id="availableMembers">
                        <!-- Available members will be loaded here -->
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <span class="ms-2">Loading members...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Selected Members</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Member Modal -->
<div class="modal fade" id="removeMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remove Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove <strong id="memberNameToRemove"></strong> from this channel?</p>
                <p class="text-muted">They will no longer be able to see messages or participate in this channel.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRemoveMember">Remove Member</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    let memberToRemove = null;

    @if($canManageMembers)
    // Load available members when modal opens
    document.getElementById('addMembersModal').addEventListener('show.bs.modal', function() {
        loadAvailableMembers();
    });

    function loadAvailableMembers() {
        fetch(`/api/channels/{{ $channel->id }}/available-members`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAvailableMembers(data.members);
            } else {
                document.getElementById('availableMembers').innerHTML = '<p class="text-muted text-center">No available members found.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading members:', error);
            document.getElementById('availableMembers').innerHTML = '<p class="text-danger text-center">Error loading members.</p>';
        });
    }

    function displayAvailableMembers(members) {
        const container = document.getElementById('availableMembers');

        if (members.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">All organization members are already in this channel.</p>';
            return;
        }

        let html = '';
        members.forEach(member => {
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="members[]" value="${member.id}" id="addMember${member.id}">
                    <label class="form-check-label d-flex align-items-center" for="addMember${member.id}">
                        <i class="bi bi-person-circle me-2"></i>
                        <div>
                            <div>${member.name}</div>
                            <small class="text-muted">${member.email}</small>
                        </div>
                    </label>
                </div>
            `;
        });
        container.innerHTML = html;
    }

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

    // Search members
    document.getElementById('memberSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const memberItems = document.querySelectorAll('#availableMembers .form-check');

        memberItems.forEach(item => {
            const label = item.querySelector('label').textContent.toLowerCase();
            if (label.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
    @endif

    function removeMember(memberId, memberName) {
        memberToRemove = memberId;
        document.getElementById('memberNameToRemove').textContent = memberName;

        const removeModal = new bootstrap.Modal(document.getElementById('removeMemberModal'));
        removeModal.show();
    }

    @if($canManageMembers)
    document.getElementById('confirmRemoveMember').addEventListener('click', function() {
        if (memberToRemove) {
            fetch(`/api/channels/{{ $channel->id }}/members/${memberToRemove}`, {
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
    });
    @endif

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