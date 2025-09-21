<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
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

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Admin access required',
                'code' => 'ADMIN_ACCESS_REQUIRED'
            ], 403);
        }

        return $next($request);
    }
}