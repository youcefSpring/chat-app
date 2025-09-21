<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\CreateChannelRequest;
use App\Http\Requests\Channel\UpdateChannelRequest;
use App\Models\Channel;
use App\Models\Organization;
use App\Services\ChannelService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function __construct(
        private ChannelService $channelService,
        private AuditService $auditService
    ) {}

    public function index(Request $request, Organization $organization): JsonResponse
    {
        if ($request->user()->organization_id !== $organization->id) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $perPage = min($request->get('per_page', 20), 50);
        $type = $request->get('type');

        $query = $organization->channels()
            ->with(['creator:id,name', 'members'])
            ->withCount('members');

        if ($type) {
            $query->where('type', $type);
        }

        // Filter based on access permissions
        $user = $request->user();
        $query->where(function ($q) use ($user) {
            $q->where('type', 'public')
              ->orWhereHas('members', function ($memberQuery) use ($user) {
                  $memberQuery->where('user_id', $user->id);
              });
        });

        $channels = $query->orderBy('name')->paginate($perPage);

        // Add unread count and last message for each channel
        $channels->getCollection()->transform(function ($channel) use ($user) {
            $channel->unread_count = $this->getUnreadCount($channel, $user);
            $channel->last_message = $this->getLastMessage($channel);
            return $channel;
        });

        return response()->json($channels);
    }

    public function store(CreateChannelRequest $request, Organization $organization): JsonResponse
    {
        if ($request->user()->organization_id !== $organization->id) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            $channel = $this->channelService->createChannel(
                $organization,
                $request->user(),
                $request->validated()
            );

            $this->auditService->logChannelCreated($request->user(), $channel);

            return response()->json($channel->load(['creator', 'members']), 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'CHANNEL_CREATION_FAILED'
            ], 422);
        }
    }

    public function show(Request $request, Channel $channel): JsonResponse
    {
        if (!$this->userCanAccessChannel($channel, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $channel->load(['creator:id,name', 'members:id,name,avatar_url,status', 'organization:id,name']);

        return response()->json($channel);
    }

    public function update(UpdateChannelRequest $request, Channel $channel): JsonResponse
    {
        try {
            $oldData = $channel->toArray();

            $channel->update($request->validated());

            $this->auditService->logUserAction(
                $request->user(),
                'channel.updated',
                $channel,
                [
                    'old_data' => $oldData,
                    'new_data' => $channel->fresh()->toArray(),
                ]
            );

            return response()->json($channel->fresh()->load(['creator', 'members']));

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update channel',
                'code' => 'CHANNEL_UPDATE_FAILED'
            ], 422);
        }
    }

    public function destroy(Request $request, Channel $channel): JsonResponse
    {
        if (!$this->userCanDeleteChannel($channel, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            $this->channelService->deleteChannel($channel, $request->user());

            return response()->json(null, 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete channel',
                'code' => 'CHANNEL_DELETE_FAILED'
            ], 422);
        }
    }

    public function addMembers(Request $request, Channel $channel): JsonResponse
    {
        if (!$this->userCanManageMembers($channel, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $request->validate([
            'user_ids' => ['required', 'array', 'max:50'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        try {
            $this->channelService->inviteUsersToChannel($channel, $request->user_ids);

            $this->auditService->logUserAction(
                $request->user(),
                'channel.members_added',
                $channel,
                ['added_users' => $request->user_ids]
            );

            return response()->json([
                'message' => 'Members added successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add members',
                'code' => 'ADD_MEMBERS_FAILED'
            ], 422);
        }
    }

    public function removeMember(Request $request, Channel $channel, $userId): JsonResponse
    {
        $user = $request->user();

        // Check if user can remove this member
        if ($userId != $user->id && !$this->userCanManageMembers($channel, $user)) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            $memberToRemove = \App\Models\User::findOrFail($userId);

            $this->channelService->removeMemberFromChannel(
                $channel,
                $memberToRemove,
                $userId !== $user->id ? $user : null
            );

            return response()->json(null, 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove member',
                'code' => 'REMOVE_MEMBER_FAILED'
            ], 422);
        }
    }

    public function join(Request $request, Channel $channel): JsonResponse
    {
        if ($channel->type !== 'public') {
            return response()->json([
                'message' => 'Cannot join private channel',
                'code' => 'INVALID_CHANNEL_TYPE'
            ], 422);
        }

        try {
            $this->channelService->addMemberToChannel($channel, $request->user());

            return response()->json([
                'message' => 'Successfully joined channel'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'JOIN_FAILED'
            ], 422);
        }
    }

    public function leave(Request $request, Channel $channel): JsonResponse
    {
        try {
            $this->channelService->removeMemberFromChannel($channel, $request->user());

            return response()->json(null, 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to leave channel',
                'code' => 'LEAVE_FAILED'
            ], 422);
        }
    }

    private function userCanAccessChannel(Channel $channel, $user): bool
    {
        if ($channel->organization_id !== $user->organization_id) {
            return false;
        }

        if ($channel->type === 'public') {
            return true;
        }

        return $channel->members()->where('user_id', $user->id)->exists();
    }

    private function userCanDeleteChannel(Channel $channel, $user): bool
    {
        return $channel->created_by === $user->id || $user->role === 'admin';
    }

    private function userCanManageMembers(Channel $channel, $user): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $channel->members()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }

    private function getUnreadCount(Channel $channel, $user): int
    {
        $member = $channel->members()->where('user_id', $user->id)->first();

        if (!$member) {
            return 0;
        }

        return $channel->messages()
            ->where('created_at', '>', $member->pivot->last_read_at ?? $member->pivot->joined_at)
            ->count();
    }

    private function getLastMessage(Channel $channel)
    {
        return $channel->messages()
            ->with(['sender:id,name,avatar_url'])
            ->latest()
            ->first();
    }
}