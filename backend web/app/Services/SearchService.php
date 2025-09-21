<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Channel;
use App\Models\User;
use App\Models\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function searchMessages(User $user, string $query, array $filters = []): array
    {
        $searchQuery = Message::query()
            ->with(['sender', 'channel', 'attachments'])
            ->whereHas('channel', function ($channelQuery) use ($user) {
                $this->addChannelAccessConstraints($channelQuery, $user);
            })
            ->whereNull('deleted_at');

        if (!empty($query)) {
            $searchQuery->whereRaw(
                "to_tsvector('english', body) @@ plainto_tsquery('english', ?)",
                [$query]
            );
        }

        $this->applyMessageFilters($searchQuery, $filters);

        return $searchQuery
            ->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->toArray();
    }

    public function searchChannels(User $user, string $query): array
    {
        $searchQuery = Channel::query()
            ->where('organization_id', $user->organization_id)
            ->where(function ($q) use ($user) {
                $q->where('type', 'public')
                  ->orWhereHas('members', function ($memberQuery) use ($user) {
                      $memberQuery->where('user_id', $user->id);
                  });
            });

        if (!empty($query)) {
            $searchQuery->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('description', 'ILIKE', "%{$query}%");
            });
        }

        return $searchQuery
            ->with(['creator'])
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function searchUsers(User $user, string $query): array
    {
        return User::where('organization_id', $user->organization_id)
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('email', 'ILIKE', "%{$query}%");
            })
            ->select(['id', 'name', 'email', 'avatar_url', 'status'])
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function searchFiles(User $user, string $query, array $filters = []): array
    {
        $searchQuery = Attachment::query()
            ->with(['message.channel', 'uploader'])
            ->whereHas('message.channel', function ($channelQuery) use ($user) {
                $this->addChannelAccessConstraints($channelQuery, $user);
            });

        if (!empty($query)) {
            $searchQuery->where('original_name', 'ILIKE', "%{$query}%");
        }

        $this->applyFileFilters($searchQuery, $filters);

        return $searchQuery
            ->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->toArray();
    }

    public function getGlobalSearchResults(User $user, string $query): array
    {
        return [
            'messages' => $this->searchMessages($user, $query, ['limit' => 10]),
            'channels' => $this->searchChannels($user, $query),
            'users' => $this->searchUsers($user, $query),
            'files' => $this->searchFiles($user, $query, ['limit' => 10]),
        ];
    }

    public function getMentions(User $user, array $filters = []): array
    {
        $mentionPattern = '@' . $user->name;

        return Message::query()
            ->with(['sender', 'channel'])
            ->whereHas('channel', function ($channelQuery) use ($user) {
                $this->addChannelAccessConstraints($channelQuery, $user);
            })
            ->where('body', 'ILIKE', "%{$mentionPattern}%")
            ->where('sender_id', '!=', $user->id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->toArray();
    }

    public function getPopularSearchTerms(User $user, int $limit = 10): array
    {
        return DB::table('search_logs')
            ->where('user_id', $user->id)
            ->where('created_at', '>', now()->subDays(30))
            ->select('query', DB::raw('COUNT(*) as count'))
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('query')
            ->toArray();
    }

    public function logSearch(User $user, string $query, string $type, int $resultCount): void
    {
        DB::table('search_logs')->insert([
            'user_id' => $user->id,
            'query' => $query,
            'type' => $type,
            'result_count' => $resultCount,
            'created_at' => now(),
        ]);
    }

    private function addChannelAccessConstraints(Builder $channelQuery, User $user): void
    {
        $channelQuery->where('organization_id', $user->organization_id)
            ->where(function ($q) use ($user) {
                $q->where('type', 'public')
                  ->orWhereHas('members', function ($memberQuery) use ($user) {
                      $memberQuery->where('user_id', $user->id);
                  });
            });
    }

    private function applyMessageFilters(Builder $query, array $filters): void
    {
        if (isset($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }

        if (isset($filters['sender_id'])) {
            $query->where('sender_id', $filters['sender_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['has_attachments']) && $filters['has_attachments']) {
            $query->whereHas('attachments');
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }

    private function applyFileFilters(Builder $query, array $filters): void
    {
        if (isset($filters['file_type'])) {
            $mimeTypes = $this->getFileTypeMimeTypes($filters['file_type']);
            if ($mimeTypes) {
                $query->whereIn('mime_type', $mimeTypes);
            }
        }

        if (isset($filters['size_min'])) {
            $query->where('size', '>=', $filters['size_min']);
        }

        if (isset($filters['size_max'])) {
            $query->where('size', '<=', $filters['size_max']);
        }

        if (isset($filters['uploader_id'])) {
            $query->where('uploader_id', $filters['uploader_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }

    private function getFileTypeMimeTypes(string $fileType): array
    {
        $mimeTypesMap = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'document' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'video' => ['video/mp4', 'video/webm', 'video/quicktime'],
            'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'],
        ];

        return $mimeTypesMap[$fileType] ?? [];
    }
}