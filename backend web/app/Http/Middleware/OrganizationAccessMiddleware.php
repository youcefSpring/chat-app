<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizationAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'code' => 'UNAUTHENTICATED'
            ], 401);
        }

        // Check if route has organization parameter
        $organization = $request->route('organization');

        if ($organization) {
            // Verify user belongs to the organization
            if ($user->organization_id !== $organization->id) {
                return response()->json([
                    'message' => 'Access denied to this organization',
                    'code' => 'ORGANIZATION_ACCESS_DENIED'
                ], 403);
            }
        }

        return $next($request);
    }
}