<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request): View
    {
        $query = BlogPost::published()->with(['user', 'tags']);

        // Filter by tag
        if ($request->has('tag') && $request->tag) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // Filter by month/year
        if ($request->has('month') && $request->month) {
            $query->whereMonth('published_at', $request->month);
        }

        if ($request->has('year') && $request->year) {
            $query->whereYear('published_at', $request->year);
        }

        // Search by title or content
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        $posts = $query->latest('published_at')->paginate(6);

        // Get available tags for filtering
        $tags = Tag::whereHas('blogPosts', function ($q) {
            $q->published();
        })->orderBy('name')->get();

        // Get archive dates (year/month combinations)
        $archives = BlogPost::published()
            ->selectRaw('YEAR(published_at) as year, MONTH(published_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                $item->month_name = date('F', mktime(0, 0, 0, $item->month, 1));
                return $item;
            });

        return view('public.blog.index', compact('posts', 'tags', 'archives'));
    }

    /**
     * Display the specified blog post.
     */
    public function show(BlogPost $blogPost): View
    {
        // Ensure post is published
        if (!$blogPost->isPublished()) {
            abort(404);
        }

        $blogPost->load(['user', 'tags']);

        // Get related posts (same tags)
        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $blogPost->id)
            ->whereHas('tags', function ($q) use ($blogPost) {
                $q->whereIn('tags.id', $blogPost->tags->pluck('id'));
            })
            ->with('tags')
            ->limit(3)
            ->get();

        return view('public.blog.show', compact('blogPost', 'relatedPosts'));
    }
}