<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class PresenceService
{
    private const ONLINE_THRESHOLD_MINUTES = 5;
    private const AWAY_THRESHOLD_MINUTES = 10;
    private const HEARTBEAT_KEY_PREFIX = 'user_heartbeat:';
    private const PRESENCE_KEY_PREFIX = 'user_presence:';

    public function updateUserPresence(User $user, string $status = null): void
    {
        $currentTime = now();

        if ($status) {
            $user->update([
                'status' => $status,
                'last_seen_at' => $currentTime,
            ]);
        } else {
            $user->update(['last_seen_at' => $currentTime]);
        }

        $this->updateRedisPresence($user, $status ?? $user->status);

        event('user.presence_updated', [
            'user_id' => $user->id,
            'status' => $user->status,
            'last_seen_at' => $currentTime,
        ]);
    }

    public function handleHeartbeat(User $user): void
    {
        $heartbeatKey = self::HEARTBEAT_KEY_PREFIX . $user->id;

        Redis::setex($heartbeatKey, 60, now()->timestamp);

        if ($user->status !== 'online' && $user->status !== 'dnd') {
            $this->updateUserPresence($user, 'online');
        }
    }

    public function setUserAway(User $user): void
    {
        if ($user->status === 'online') {
            $this->updateUserPresence($user, 'away');
        }
    }

    public function setUserOffline(User $user): void
    {
        $this->updateUserPresence($user, 'offline');
        $this->removeFromRedisPresence($user);
    }

    public function getUsersPresenceInOrganization(int $organizationId): array
    {
        $users = User::where('organization_id', $organizationId)
            ->select(['id', 'name', 'status', 'last_seen_at'])
            ->get();

        $presence = [];
        foreach ($users as $user) {
            $presence[$user->id] = [
                'name' => $user->name,
                'status' => $this->getActualUserStatus($user),
                'last_seen_at' => $user->last_seen_at,
            ];
        }

        return $presence;
    }

    public function getOnlineUsersInChannel(int $channelId): array
    {
        return Cache::remember("channel_online_users:{$channelId}", 60, function () use ($channelId) {
            return User::whereHas('channelMembers', function ($query) use ($channelId) {
                $query->where('channel_id', $channelId);
            })
            ->whereIn('status', ['online', 'away'])
            ->where('last_seen_at', '>', now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES))
            ->select(['id', 'name', 'status', 'avatar_url'])
            ->get()
            ->toArray();
        });
    }

    public function getTypingUsers(int $channelId): array
    {
        $typingKey = "typing:channel:{$channelId}";
        $typingUsers = Redis::hgetall($typingKey);

        $result = [];
        foreach ($typingUsers as $userId => $timestamp) {
            if (now()->timestamp - $timestamp < 5) {
                $user = User::find($userId);
                if ($user) {
                    $result[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                    ];
                }
            } else {
                Redis::hdel($typingKey, $userId);
            }
        }

        return $result;
    }

    public function setUserTyping(User $user, int $channelId): void
    {
        $typingKey = "typing:channel:{$channelId}";
        Redis::hset($typingKey, $user->id, now()->timestamp);
        Redis::expire($typingKey, 10);

        event('user.typing', [
            'user_id' => $user->id,
            'channel_id' => $channelId,
            'user_name' => $user->name,
        ]);
    }

    public function setUserStoppedTyping(User $user, int $channelId): void
    {
        $typingKey = "typing:channel:{$channelId}";
        Redis::hdel($typingKey, $user->id);

        event('user.stopped_typing', [
            'user_id' => $user->id,
            'channel_id' => $channelId,
        ]);
    }

    public function cleanupStalePresence(): void
    {
        $staleUsers = User::where('status', '!=', 'offline')
            ->where('last_seen_at', '<', now()->subMinutes(self::AWAY_THRESHOLD_MINUTES))
            ->get();

        foreach ($staleUsers as $user) {
            $heartbeatKey = self::HEARTBEAT_KEY_PREFIX . $user->id;

            if (!Redis::exists($heartbeatKey)) {
                $this->setUserOffline($user);
            }
        }
    }

    public function getUserPresenceStatus(User $user): string
    {
        return $this->getActualUserStatus($user);
    }

    private function getActualUserStatus(User $user): string
    {
        if ($user->status === 'dnd' || $user->status === 'offline') {
            return $user->status;
        }

        if (!$user->last_seen_at) {
            return 'offline';
        }

        $minutesSinceLastSeen = $user->last_seen_at->diffInMinutes(now());

        if ($minutesSinceLastSeen > self::AWAY_THRESHOLD_MINUTES) {
            return 'offline';
        }

        if ($minutesSinceLastSeen > self::ONLINE_THRESHOLD_MINUTES) {
            return 'away';
        }

        return 'online';
    }

    private function updateRedisPresence(User $user, string $status): void
    {
        $presenceKey = self::PRESENCE_KEY_PREFIX . $user->id;
        $presenceData = [
            'status' => $status,
            'last_seen_at' => now()->timestamp,
            'organization_id' => $user->organization_id,
        ];

        Redis::hmset($presenceKey, $presenceData);
        Redis::expire($presenceKey, 3600); // 1 hour
    }

    private function removeFromRedisPresence(User $user): void
    {
        $presenceKey = self::PRESENCE_KEY_PREFIX . $user->id;
        $heartbeatKey = self::HEARTBEAT_KEY_PREFIX . $user->id;

        Redis::del($presenceKey);
        Redis::del($heartbeatKey);
    }
}