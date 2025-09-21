@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="chat-layout d-flex">
    <!-- Sidebar -->
    <div class="sidebar text-white">
        <!-- Organization Header -->
        <div class="p-3 border-bottom border-secondary">
            <h5 class="mb-1">{{ auth()->user()->organization->name }}</h5>
            <small class="text-muted">{{ auth()->user()->organization->members()->count() }} members</small>
        </div>

        <!-- User Status -->
        <div class="p-3 border-bottom border-secondary">
            <div class="d-flex align-items-center">
                <div class="position-relative me-2">
                    <i class="bi bi-person-circle fs-4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle">
                        <span class="visually-hidden">Online</span>
                    </span>
                </div>
                <div>
                    <div class="fw-medium">{{ auth()->user()->name }}</div>
                    <small class="text-muted">Online</small>
                </div>
            </div>
        </div>

        <!-- Channels -->
        <div class="flex-grow-1 overflow-auto">
            <!-- Public Channels -->
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-uppercase text-muted small mb-0">Channels</h6>
                    @if(auth()->user()->role === 'admin')
                        <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#createChannelModal">
                            <i class="bi bi-plus"></i>
                        </button>
                    @endif
                </div>
                <div id="channelsList">
                    @foreach($channels->where('type', 'public') as $channel)
                        <div class="channel-item p-2 rounded mb-1 cursor-pointer {{ $activeChannel && $activeChannel->id === $channel->id ? 'bg-primary' : '' }}"
                             onclick="selectChannel({{ $channel->id }})">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-hash me-2"></i>
                                    <span>{{ $channel->name }}</span>
                                </div>
                                @if($channel->unread_count > 0)
                                    <span class="unread-badge">{{ $channel->unread_count }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Private Channels -->
            @if($channels->where('type', 'private')->count() > 0)
                <div class="p-3">
                    <h6 class="text-uppercase text-muted small mb-2">Private Channels</h6>
                    <div id="privateChannelsList">
                        @foreach($channels->where('type', 'private') as $channel)
                            <div class="channel-item p-2 rounded mb-1 cursor-pointer {{ $activeChannel && $activeChannel->id === $channel->id ? 'bg-primary' : '' }}"
                                 onclick="selectChannel({{ $channel->id }})">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-lock me-2"></i>
                                        <span>{{ $channel->name }}</span>
                                    </div>
                                    @if($channel->unread_count > 0)
                                        <span class="unread-badge">{{ $channel->unread_count }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Direct Messages -->
            <div class="p-3">
                <h6 class="text-uppercase text-muted small mb-2">Direct Messages</h6>
                <div id="directMessagesList">
                    @foreach($directChannels as $channel)
                        @php
                            $otherUser = $channel->members->where('id', '!=', auth()->id())->first();
                        @endphp
                        <div class="channel-item p-2 rounded mb-1 cursor-pointer {{ $activeChannel && $activeChannel->id === $channel->id ? 'bg-primary' : '' }}"
                             onclick="selectChannel({{ $channel->id }})">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-2">
                                        <i class="bi bi-person-circle"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle p-1
                                            @if($otherUser->presence_status === 'online') bg-success
                                            @elseif($otherUser->presence_status === 'away') bg-warning
                                            @elseif($otherUser->presence_status === 'dnd') bg-danger
                                            @else bg-secondary @endif
                                            border border-light rounded-circle">
                                        </span>
                                    </div>
                                    <span>{{ $otherUser->name }}</span>
                                </div>
                                @if($channel->unread_count > 0)
                                    <span class="unread-badge">{{ $channel->unread_count }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @if($activeChannel)
            <!-- Channel Header -->
            <div class="bg-white border-bottom p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-{{ $activeChannel->type === 'direct' ? 'person-circle' : ($activeChannel->type === 'private' ? 'lock' : 'hash') }} me-2"></i>
                        <h5 class="mb-0">
                            @if($activeChannel->type === 'direct')
                                {{ $activeChannel->members->where('id', '!=', auth()->id())->first()->name }}
                            @else
                                {{ $activeChannel->name }}
                            @endif
                        </h5>
                        @if($activeChannel->type !== 'direct')
                            <span class="badge bg-secondary ms-2">{{ $activeChannel->members->count() }} members</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center">
                        @if($activeChannel->type !== 'direct')
                            <button class="btn btn-sm btn-outline-secondary me-2" onclick="showChannelInfo()">
                                <i class="bi bi-info-circle"></i>
                            </button>
                        @endif
                        <button class="btn btn-sm btn-outline-secondary" onclick="startCall()">
                            <i class="bi bi-telephone"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="startVideoCall()">
                            <i class="bi bi-camera-video"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-grow-1 p-3 overflow-auto" id="messagesContainer">
                <div id="messagesList">
                    @foreach($messages as $message)
                        <div class="message-item" data-message-id="{{ $message->id }}">
                            <div class="d-flex">
                                <div class="position-relative me-2">
                                    <i class="bi bi-person-circle fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <strong class="me-2">{{ $message->user->name }}</strong>
                                        <small class="text-muted">{{ $message->created_at->format('h:i A') }}</small>
                                        @if($message->edited_at)
                                            <small class="text-muted ms-1">(edited)</small>
                                        @endif
                                    </div>
                                    <div class="message-content">
                                        {!! nl2br(e($message->content)) !!}
                                    </div>
                                    @if($message->attachments->count() > 0)
                                        <div class="mt-2">
                                            @foreach($message->attachments as $attachment)
                                                <div class="attachment-item border rounded p-2 mb-1">
                                                    <i class="bi bi-paperclip me-1"></i>
                                                    <a href="{{ route('attachments.download', $attachment) }}" class="text-decoration-none">
                                                        {{ $attachment->original_name }}
                                                    </a>
                                                    <small class="text-muted">({{ $attachment->size_formatted }})</small>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($message->reactions->count() > 0)
                                        <div class="mt-1">
                                            @foreach($message->reactions->groupBy('emoji') as $emoji => $reactions)
                                                <span class="badge bg-light text-dark me-1">
                                                    {{ $emoji }} {{ $reactions->count() }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div id="typingIndicator" class="typing-indicator" style="display: none;">
                    <span id="typingUsers"></span> is typing...
                </div>
            </div>

            <!-- Message Input -->
            <div class="bg-white border-top p-3">
                <form id="messageForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="channel_id" value="{{ $activeChannel->id }}">
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('fileInput').click()">
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <input type="file" id="fileInput" name="attachments[]" multiple style="display: none;" onchange="handleFileSelect(event)">
                        <input type="text"
                               class="form-control"
                               id="messageInput"
                               name="content"
                               placeholder="Type a message..."
                               autocomplete="off"
                               onkeydown="handleMessageKeydown(event)"
                               oninput="handleTyping()">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                    <div id="filePreview" class="mt-2" style="display: none;"></div>
                </form>
            </div>
        @else
            <!-- Welcome Screen -->
            <div class="d-flex align-items-center justify-content-center h-100">
                <div class="text-center">
                    <i class="bi bi-chat-dots display-1 text-muted mb-3"></i>
                    <h3 class="text-muted">Welcome to {{ auth()->user()->organization->name }}</h3>
                    <p class="text-muted">Select a channel to start messaging</p>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Create Channel Modal -->
@if(auth()->user()->role === 'admin')
<div class="modal fade" id="createChannelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Channel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createChannelForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="channelName" class="form-label">Channel Name</label>
                        <input type="text" class="form-control" id="channelName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="channelDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="channelDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="channelType" class="form-label">Channel Type</label>
                        <select class="form-select" id="channelType" name="type" required>
                            <option value="public">Public - Anyone in the organization can join</option>
                            <option value="private">Private - Invite only</option>
                        </select>
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
@endif
@endsection

@section('scripts')
<script>
    let currentChannelId = {{ $activeChannel ? $activeChannel->id : 'null' }};
    let typingTimer;
    let isTyping = false;

    function selectChannel(channelId) {
        window.location.href = `/dashboard?channel=${channelId}`;
    }

    function handleMessageKeydown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            document.getElementById('messageForm').dispatchEvent(new Event('submit'));
        }
    }

    function handleTyping() {
        if (!isTyping && currentChannelId) {
            isTyping = true;
            // Send typing indicator
            fetch(`/api/channels/${currentChannelId}/typing`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });
        }

        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            isTyping = false;
        }, 3000);
    }

    function handleFileSelect(event) {
        const files = event.target.files;
        const preview = document.getElementById('filePreview');

        if (files.length > 0) {
            let html = '<div class="border rounded p-2"><strong>Selected files:</strong><br>';
            for (let file of files) {
                html += `<small class="text-muted">${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</small><br>`;
            }
            html += '</div>';
            preview.innerHTML = html;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }

    function startCall() {
        if (currentChannelId) {
            // Implementation for starting audio call
            alert('Starting audio call...');
        }
    }

    function startVideoCall() {
        if (currentChannelId) {
            // Implementation for starting video call
            alert('Starting video call...');
        }
    }

    function showChannelInfo() {
        if (currentChannelId) {
            // Implementation for showing channel info
            alert('Channel information...');
        }
    }

    // Handle message form submission
    document.getElementById('messageForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const messageInput = document.getElementById('messageInput');

        if (!formData.get('content').trim() && formData.getAll('attachments[]').length === 0) {
            return;
        }

        fetch('/api/messages', {
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
                messageInput.value = '';
                document.getElementById('fileInput').value = '';
                document.getElementById('filePreview').style.display = 'none';
                // Append new message to the list
                appendMessage(data.message);
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
        });
    });

    // Handle create channel form
    @if(auth()->user()->role === 'admin')
    document.getElementById('createChannelForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

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
            }
        })
        .catch(error => {
            console.error('Error creating channel:', error);
        });
    });
    @endif

    function appendMessage(message) {
        const messagesList = document.getElementById('messagesList');
        const messageHtml = `
            <div class="message-item" data-message-id="${message.id}">
                <div class="d-flex">
                    <div class="position-relative me-2">
                        <i class="bi bi-person-circle fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <strong class="me-2">${message.user.name}</strong>
                            <small class="text-muted">${new Date(message.created_at).toLocaleTimeString()}</small>
                        </div>
                        <div class="message-content">
                            ${message.content.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                </div>
            </div>
        `;
        messagesList.insertAdjacentHTML('beforeend', messageHtml);
        document.getElementById('messagesContainer').scrollTop = document.getElementById('messagesContainer').scrollHeight;
    }

    // Auto-scroll to bottom on page load
    if (document.getElementById('messagesContainer')) {
        document.getElementById('messagesContainer').scrollTop = document.getElementById('messagesContainer').scrollHeight;
    }
</script>
@endsection