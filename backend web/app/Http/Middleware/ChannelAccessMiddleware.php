<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChannelAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $channel = $request->route('channel');

        if (!$user || !$channel) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        // Check organization membership
        if ($user->organization_id !== $channel->organization_id) {
            return response()->json([
                'message' => 'Access denied to this channel',
                'code' => 'CHANNEL_ACCESS_DENIED'
            ], 403);
        }

        // Check channel access based on type
        if ($channel->type === 'public') {
            // Public channels are accessible to all organization members
            return $next($request);
        }

        // For private and direct channels, check membership
        if (!$channel->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You are not a member of this channel',
                'code' => 'CHANNEL_MEMBERSHIP_REQUIRED'
            ], 403);
        }

        return $next($request);
    }
}