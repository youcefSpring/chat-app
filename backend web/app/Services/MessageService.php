<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Channel;
use App\Models\User;
use App\Models\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class MessageService
{
    private const EDIT_WINDOW_MINUTES = 15;

    public function sendMessage(Channel $channel, User $user, array $messageData): Message
    {
        $this->validateUserCanPostToChannel($channel, $user);

        return DB::transaction(function () use ($channel, $user, $messageData) {
            $message = Message::create([
                'channel_id' => $channel->id,
                'sender_id' => $user->id,
                'body' => $messageData['body'],
                'type' => $messageData['type'] ?? 'text',
                'reply_to_message_id' => $messageData['reply_to_message_id'] ?? null,
                'metadata' => $messageData['metadata'] ?? [],
            ]);

            if (isset($messageData['attachments'])) {
                $this->attachFiles($message, $messageData['attachments']);
            }

            event('message.sent', $message->load(['sender', 'channel', 'attachments']));

            $this->updateChannelLastActivity($channel);

            return $message;
        });
    }

    public function editMessage(Message $message, User $user, string $newBody): Message
    {
        $this->validateUserCanEditMessage($message, $user);

        $message->update([
            'body' => $newBody,
            'edited_at' => now(),
        ]);

        event('message.edited', $message->load(['sender', 'channel']));

        return $message;
    }

    public function deleteMessage(Message $message, User $user): void
    {
        $this->validateUserCanDeleteMessage($message, $user);

        $message->delete();

        event('message.deleted', [
            'message_id' => $message->id,
            'channel_id' => $message->channel_id,
        ]);
    }

    public function addReaction(Message $message, User $user, string $reaction): void
    {
        $this->validateUserCanAccessMessage($message, $user);

        $message->reactions()->updateOrCreate(
            [
                'user_id' => $user->id,
                'reaction' => $reaction,
            ],
            ['created_at' => now()]
        );

        event('reaction.added', [
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => $reaction,
        ]);
    }

    public function removeReaction(Message $message, User $user, string $reaction): void
    {
        $this->validateUserCanAccessMessage($message, $user);

        $message->reactions()
            ->where('user_id', $user->id)
            ->where('reaction', $reaction)
            ->delete();

        event('reaction.removed', [
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => $reaction,
        ]);
    }

    public function getThreadMessages(Message $parentMessage, User $user): array
    {
        $this->validateUserCanAccessMessage($parentMessage, $user);

        return Message::where('reply_to_message_id', $parentMessage->id)
            ->with(['sender', 'attachments', 'reactions'])
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    public function markChannelAsRead(Channel $channel, User $user): void
    {
        $this->validateUserCanAccessChannel($channel, $user);

        $channel->members()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    private function validateUserCanPostToChannel(Channel $channel, User $user): void
    {
        if (!$this->userCanAccessChannel($channel, $user)) {
            throw ValidationException::withMessages([
                'channel' => ['You do not have permission to post to this channel.'],
            ]);
        }
    }

    private function validateUserCanEditMessage(Message $message, User $user): void
    {
        if ($message->sender_id !== $user->id) {
            throw ValidationException::withMessages([
                'message' => ['You can only edit your own messages.'],
            ]);
        }

        if ($message->created_at->diffInMinutes(now()) > self::EDIT_WINDOW_MINUTES) {
            throw ValidationException::withMessages([
                'message' => ['Messages can only be edited within 15 minutes of sending.'],
            ]);
        }
    }

    private function validateUserCanDeleteMessage(Message $message, User $user): void
    {
        if ($message->sender_id !== $user->id && !$this->userIsChannelAdmin($message->channel, $user)) {
            throw ValidationException::withMessages([
                'message' => ['You can only delete your own messages.'],
            ]);
        }
    }

    private function validateUserCanAccessMessage(Message $message, User $user): void
    {
        if (!$this->userCanAccessChannel($message->channel, $user)) {
            throw ValidationException::withMessages([
                'message' => ['You do not have permission to access this message.'],
            ]);
        }
    }

    private function validateUserCanAccessChannel(Channel $channel, User $user): void
    {
        if (!$this->userCanAccessChannel($channel, $user)) {
            throw ValidationException::withMessages([
                'channel' => ['You do not have permission to access this channel.'],
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

    private function attachFiles(Message $message, array $attachments): void
    {
        foreach ($attachments as $attachmentData) {
            Attachment::create([
                'message_id' => $message->id,
                'uploader_id' => $message->sender_id,
                'original_name' => $attachmentData['original_name'],
                'file_path' => $attachmentData['file_path'],
                'thumbnail_path' => $attachmentData['thumbnail_path'] ?? null,
                'mime_type' => $attachmentData['mime_type'],
                'size' => $attachmentData['size'],
                'metadata' => $attachmentData['metadata'] ?? [],
            ]);
        }
    }

    private function updateChannelLastActivity(Channel $channel): void
    {
        $channel->touch();
    }
}