<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function dashboard(Request $request, Organization $organization): JsonResponse
    {
        if ($request->user()->role !== 'admin' || $request->user()->organization_id !== $organization->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $stats = [
            'total_users' => $organization->users()->count(),
            'active_users_today' => $organization->users()
                ->where('last_seen_at', '>', now()->subDay())
                ->count(),
            'total_channels' => $organization->channels()->count(),
            'total_messages_today' => $organization->channels()
                ->withCount(['messages' => function ($query) {
                    $query->where('created_at', '>', now()->subDay());
                }])
                ->get()
                ->sum('messages_count'),
        ];

        $recentActivity = $this->auditService->getOrganizationAuditLogs(
            $organization,
            ['limit' => 10]
        );

        return response()->json([
            'stats' => $stats,
            'recent_activity' => $recentActivity
        ]);
    }

    public function auditLogs(Request $request, Organization $organization): JsonResponse
    {
        if ($request->user()->role !== 'admin' || $request->user()->organization_id !== $organization->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $filters = $request->only(['actor_id', 'action', 'from_date', 'to_date', 'limit']);
        $filters['limit'] = min($request->get('limit', 50), 100);

        $logs = $this->auditService->getOrganizationAuditLogs($organization, $filters);

        return response()->json(['data' => $logs]);
    }

    public function exportAuditLogs(Request $request, Organization $organization)
    {
        if ($request->user()->role !== 'admin' || $request->user()->organization_id !== $organization->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $filters = $request->only(['actor_id', 'action', 'from_date', 'to_date']);

        $csv = $this->auditService->exportAuditLogs($organization, $filters);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="audit-logs.csv"');
    }
}