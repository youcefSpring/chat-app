@extends('layouts.app')

@section('title', 'Edit Channel - ' . $channel->name)

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('channels.show', $channel) }}" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h5 class="mb-0">Edit Channel: {{ $channel->name }}</h5>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('channels.update', $channel) }}" method="POST" id="editChannelForm">
                        @csrf
                        @method('PATCH')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Channel Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $channel->name) }}" required>
                                    <div class="form-text">Use lowercase letters, numbers, and hyphens</div>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Channel Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror"
                                            id="type" name="type" required>
                                        <option value="public" {{ old('type', $channel->type) === 'public' ? 'selected' : '' }}>
                                            Public - Anyone in the organization can join
                                        </option>
                                        <option value="private" {{ old('type', $channel->type) === 'private' ? 'selected' : '' }}>
                                            Private - Invite only
                                        </option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      placeholder="What's this channel about?">{{ old('description', $channel->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Channel Statistics -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="mb-0">{{ $channel->members->count() }}</h5>
                                        <small class="text-muted">Members</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="mb-0">{{ $channel->messages->count() }}</h5>
                                        <small class="text-muted">Messages</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="mb-0">{{ $channel->created_at->diffForHumans() }}</h5>
                                        <small class="text-muted">Created</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="{{ route('channels.show', $channel) }}" class="btn btn-secondary">Cancel</a>
                                <a href="{{ route('channels.members', $channel) }}" class="btn btn-outline-info">
                                    <i class="bi bi-people me-1"></i>Manage Members
                                </a>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-danger me-2" onclick="deleteChannel()">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check me-1"></i>Update Channel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Channel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete this channel?</strong></p>
                <p class="text-muted">This action will permanently delete:</p>
                <ul class="text-muted">
                    <li>All messages in this channel</li>
                    <li>All file attachments</li>
                    <li>Channel membership data</li>
                </ul>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('channels.destroy', $channel) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Channel</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-format channel name
    document.getElementById('name').addEventListener('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-');
    });

    // Handle form submission
    document.getElementById('editChannelForm').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        submitBtn.disabled = true;
    });

    function deleteChannel() {
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>
@endsection