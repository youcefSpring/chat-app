<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PresenceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private PresenceService $presenceService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $organization = $user->organization;

        // Get user's channels
        $channels = $user->channelMembers()
            ->with(['channel.members', 'channel.messages' => function ($query) {
                $query->latest()->limit(1)->with('sender:id,name,avatar_url');
            }])
            ->get()
            ->pluck('channel')
            ->sortBy('name');

        // Get organization users presence
        $organizationPresence = $this->presenceService->getUsersPresenceInOrganization($organization->id);

        // Get recent activity/notifications
        $notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'user',
            'organization',
            'channels',
            'organizationPresence',
            'notifications'
        ));
    }

    public function chat(Request $request): View
    {
        $user = $request->user();
        $organization = $user->organization;

        // Get user's channels for sidebar
        $channels = $user->channelMembers()
            ->with(['channel' => function ($query) {
                $query->withCount('members');
            }])
            ->get()
            ->pluck('channel')
            ->sortBy('name');

        // Get online users
        $onlineUsers = $user->organization->users()
            ->whereIn('status', ['online', 'away'])
            ->where('last_seen_at', '>', now()->subMinutes(10))
            ->select(['id', 'name', 'avatar_url', 'status'])
            ->get();

        return view('dashboard.chat', compact(
            'user',
            'organization',
            'channels',
            'onlineUsers'
        ));
    }

    public function profile(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.profile', compact('user'));
    }
}