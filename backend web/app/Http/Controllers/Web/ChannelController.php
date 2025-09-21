<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\CreateChannelRequest;
use App\Models\Channel;
use App\Services\ChannelService;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ChannelController extends Controller
{
    public function __construct(
        private ChannelService $channelService,
        private MessageService $messageService
    ) {}

    public function show(Request $request, Channel $channel): View
    {
        if (!$this->userCanAccessChannel($channel, $request->user())) {
            abort(403, 'Access denied to this channel');
        }

        // Load channel with members and recent messages
        $channel->load([
            'members:id,name,avatar_url,status',
            'creator:id,name,avatar_url'
        ]);

        // Get recent messages (last 50)
        $messages = $channel->messages()
            ->with([
                'sender:id,name,avatar_url',
                'attachments',
                'reactions.user:id,name',
                'replyTo:id,body,sender_id',
                'replyTo.sender:id,name'
            ])
            ->whereNull('deleted_at')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        // Mark channel as read
        $this->messageService->markChannelAsRead($channel, $request->user());

        // Get online members
        $onlineMembers = $channel->members()
            ->whereIn('status', ['online', 'away'])
            ->where('last_seen_at', '>', now()->subMinutes(10))
            ->get();

        return view('channels.show', compact(
            'channel',
            'messages',
            'onlineMembers'
        ));
    }

    public function create(Request $request): View
    {
        $organization = $request->user()->organization;

        // Get organization members for invitation
        $organizationMembers = $organization->users()
            ->where('id', '!=', $request->user()->id)
            ->select(['id', 'name', 'email', 'avatar_url'])
            ->orderBy('name')
            ->get();

        return view('channels.create', compact('organizationMembers'));
    }

    public function store(CreateChannelRequest $request): RedirectResponse
    {
        try {
            $channel = $this->channelService->createChannel(
                $request->user()->organization,
                $request->user(),
                $request->validated()
            );

            return redirect()
                ->route('channels.show', $channel)
                ->with('success', 'Channel created successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit(Request $request, Channel $channel): View
    {
        if (!$this->userCanEditChannel($channel, $request->user())) {
            abort(403, 'Access denied');
        }

        return view('channels.edit', compact('channel'));
    }

    public function update(Request $request, Channel $channel): RedirectResponse
    {
        if (!$this->userCanEditChannel($channel, $request->user())) {
            abort(403, 'Access denied');
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9-_]+$/'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        try {
            $channel->update($request->only(['name', 'description']));

            return redirect()
                ->route('channels.show', $channel)
                ->with('success', 'Channel updated successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update channel']);
        }
    }

    public function members(Request $request, Channel $channel): View
    {
        if (!$this->userCanAccessChannel($channel, $request->user())) {
            abort(403, 'Access denied');
        }

        $members = $channel->members()
            ->withPivot(['role', 'joined_at', 'notifications_enabled'])
            ->orderBy('name')
            ->get();

        $canManageMembers = $this->userCanManageMembers($channel, $request->user());

        return view('channels.members', compact('channel', 'members', 'canManageMembers'));
    }

    public function join(Request $request, Channel $channel): RedirectResponse
    {
        if ($channel->type !== 'public') {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Cannot join private channel']);
        }

        try {
            $this->channelService->addMemberToChannel($channel, $request->user());

            return redirect()
                ->route('channels.show', $channel)
                ->with('success', 'Successfully joined channel!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function leave(Request $request, Channel $channel): RedirectResponse
    {
        try {
            $this->channelService->removeMemberFromChannel($channel, $request->user());

            return redirect()
                ->route('dashboard')
                ->with('success', 'Successfully left channel');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to leave channel']);
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

    private function userCanEditChannel(Channel $channel, $user): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($channel->created_by === $user->id) {
            return true;
        }

        return $channel->members()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }

    private function userCanManageMembers(Channel $channel, $user): bool
    {
        return $this->userCanEditChannel($channel, $user);
    }
}