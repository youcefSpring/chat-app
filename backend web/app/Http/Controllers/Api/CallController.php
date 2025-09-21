<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Call\InitiateCallRequest;
use App\Models\Call;
use App\Models\Channel;
use App\Services\CallService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function __construct(
        private CallService $callService,
        private AuditService $auditService
    ) {}

    public function store(InitiateCallRequest $request): JsonResponse
    {
        $request->validate(['channel_id' => ['required', 'integer', 'exists:channels,id']]);

        try {
            $channel = Channel::findOrFail($request->channel_id);
            $call = $this->callService->initiateCall(
                $channel,
                $request->user(),
                $request->get('type', 'audio')
            );

            $this->auditService->logCallInitiated($request->user(), $call);

            return response()->json($call->load(['participants', 'channel']), 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'CALL_INITIATION_FAILED'
            ], 422);
        }
    }

    public function show(Request $request, Call $call): JsonResponse
    {
        if (!$call->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return response()->json($call->load(['participants.user', 'channel', 'initiator']));
    }

    public function update(Request $request, Call $call): JsonResponse
    {
        $request->validate(['action' => ['required', 'in:join,leave,accept,reject']]);

        try {
            switch ($request->action) {
                case 'join':
                case 'accept':
                    $this->callService->joinCall($call, $request->user());
                    break;
                case 'leave':
                    $this->callService->leaveCall($call, $request->user());
                    break;
                case 'reject':
                    $this->callService->rejectCall($call, $request->user());
                    break;
            }

            return response()->json($call->fresh()->load(['participants.user']));

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'CALL_ACTION_FAILED'
            ], 422);
        }
    }

    public function destroy(Request $request, Call $call): JsonResponse
    {
        try {
            $this->callService->endCall($call, $request->user());
            return response()->json(null, 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'CALL_END_FAILED'
            ], 422);
        }
    }
}