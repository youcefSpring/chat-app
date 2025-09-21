@extends('layouts.app')

@section('title', 'Create Channel')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('channels.index') }}" class="btn btn-outline-secondary btn-sm me-3">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h5 class="mb-0">Create New Channel</h5>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('channels.store') }}" method="POST" id="createChannelForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Channel Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}" required>
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
                                        <option value="public" {{ old('type') === 'public' ? 'selected' : '' }}>
                                            Public - Anyone in the organization can join
                                        </option>
                                        <option value="private" {{ old('type') === 'private' ? 'selected' : '' }}>
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
                                      placeholder="What's this channel about?">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" id="membersSection" style="display: none;">
                            <label class="form-label">Add Members</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                @foreach($organizationMembers as $member)
                                    @if($member->id !== auth()->id())
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="members[]" value="{{ $member->id }}"
                                                   id="member{{ $member->id }}"
                                                   {{ in_array($member->id, old('members', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="member{{ $member->id }}">
                                                {{ $member->name }} <small class="text-muted">({{ $member->email }})</small>
                                            </label>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('channels.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Channel</button>
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
    // Show/hide members section based on channel type
    document.getElementById('type').addEventListener('change', function() {
        const membersSection = document.getElementById('membersSection');
        if (this.value === 'private') {
            membersSection.style.display = 'block';
        } else {
            membersSection.style.display = 'none';
        }
    });

    // Auto-format channel name
    document.getElementById('name').addEventListener('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-');
    });

    // Handle form submission
    document.getElementById('createChannelForm').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
        submitBtn.disabled = true;
    });
</script>
@endsection