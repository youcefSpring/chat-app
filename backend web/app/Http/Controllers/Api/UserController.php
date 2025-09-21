<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Organization;
use App\Services\PresenceService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private PresenceService $presenceService,
        private AuditService $auditService
    ) {}

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['organization']);

        return response()->json($user);
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $oldData = $user->toArray();

            $user->update($request->validated());

            $this->auditService->logUserAction(
                $user,
                'user.profile_updated',
                $user,
                [
                    'old_data' => $oldData,
                    'new_data' => $user->fresh()->toArray(),
                ]
            );

            return response()->json($user->fresh());

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user profile',
                'code' => 'UPDATE_FAILED'
            ], 422);
        }
    }

    public function updatePresence(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:online,away,dnd,offline']
        ]);

        try {
            $user = $request->user();

            $this->presenceService->updateUserPresence($user, $request->status);

            return response()->json(['status' => $request->status]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update presence',
                'code' => 'PRESENCE_UPDATE_FAILED'
            ], 422);
        }
    }

    public function heartbeat(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $this->presenceService->handleHeartbeat($user);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Heartbeat failed',
                'code' => 'HEARTBEAT_FAILED'
            ], 422);
        }
    }

    public function organizationUsers(Request $request, Organization $organization): JsonResponse
    {
        if ($request->user()->organization_id !== $organization->id) {
            return response()->json([
                'message' => 'Access denied to organization users',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $perPage = min($request->get('per_page', 20), 50);
        $search = $request->get('search');

        $query = $organization->users()
            ->select(['id', 'name', 'email', 'role', 'status', 'avatar_url', 'last_seen_at']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('name')->paginate($perPage);

        return response()->json($users);
    }

    public function user(Request $request, Organization $organization, $userId): JsonResponse
    {
        if ($request->user()->organization_id !== $organization->id) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $user = $organization->users()
            ->select(['id', 'name', 'email', 'role', 'status', 'avatar_url', 'last_seen_at', 'created_at'])
            ->findOrFail($userId);

        return response()->json($user);
    }

    public function setTyping(Request $request): JsonResponse
    {
        $request->validate([
            'channel_id' => ['required', 'integer', 'exists:channels,id']
        ]);

        try {
            $user = $request->user();
            $channelId = $request->channel_id;

            // Verify user has access to channel
            $channel = \App\Models\Channel::findOrFail($channelId);

            if ($channel->type === 'public') {
                if ($channel->organization_id !== $user->organization_id) {
                    return response()->json(['message' => 'Access denied'], 403);
                }
            } else {
                if (!$channel->members()->where('user_id', $user->id)->exists()) {
                    return response()->json(['message' => 'Access denied'], 403);
                }
            }

            $this->presenceService->setUserTyping($user, $channelId);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to set typing status',
                'code' => 'TYPING_FAILED'
            ], 422);
        }
    }

    public function stopTyping(Request $request): JsonResponse
    {
        $request->validate([
            'channel_id' => ['required', 'integer', 'exists:channels,id']
        ]);

        try {
            $user = $request->user();
            $channelId = $request->channel_id;

            $this->presenceService->setUserStoppedTyping($user, $channelId);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to stop typing status',
                'code' => 'STOP_TYPING_FAILED'
            ], 422);
        }
    }

    public function onlineUsers(Request $request, $channelId): JsonResponse
    {
        try {
            $users = $this->presenceService->getOnlineUsersInChannel($channelId);

            return response()->json(['data' => $users]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get online users',
                'code' => 'ONLINE_USERS_FAILED'
            ], 422);
        }
    }
}