<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\User;
use App\Models\Organization;
use App\Models\ChannelMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChannelService
{
    public function createChannel(Organization $organization, User $creator, array $channelData): Channel
    {
        $this->validateUserCanCreateChannel($organization, $creator, $channelData['type']);

        return DB::transaction(function () use ($organization, $creator, $channelData) {
            $channel = Channel::create([
                'organization_id' => $organization->id,
                'name' => $channelData['name'],
                'description' => $channelData['description'] ?? null,
                'type' => $channelData['type'],
                'created_by' => $creator->id,
                'settings' => $channelData['settings'] ?? [],
            ]);

            $this->addMemberToChannel($channel, $creator, 'admin');

            if ($channel->type === 'private' && isset($channelData['invited_users'])) {
                $this->inviteUsersToChannel($channel, $channelData['invited_users']);
            }

            event('channel.created', $channel->load(['creator', 'organization']));

            return $channel;
        });
    }

    public function addMemberToChannel(Channel $channel, User $user, string $role = 'member'): ChannelMember
    {
        $this->validateUserCanJoinChannel($channel, $user);

        $member = ChannelMember::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
            'notifications_enabled' => true,
        ]);

        event('channel.member_joined', [
            'channel' => $channel,
            'user' => $user,
            'role' => $role,
        ]);

        return $member;
    }

    public function removeMemberFromChannel(Channel $channel, User $user, User $removedBy = null): void
    {
        $this->validateUserCanLeaveOrBeRemoved($channel, $user, $removedBy);

        $channel->members()->where('user_id', $user->id)->delete();

        event('channel.member_left', [
            'channel' => $channel,
            'user' => $user,
            'removed_by' => $removedBy,
        ]);
    }

    public function updateMemberRole(Channel $channel, User $user, string $newRole, User $updatedBy): void
    {
        $this->validateUserCanManageChannelMembers($channel, $updatedBy);

        $channel->members()
            ->where('user_id', $user->id)
            ->update(['role' => $newRole]);

        event('channel.member_role_updated', [
            'channel' => $channel,
            'user' => $user,
            'new_role' => $newRole,
            'updated_by' => $updatedBy,
        ]);
    }

    public function inviteUsersToChannel(Channel $channel, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user && $user->organization_id === $channel->organization_id) {
                try {
                    $this->addMemberToChannel($channel, $user);
                } catch (ValidationException $e) {
                    continue;
                }
            }
        }
    }

    public function createDirectChannel(User $user1, User $user2): Channel
    {
        if ($user1->organization_id !== $user2->organization_id) {
            throw ValidationException::withMessages([
                'users' => ['Users must be in the same organization for direct messaging.'],
            ]);
        }

        if ($user1->id === $user2->id) {
            throw ValidationException::withMessages([
                'users' => ['Cannot create direct channel with yourself.'],
            ]);
        }

        $existingChannel = $this->findExistingDirectChannel($user1, $user2);
        if ($existingChannel) {
            return $existingChannel;
        }

        return DB::transaction(function () use ($user1, $user2) {
            $channel = Channel::create([
                'organization_id' => $user1->organization_id,
                'name' => $this->generateDirectChannelName($user1, $user2),
                'type' => 'direct',
                'created_by' => $user1->id,
                'settings' => [],
            ]);

            $this->addMemberToChannel($channel, $user1, 'member');
            $this->addMemberToChannel($channel, $user2, 'member');

            return $channel;
        });
    }

    public function updateChannelSettings(Channel $channel, User $user, array $settings): void
    {
        $this->validateUserCanManageChannel($channel, $user);

        $channel->update(['settings' => array_merge($channel->settings, $settings)]);

        event('channel.settings_updated', [
            'channel' => $channel,
            'updated_by' => $user,
            'settings' => $settings,
        ]);
    }

    public function deleteChannel(Channel $channel, User $user): void
    {
        $this->validateUserCanDeleteChannel($channel, $user);

        $channel->delete();

        event('channel.deleted', [
            'channel_id' => $channel->id,
            'organization_id' => $channel->organization_id,
            'deleted_by' => $user,
        ]);
    }

    private function validateUserCanCreateChannel(Organization $organization, User $user, string $type): void
    {
        if ($user->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'organization' => ['You can only create channels in your own organization.'],
            ]);
        }

        if ($type === 'private' && !in_array($user->role, ['admin', 'member'])) {
            throw ValidationException::withMessages([
                'type' => ['You do not have permission to create private channels.'],
            ]);
        }
    }

    private function validateUserCanJoinChannel(Channel $channel, User $user): void
    {
        if ($user->organization_id !== $channel->organization_id) {
            throw ValidationException::withMessages([
                'channel' => ['You can only join channels in your organization.'],
            ]);
        }

        if ($channel->members()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'channel' => ['User is already a member of this channel.'],
            ]);
        }
    }

    private function validateUserCanLeaveOrBeRemoved(Channel $channel, User $user, ?User $removedBy): void
    {
        if ($removedBy && $removedBy->id !== $user->id) {
            $this->validateUserCanManageChannelMembers($channel, $removedBy);
        }

        if (!$channel->members()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'channel' => ['User is not a member of this channel.'],
            ]);
        }
    }

    private function validateUserCanManageChannel(Channel $channel, User $user): void
    {
        if ($channel->created_by !== $user->id &&
            !$this->userIsChannelAdmin($channel, $user) &&
            $user->role !== 'admin') {
            throw ValidationException::withMessages([
                'channel' => ['You do not have permission to manage this channel.'],
            ]);
        }
    }

    private function validateUserCanManageChannelMembers(Channel $channel, User $user): void
    {
        if (!$this->userIsChannelAdmin($channel, $user) && $user->role !== 'admin') {
            throw ValidationException::withMessages([
                'channel' => ['You do not have permission to manage channel members.'],
            ]);
        }
    }

    private function validateUserCanDeleteChannel(Channel $channel, User $user): void
    {
        if ($channel->created_by !== $user->id && $user->role !== 'admin') {
            throw ValidationException::withMessages([
                'channel' => ['Only the channel creator or organization admin can delete this channel.'],
            ]);
        }
    }

    private function userIsChannelAdmin(Channel $channel, User $user): bool
    {
        return $channel->members()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }

    private function findExistingDirectChannel(User $user1, User $user2): ?Channel
    {
        return Channel::where('type', 'direct')
            ->where('organization_id', $user1->organization_id)
            ->whereHas('members', function ($query) use ($user1) {
                $query->where('user_id', $user1->id);
            })
            ->whereHas('members', function ($query) use ($user2) {
                $query->where('user_id', $user2->id);
            })
            ->first();
    }

    private function generateDirectChannelName(User $user1, User $user2): string
    {
        $names = [$user1->name, $user2->name];
        sort($names);
        return implode(', ', $names);
    }
}