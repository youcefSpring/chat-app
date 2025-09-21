<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\CreateMessageRequest;
use App\Http\Requests\Message\UpdateMessageRequest;
use App\Http\Requests\Reaction\AddReactionRequest;
use App\Models\Channel;
use App\Models\Message;
use App\Services\MessageService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private MessageService $messageService,
        private NotificationService $notificationService
    ) {}

    public function index(Request $request, Channel $channel): JsonResponse
    {
        if (!$this->userCanAccessChannel($channel, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $perPage = min($request->get('per_page', 50), 50);
        $before = $request->get('before');
        $after = $request->get('after');

        $query = $channel->messages()
            ->with([
                'sender:id,name,avatar_url',
                'attachments',
                'reactions.user:id,name',
                'replyTo:id,body,sender_id',
                'replyTo.sender:id,name'
            ])
            ->whereNull('deleted_at');

        if ($before) {
            $query->where('id', '<', $before);
        }

        if ($after) {
            $query->where('id', '>', $after);
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->limit($perPage)
            ->get()
            ->reverse()
            ->values();

        // Group reactions by emoji
        $messages->transform(function ($message) {
            $groupedReactions = $message->reactions->groupBy('reaction')->map(function ($reactions, $emoji) {
                return [
                    'reaction' => $emoji,
                    'count' => $reactions->count(),
                    'users' => $reactions->map(function ($reaction) {
                        return [
                            'id' => $reaction->user->id,
                            'name' => $reaction->user->name,
                        ];
                    })->values()
                ];
            })->values();

            $message->reactions = $groupedReactions;
            return $message;
        });

        return response()->json([
            'data' => $messages,
            'meta' => [
                'has_more' => $messages->count() === $perPage
            ]
        ]);
    }

    public function store(CreateMessageRequest $request, Channel $channel): JsonResponse
    {
        try {
            $message = $this->messageService->sendMessage(
                $channel,
                $request->user(),
                $request->validated()
            );

            // Send notifications for mentions
            if (isset($request->validated()['metadata']['mentions'])) {
                $this->notificationService->sendMentionNotification(
                    $message,
                    $request->validated()['metadata']['mentions']
                );
            } else {
                // Send regular message notification
                $this->notificationService->sendMessageNotification($message);
            }

            $message->load([
                'sender:id,name,avatar_url',
                'attachments',
                'reactions',
                'channel:id,name'
            ]);

            return response()->json($message, 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'MESSAGE_SEND_FAILED'
            ], 422);
        }
    }

    public function show(Request $request, Message $message): JsonResponse
    {
        if (!$this->userCanAccessMessage($message, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $message->load([
            'sender:id,name,avatar_url',
            'attachments',
            'reactions.user:id,name',
            'channel:id,name',
            'replyTo:id,body,sender_id',
            'replyTo.sender:id,name'
        ]);

        return response()->json($message);
    }

    public function update(UpdateMessageRequest $request, Message $message): JsonResponse
    {
        try {
            $updatedMessage = $this->messageService->editMessage(
                $message,
                $request->user(),
                $request->validated()['body']
            );

            $updatedMessage->load([
                'sender:id,name,avatar_url',
                'attachments',
                'reactions',
                'channel:id,name'
            ]);

            return response()->json($updatedMessage);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'MESSAGE_UPDATE_FAILED'
            ], 422);
        }
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        if (!$this->userCanDeleteMessage($message, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            $this->messageService->deleteMessage($message, $request->user());

            return response()->json(null, 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete message',
                'code' => 'MESSAGE_DELETE_FAILED'
            ], 422);
        }
    }

    public function addReaction(AddReactionRequest $request, Message $message): JsonResponse
    {
        if (!$this->userCanAccessMessage($message, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            $this->messageService->addReaction(
                $message,
                $request->user(),
                $request->validated()['reaction']
            );

            return response()->json([
                'message' => 'Reaction added'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add reaction',
                'code' => 'REACTION_ADD_FAILED'
            ], 422);
        }
    }

    public function removeReaction(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'reaction' => ['required', 'string', 'max:50']
        ]);

        if (!$this->userCanAccessMessage($message, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            $this->messageService->removeReaction(
                $message,
                $request->user(),
                $request->reaction
            );

            return response()->json(null, 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove reaction',
                'code' => 'REACTION_REMOVE_FAILED'
            ], 422);
        }
    }

    public function thread(Request $request, Message $message): JsonResponse
    {
        if (!$this->userCanAccessMessage($message, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            $threadMessages = $this->messageService->getThreadMessages($message, $request->user());

            return response()->json([
                'data' => $threadMessages,
                'parent_message' => $message->load(['sender:id,name,avatar_url'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load thread',
                'code' => 'THREAD_LOAD_FAILED'
            ], 422);
        }
    }

    public function markAsRead(Request $request, Channel $channel): JsonResponse
    {
        try {
            $this->messageService->markChannelAsRead($channel, $request->user());

            return response()->json([
                'message' => 'Channel marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark as read',
                'code' => 'MARK_READ_FAILED'
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

    private function userCanAccessMessage(Message $message, $user): bool
    {
        return $this->userCanAccessChannel($message->channel, $user);
    }

    private function userCanDeleteMessage(Message $message, $user): bool
    {
        if ($message->sender_id === $user->id) {
            return true;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $message->channel->members()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }
}