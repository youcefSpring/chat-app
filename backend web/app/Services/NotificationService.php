<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\Message;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendMessageNotification(Message $message): void
    {
        $channel = $message->channel;
        $sender = $message->sender;

        $recipients = $this->getNotificationRecipients($channel, $sender);

        foreach ($recipients as $recipient) {
            if ($this->shouldNotifyUser($recipient, $channel, $message)) {
                $this->createNotification($recipient, 'message', $message);
                $this->sendPushNotification($recipient, $message);
            }
        }
    }

    public function sendMentionNotification(Message $message, array $mentionedUserIds): void
    {
        foreach ($mentionedUserIds as $userId) {
            $user = User::find($userId);
            if ($user && $this->userCanAccessChannel($message->channel, $user)) {
                $this->createNotification($user, 'mention', $message);
                $this->sendPushNotification($user, $message, 'mention');
            }
        }
    }

    public function sendChannelInvitationNotification(User $user, Channel $channel, User $invitedBy): void
    {
        $notification = $this->createNotification($user, 'channel_invitation', $channel, [
            'invited_by' => $invitedBy->name,
            'channel_name' => $channel->name,
        ]);

        $this->sendPushNotification($user, $notification, 'channel_invitation');
    }

    public function sendCallNotification(User $user, $call): void
    {
        $notification = $this->createNotification($user, 'call', $call, [
            'call_type' => $call->type,
            'channel_name' => $call->channel->name,
            'initiated_by' => $call->initiator->name,
        ]);

        $this->sendPushNotification($user, $notification, 'call');
    }

    public function markAsRead(User $user, string $notificationId): void
    {
        Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function getUserNotifications(User $user, int $limit = 50): array
    {
        return Notification::where('user_id', $user->id)
            ->with(['notifiable'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function cleanupOldNotifications(int $daysToKeep = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }

    private function getNotificationRecipients(Channel $channel, User $sender): array
    {
        return $channel->members()
            ->where('user_id', '!=', $sender->id)
            ->where('notifications_enabled', true)
            ->get()
            ->pluck('user')
            ->toArray();
    }

    private function shouldNotifyUser(User $user, Channel $channel, Message $message): bool
    {
        if ($user->status === 'dnd') {
            return false;
        }

        $channelMember = $channel->members()->where('user_id', $user->id)->first();
        if (!$channelMember || !$channelMember->notifications_enabled) {
            return false;
        }

        if ($this->userIsOnline($user)) {
            return false;
        }

        return true;
    }

    private function userCanAccessChannel(Channel $channel, User $user): bool
    {
        if ($channel->type === 'public') {
            return $channel->organization_id === $user->organization_id;
        }

        return $channel->members()->where('user_id', $user->id)->exists();
    }

    private function userIsOnline(User $user): bool
    {
        return $user->status === 'online' &&
               $user->last_seen_at &&
               $user->last_seen_at->diffInMinutes(now()) < 5;
    }

    private function createNotification(User $user, string $type, $notifiable, array $additionalData = []): Notification
    {
        $data = $this->buildNotificationData($type, $notifiable, $additionalData);

        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'data' => $data,
        ]);
    }

    private function buildNotificationData(string $type, $notifiable, array $additionalData): array
    {
        $data = $additionalData;

        switch ($type) {
            case 'message':
                $data = array_merge($data, [
                    'message_body' => substr($notifiable->body, 0, 100),
                    'sender_name' => $notifiable->sender->name,
                    'channel_name' => $notifiable->channel->name,
                    'channel_type' => $notifiable->channel->type,
                ]);
                break;

            case 'mention':
                $data = array_merge($data, [
                    'message_body' => substr($notifiable->body, 0, 100),
                    'sender_name' => $notifiable->sender->name,
                    'channel_name' => $notifiable->channel->name,
                ]);
                break;

            case 'channel_invitation':
                $data = array_merge($data, [
                    'channel_type' => $notifiable->type,
                    'channel_description' => $notifiable->description,
                ]);
                break;

            case 'call':
                $data = array_merge($data, [
                    'call_status' => $notifiable->status,
                ]);
                break;
        }

        return $data;
    }

    private function sendPushNotification(User $user, $payload, string $type = 'message'): void
    {
        if ($this->userIsOnline($user)) {
            $this->sendWebSocketNotification($user, $payload, $type);
        } else {
            $this->sendMobilePushNotification($user, $payload, $type);
        }
    }

    private function sendWebSocketNotification(User $user, $payload, string $type): void
    {
        event('notification.received', [
            'user_id' => $user->id,
            'type' => $type,
            'payload' => $payload,
        ]);
    }

    private function sendMobilePushNotification(User $user, $payload, string $type): void
    {
        $title = $this->buildPushTitle($type, $payload);
        $body = $this->buildPushBody($type, $payload);

        Log::info("Sending push notification to user {$user->id}: {$title} - {$body}");
    }

    private function buildPushTitle(string $type, $payload): string
    {
        switch ($type) {
            case 'message':
                return isset($payload->data['channel_type']) && $payload->data['channel_type'] === 'direct'
                    ? $payload->data['sender_name']
                    : "#{$payload->data['channel_name']}";

            case 'mention':
                return "Mentioned in #{$payload->data['channel_name']}";

            case 'channel_invitation':
                return "Channel Invitation";

            case 'call':
                return $payload->data['call_type'] === 'video' ? "Video Call" : "Audio Call";

            default:
                return "Notification";
        }
    }

    private function buildPushBody(string $type, $payload): string
    {
        switch ($type) {
            case 'message':
            case 'mention':
                return "{$payload->data['sender_name']}: {$payload->data['message_body']}";

            case 'channel_invitation':
                return "{$payload->data['invited_by']} invited you to join #{$payload->data['channel_name']}";

            case 'call':
                return "{$payload->data['initiated_by']} is calling...";

            default:
                return "You have a new notification";
        }
    }
}