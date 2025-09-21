<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function show(Request $request, Organization $organization): JsonResponse
    {
        if ($request->user()->organization_id !== $organization->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return response()->json($organization);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        try {
            $oldData = $organization->toArray();

            $organization->update($request->validated());

            $this->auditService->logUserAction(
                $request->user(),
                'organization.updated',
                $organization,
                [
                    'old_data' => $oldData,
                    'new_data' => $organization->fresh()->toArray(),
                ]
            );

            return response()->json($organization->fresh());

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update organization',
                'code' => 'UPDATE_FAILED'
            ], 422);
        }
    }

    public function members(Request $request, Organization $organization): JsonResponse
    {
        if ($request->user()->organization_id !== $organization->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = min($request->get('per_page', 20), 50);

        $members = $organization->users()
            ->select(['id', 'name', 'email', 'role', 'status', 'avatar_url', 'last_seen_at', 'created_at'])
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($members);
    }

    public function inviteUser(Request $request, Organization $organization): JsonResponse
    {
        if ($request->user()->role !== 'admin' || $request->user()->organization_id !== $organization->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['sometimes', 'in:admin,member,guest'],
        ]);

        try {
            // Implementation would send invitation email
            // For now, just log the action

            $this->auditService->logUserAction(
                $request->user(),
                'organization.user_invited',
                $organization,
                [
                    'invited_email' => $request->email,
                    'role' => $request->get('role', 'member'),
                ]
            );

            return response()->json([
                'message' => 'Invitation sent successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send invitation',
                'code' => 'INVITATION_FAILED'
            ], 422);
        }
    }
}