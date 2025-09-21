<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 50);
        $unreadOnly = $request->boolean('unread_only');

        $query = $request->user()->notifications();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'total' => $notifications->total(),
                'unread_count' => $this->notificationService->getUnreadCount($request->user())
            ]
        ]);
    }

    public function markAsRead(Request $request, $notificationId): JsonResponse
    {
        try {
            $this->notificationService->markAsRead($request->user(), $notificationId);

            return response()->json(['message' => 'Notification marked as read']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark notification as read',
                'code' => 'MARK_READ_FAILED'
            ], 422);
        }
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $this->notificationService->markAllAsRead($request->user());

            return response()->json(['message' => 'All notifications marked as read']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark all notifications as read',
                'code' => 'MARK_ALL_READ_FAILED'
            ], 422);
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return response()->json(['unread_count' => $count]);
    }
}