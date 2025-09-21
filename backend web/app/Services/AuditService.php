<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditService
{
    public function log(
        Organization $organization,
        ?User $actor,
        string $action,
        ?Model $target = null,
        array $details = [],
        ?Request $request = null
    ): AuditLog {
        $auditLog = AuditLog::create([
            'organization_id' => $organization->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target?->id,
            'details' => $details,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        Log::info('Audit log created', [
            'organization_id' => $organization->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target?->id,
        ]);

        return $auditLog;
    }

    public function logUserAction(User $actor, string $action, ?Model $target = null, array $details = [], ?Request $request = null): AuditLog
    {
        return $this->log(
            $actor->organization,
            $actor,
            $action,
            $target,
            $details,
            $request
        );
    }

    public function logSystemAction(Organization $organization, string $action, ?Model $target = null, array $details = []): AuditLog
    {
        return $this->log(
            $organization,
            null,
            $action,
            $target,
            $details
        );
    }

    public function logChannelCreated(User $creator, $channel): void
    {
        $this->logUserAction($creator, 'channel.created', $channel, [
            'channel_name' => $channel->name,
            'channel_type' => $channel->type,
        ]);
    }

    public function logChannelDeleted(User $actor, $channel): void
    {
        $this->logUserAction($actor, 'channel.deleted', $channel, [
            'channel_name' => $channel->name,
            'channel_type' => $channel->type,
        ]);
    }

    public function logUserJoinedChannel(User $user, $channel, ?User $invitedBy = null): void
    {
        $details = [
            'channel_name' => $channel->name,
        ];

        if ($invitedBy) {
            $details['invited_by'] = $invitedBy->name;
        }

        $this->logUserAction($user, 'channel.joined', $channel, $details);
    }

    public function logUserLeftChannel(User $user, $channel, ?User $removedBy = null): void
    {
        $details = [
            'channel_name' => $channel->name,
        ];

        if ($removedBy && $removedBy->id !== $user->id) {
            $details['removed_by'] = $removedBy->name;
            $action = 'channel.member_removed';
        } else {
            $action = 'channel.left';
        }

        $this->logUserAction($user, $action, $channel, $details);
    }

    public function logMessageDeleted(User $actor, $message): void
    {
        $this->logUserAction($actor, 'message.deleted', $message, [
            'channel_id' => $message->channel_id,
            'message_body_preview' => substr($message->body, 0, 100),
            'original_sender_id' => $message->sender_id,
        ]);
    }

    public function logFileUploaded(User $uploader, $attachment): void
    {
        $this->logUserAction($uploader, 'file.uploaded', $attachment, [
            'file_name' => $attachment->original_name,
            'file_size' => $attachment->size,
            'mime_type' => $attachment->mime_type,
        ]);
    }

    public function logFileDeleted(User $actor, $attachment): void
    {
        $this->logUserAction($actor, 'file.deleted', $attachment, [
            'file_name' => $attachment->original_name,
            'file_size' => $attachment->size,
            'original_uploader_id' => $attachment->uploader_id,
        ]);
    }

    public function logCallInitiated(User $initiator, $call): void
    {
        $this->logUserAction($initiator, 'call.initiated', $call, [
            'call_type' => $call->type,
            'channel_id' => $call->channel_id,
        ]);
    }

    public function logUserRoleChanged(User $actor, User $targetUser, string $oldRole, string $newRole): void
    {
        $this->logUserAction($actor, 'user.role_changed', $targetUser, [
            'old_role' => $oldRole,
            'new_role' => $newRole,
        ]);
    }

    public function logLoginAttempt(string $email, bool $successful, ?string $ip = null, ?string $userAgent = null): void
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            $this->log(
                $user->organization,
                $successful ? $user : null,
                $successful ? 'auth.login_success' : 'auth.login_failed',
                $user,
                [
                    'email' => $email,
                    'successful' => $successful,
                ],
                null
            );
        }
    }

    public function logPasswordChanged(User $user): void
    {
        $this->logUserAction($user, 'auth.password_changed', $user);
    }

    public function logTwoFactorEnabled(User $user): void
    {
        $this->logUserAction($user, 'auth.2fa_enabled', $user);
    }

    public function logTwoFactorDisabled(User $user): void
    {
        $this->logUserAction($user, 'auth.2fa_disabled', $user);
    }

    public function getOrganizationAuditLogs(Organization $organization, array $filters = []): array
    {
        $query = AuditLog::where('organization_id', $organization->id)
            ->with(['actor:id,name,email', 'organization:id,name']);

        $this->applyAuditFilters($query, $filters);

        return $query
            ->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->toArray();
    }

    public function getUserAuditLogs(User $user, array $filters = []): array
    {
        $query = AuditLog::where('organization_id', $user->organization_id)
            ->where(function ($q) use ($user) {
                $q->where('actor_id', $user->id)
                  ->orWhere(function ($subQ) use ($user) {
                      $subQ->where('target_type', User::class)
                           ->where('target_id', $user->id);
                  });
            })
            ->with(['actor:id,name,email']);

        $this->applyAuditFilters($query, $filters);

        return $query
            ->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->toArray();
    }

    public function exportAuditLogs(Organization $organization, array $filters = []): string
    {
        $logs = $this->getOrganizationAuditLogs($organization, $filters);

        $csv = "Date,Actor,Action,Target Type,Target ID,Details,IP Address\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $log['created_at'],
                $log['actor']['name'] ?? 'System',
                $log['action'],
                $log['target_type'] ?? '',
                $log['target_id'] ?? '',
                json_encode($log['details']),
                $log['ip_address'] ?? ''
            );
        }

        return $csv;
    }

    public function cleanupOldAuditLogs(int $daysToKeep = 365): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }

    private function applyAuditFilters($query, array $filters): void
    {
        if (isset($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', 'LIKE', "%{$filters['action']}%");
        }

        if (isset($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }
    }
}