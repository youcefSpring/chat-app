<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(private SearchService $searchService) {}

    public function search(SearchRequest $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'channel_id', 'sender_id', 'file_type', 'date_from', 'date_to',
                'limit', 'has_attachments'
            ]);

            switch ($request->type) {
                case 'messages':
                    $results = $this->searchService->searchMessages($request->user(), $request->q, $filters);
                    break;
                case 'channels':
                    $results = $this->searchService->searchChannels($request->user(), $request->q);
                    break;
                case 'users':
                    $results = $this->searchService->searchUsers($request->user(), $request->q);
                    break;
                case 'files':
                    $results = $this->searchService->searchFiles($request->user(), $request->q, $filters);
                    break;
                case 'all':
                default:
                    $results = $this->searchService->getGlobalSearchResults($request->user(), $request->q);
                    break;
            }

            $this->searchService->logSearch(
                $request->user(),
                $request->q,
                $request->type,
                is_array($results) ? count($results) : $results->count()
            );

            return response()->json([
                'data' => $results,
                'meta' => [
                    'query' => $request->q,
                    'type' => $request->type,
                    'total' => is_array($results) ? count($results) : $results->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Search failed',
                'code' => 'SEARCH_FAILED'
            ], 422);
        }
    }

    public function suggestions(SearchRequest $request): JsonResponse
    {
        try {
            $suggestions = $this->searchService->getPopularSearchTerms($request->user(), 10);

            return response()->json(['data' => $suggestions]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get suggestions',
                'code' => 'SUGGESTIONS_FAILED'
            ], 422);
        }
    }
}