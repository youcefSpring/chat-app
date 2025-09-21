<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): View
    {
        $query = Project::active()->with(['user', 'tags']);

        // Filter by technology/tag
        if ($request->has('tag') && $request->tag) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // Filter by year
        if ($request->has('year') && $request->year) {
            $query->whereYear('date_completed', $request->year);
        }

        // Search by title or description
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $projects = $query->orderBy('date_completed', 'desc')->paginate(9);

        // Get available tags for filtering
        $tags = Tag::whereHas('projects', function ($q) {
            $q->active();
        })->orderBy('name')->get();

        // Get available years for filtering
        $years = Project::active()
            ->whereNotNull('date_completed')
            ->selectRaw('YEAR(date_completed) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('public.projects.index', compact('projects', 'tags', 'years'));
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): View
    {
        // Ensure project is active or return 404
        if ($project->status === 'archived') {
            abort(404);
        }

        $project->load(['user', 'tags']);

        // Get related projects (same tags)
        $relatedProjects = Project::active()
            ->where('id', '!=', $project->id)
            ->whereHas('tags', function ($q) use ($project) {
                $q->whereIn('tags.id', $project->tags->pluck('id'));
            })
            ->with('tags')
            ->limit(3)
            ->get();

        return view('public.projects.show', compact('project', 'relatedProjects'));
    }
}