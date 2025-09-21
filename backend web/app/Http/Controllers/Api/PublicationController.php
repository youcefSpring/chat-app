<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Publication::with(['user:id,name', 'tags:id,name,slug'])
            ->where('status', 'published');

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('year') && $request->year) {
            $query->whereYear('publication_date', $request->year);
        }

        $publications = $query->latest('publication_date')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $publications->items(),
            'meta' => [
                'current_page' => $publications->currentPage(),
                'last_page' => $publications->lastPage(),
                'per_page' => $publications->perPage(),
                'total' => $publications->total()
            ]
        ]);
    }

    public function show(Publication $publication): JsonResponse
    {
        if ($publication->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Publication not found.'
            ], 404);
        }

        $publication->load(['user:id,name', 'tags:id,name,slug,color']);

        return response()->json([
            'success' => true,
            'data' => $publication
        ]);
    }

    public function search(string $query): JsonResponse
    {
        $publications = Publication::with(['user:id,name', 'tags:id,name,slug'])
            ->where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('authors', 'like', "%{$query}%")
                  ->orWhere('keywords', 'like', "%{$query}%");
            })
            ->latest('publication_date')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $publications,
            'query' => $query,
            'count' => $publications->count()
        ]);
    }
}