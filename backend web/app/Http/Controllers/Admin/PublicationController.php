<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePublicationRequest;
use App\Http\Requests\Admin\UpdatePublicationRequest;
use App\Models\Publication;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class PublicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $query = Publication::with(['user', 'tags']);

        if ($request->has('search') && $request->search) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('journal', 'like', '%' . $request->search . '%');
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $publications = $query->latest('publication_date')->paginate(10);

        $tags = Tag::orderBy('name')->get();

        return view('admin.publications.index', compact('publications', 'tags'));
    }

    public function create(): View
    {
        $tags = Tag::orderBy('name')->get();
        return view('admin.publications.create', compact('tags'));
    }

    public function store(StorePublicationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        if ($request->hasFile('publication_file')) {
            $data['publication_file_path'] = $request->file('publication_file')
                ->store('publications', 'local');
        }

        $publication = Publication::create($data);

        if ($request->has('tag_ids') && $request->tag_ids) {
            $publication->tags()->attach($request->tag_ids);
        }

        return redirect()->route('admin.publications.index')
            ->with('success', 'Publication created successfully.');
    }

    public function show(Publication $publication): View
    {
        $publication->load(['user', 'tags']);
        return view('admin.publications.show', compact('publication'));
    }

    public function edit(Publication $publication): View
    {
        $this->authorize('update', $publication);

        $tags = Tag::orderBy('name')->get();
        $publication->load('tags');

        return view('admin.publications.edit', compact('publication', 'tags'));
    }

    public function update(UpdatePublicationRequest $request, Publication $publication): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('publication_file')) {
            if ($publication->publication_file_path) {
                Storage::disk('local')->delete($publication->publication_file_path);
            }

            $data['publication_file_path'] = $request->file('publication_file')
                ->store('publications', 'local');
        }

        $publication->update($data);

        if ($request->has('tag_ids')) {
            $publication->tags()->sync($request->tag_ids ?: []);
        }

        return redirect()->route('admin.publications.index')
            ->with('success', 'Publication updated successfully.');
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        $this->authorize('delete', $publication);

        if ($publication->publication_file_path) {
            Storage::disk('local')->delete($publication->publication_file_path);
        }

        $publication->tags()->detach();
        $publication->delete();

        return redirect()->route('admin.publications.index')
            ->with('success', 'Publication deleted successfully.');
    }
}