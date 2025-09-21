<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of projects.
     */
    public function index(Request $request): View
    {
        $query = Project::with(['user', 'tags']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by tag
        if ($request->has('tag') && $request->tag) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        $projects = $query->latest()->paginate(10);

        // Get tags for filter
        $tags = Tag::orderBy('name')->get();

        return view('admin.projects.index', compact('projects', 'tags'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): View
    {
        $tags = Tag::orderBy('name')->get();
        return view('admin.projects.create', compact('tags'));
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // Handle image uploads
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('images/projects', 'local');
            }
            $data['images'] = $imagePaths;
        }

        $project = Project::create($data);

        // Attach tags
        if ($request->has('tag_ids') && $request->tag_ids) {
            $project->tags()->attach($request->tag_ids);
        }

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): View
    {
        $project->load(['user', 'tags']);
        return view('admin.projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        $tags = Tag::orderBy('name')->get();
        $project->load('tags');

        return view('admin.projects.edit', compact('project', 'tags'));
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();

        // Handle image uploads
        if ($request->hasFile('images')) {
            // Delete old images
            if ($project->images) {
                foreach ($project->images as $image) {
                    Storage::disk('local')->delete($image);
                }
            }

            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('images/projects', 'local');
            }
            $data['images'] = $imagePaths;
        }

        $project->update($data);

        // Sync tags
        if ($request->has('tag_ids')) {
            $project->tags()->sync($request->tag_ids ?: []);
        }

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        // Delete images
        if ($project->images) {
            foreach ($project->images as $image) {
                Storage::disk('local')->delete($image);
            }
        }

        // Detach tags
        $project->tags()->detach();

        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}