<?php

namespace App\Services;

use App\Models\Call;
use App\Models\CallParticipant;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CallService
{
    private const RINGING_TIMEOUT_1V1 = 30; // seconds
    private const RINGING_TIMEOUT_GROUP = 60; // seconds
    private const MAX_CALL_DURATION = 8 * 60 * 60; // 8 hours in seconds

    public function initiateCall(Channel $channel, User $initiator, string $type = 'audio'): Call
    {
        $this->validateUserCanInitiateCall($channel, $initiator);
        $this->validateNoActiveCallInChannel($channel);

        return DB::transaction(function () use ($channel, $initiator, $type) {
            $call = Call::create([
                'channel_id' => $channel->id,
                'initiated_by' => $initiator->id,
                'type' => $type,
                'status' => 'ringing',
                'sfu_room_id' => $this->generateSFURoomId(),
                'metadata' => [],
            ]);

            $this->addParticipant($call, $initiator, 'joined');

            if ($channel->type === 'direct') {
                $this->inviteDirectChannelMember($call, $channel, $initiator);
            } else {
                $this->inviteChannelMembers($call, $channel);
            }

            event('call.initiated', $call->load(['channel', 'initiator', 'participants']));

            $this->scheduleCallTimeout($call);

            return $call;
        });
    }

    public function joinCall(Call $call, User $user): void
    {
        $this->validateUserCanJoinCall($call, $user);
        $this->validateCallIsActive($call);

        $participant = $call->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update([
                'status' => 'joined',
                'joined_at' => now(),
            ]);
        } else {
            $this->addParticipant($call, $user, 'joined');
        }

        if ($call->status === 'ringing') {
            $call->update([
                'status' => 'active',
                'started_at' => now(),
            ]);
        }

        event('call.participant_joined', [
            'call' => $call,
            'user' => $user,
        ]);
    }

    public function leaveCall(Call $call, User $user): void
    {
        $this->validateUserIsInCall($call, $user);

        $participant = $call->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update([
                'status' => 'left',
                'left_at' => now(),
            ]);
        }

        event('call.participant_left', [
            'call' => $call,
            'user' => $user,
        ]);

        $this->checkIfCallShouldEnd($call);
    }

    public function rejectCall(Call $call, User $user): void
    {
        $this->validateUserCanRejectCall($call, $user);

        $participant = $call->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update(['status' => 'rejected']);
        }

        event('call.participant_rejected', [
            'call' => $call,
            'user' => $user,
        ]);

        if ($call->channel->type === 'direct') {
            $this->endCall($call, $user);
        }
    }

    public function endCall(Call $call, User $user): void
    {
        $this->validateUserCanEndCall($call, $user);

        $call->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        $call->participants()
            ->whereNull('left_at')
            ->update([
                'status' => 'left',
                'left_at' => now(),
            ]);

        event('call.ended', [
            'call' => $call,
            'ended_by' => $user,
        ]);
    }

    public function enableScreenShare(Call $call, User $user): void
    {
        $this->validateUserIsInCall($call, $user);
        $this->validateCallIsActive($call);

        $metadata = $call->metadata;
        $metadata['screen_share'] = [
            'enabled' => true,
            'shared_by' => $user->id,
            'started_at' => now()->toISOString(),
        ];

        $call->update(['metadata' => $metadata]);

        event('call.screen_share_started', [
            'call' => $call,
            'user' => $user,
        ]);
    }

    public function disableScreenShare(Call $call, User $user): void
    {
        $this->validateUserIsInCall($call, $user);

        $metadata = $call->metadata;
        if (isset($metadata['screen_share']) && $metadata['screen_share']['shared_by'] === $user->id) {
            $metadata['screen_share']['enabled'] = false;
            $metadata['screen_share']['ended_at'] = now()->toISOString();

            $call->update(['metadata' => $metadata]);

            event('call.screen_share_ended', [
                'call' => $call,
                'user' => $user,
            ]);
        }
    }

    public function getActiveCallInChannel(Channel $channel): ?Call
    {
        return Call::where('channel_id', $channel->id)
            ->whereIn('status', ['ringing', 'active'])
            ->first();
    }

    private function validateUserCanInitiateCall(Channel $channel, User $user): void
    {
        if (!$this->userCanAccessChannel($channel, $user)) {
            throw ValidationException::withMessages([
                'channel' => ['You do not have permission to initiate calls in this channel.'],
            ]);
        }
    }

    private function validateNoActiveCallInChannel(Channel $channel): void
    {
        if ($this->getActiveCallInChannel($channel)) {
            throw ValidationException::withMessages([
                'channel' => ['There is already an active call in this channel.'],
            ]);
        }
    }

    private function validateUserCanJoinCall(Call $call, User $user): void
    {
        if (!$this->userCanAccessChannel($call->channel, $user)) {
            throw ValidationException::withMessages([
                'call' => ['You do not have permission to join this call.'],
            ]);
        }
    }

    private function validateCallIsActive(Call $call): void
    {
        if (!in_array($call->status, ['ringing', 'active'])) {
            throw ValidationException::withMessages([
                'call' => ['This call is no longer active.'],
            ]);
        }
    }

    private function validateUserIsInCall(Call $call, User $user): void
    {
        if (!$call->participants()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'call' => ['You are not a participant in this call.'],
            ]);
        }
    }

    private function validateUserCanRejectCall(Call $call, User $user): void
    {
        if ($call->status !== 'ringing') {
            throw ValidationException::withMessages([
                'call' => ['You can only reject calls that are still ringing.'],
            ]);
        }

        $this->validateUserIsInCall($call, $user);
    }

    private function validateUserCanEndCall(Call $call, User $user): void
    {
        if ($call->initiated_by !== $user->id && !$this->userIsChannelAdmin($call->channel, $user)) {
            throw ValidationException::withMessages([
                'call' => ['Only the call initiator or channel admin can end the call.'],
            ]);
        }
    }

    private function userCanAccessChannel(Channel $channel, User $user): bool
    {
        if ($channel->type === 'public') {
            return $channel->organization_id === $user->organization_id;
        }

        return $channel->members()->where('user_id', $user->id)->exists();
    }

    private function userIsChannelAdmin(Channel $channel, User $user): bool
    {
        return $channel->members()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }

    private function addParticipant(Call $call, User $user, string $status): void
    {
        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $user->id,
            'status' => $status,
            'joined_at' => $status === 'joined' ? now() : null,
        ]);
    }

    private function inviteDirectChannelMember(Call $call, Channel $channel, User $initiator): void
    {
        $otherMember = $channel->members()
            ->where('user_id', '!=', $initiator->id)
            ->first();

        if ($otherMember) {
            $this->addParticipant($call, $otherMember->user, 'invited');
        }
    }

    private function inviteChannelMembers(Call $call, Channel $channel): void
    {
        $members = $channel->members()
            ->where('user_id', '!=', $call->initiated_by)
            ->get();

        foreach ($members as $member) {
            $this->addParticipant($call, $member->user, 'invited');
        }
    }

    private function checkIfCallShouldEnd(Call $call): void
    {
        $activeParticipants = $call->participants()
            ->whereIn('status', ['joined'])
            ->count();

        if ($activeParticipants === 0) {
            $call->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

            event('call.auto_ended', ['call' => $call]);
        }
    }

    private function generateSFURoomId(): string
    {
        return 'room_' . Str::random(16);
    }

    private function scheduleCallTimeout(Call $call): void
    {
        $timeout = $call->channel->type === 'direct'
            ? self::RINGING_TIMEOUT_1V1
            : self::RINGING_TIMEOUT_GROUP;

        dispatch(function () use ($call) {
            $call->refresh();
            if ($call->status === 'ringing') {
                $call->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                ]);

                event('call.timeout', ['call' => $call]);
            }
        })->delay(now()->addSeconds($timeout));
    }
}