<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicationController extends Controller
{
    /**
     * Display a listing of publications.
     */
    public function index(Request $request): View
    {
        $query = Publication::with('user');

        // Filter by year
        if ($request->has('year') && $request->year) {
            $query->byYear($request->year);
        }

        // Filter by journal
        if ($request->has('journal') && $request->journal) {
            $query->byJournal($request->journal);
        }

        // Search by title or authors
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('authors', 'like', '%' . $request->search . '%')
                  ->orWhere('abstract', 'like', '%' . $request->search . '%');
            });
        }

        $publications = $query->latest()->paginate(10);

        // Get available years for filtering
        $years = Publication::selectRaw('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Get available journals for filtering
        $journals = Publication::whereNotNull('journal')
            ->selectRaw('journal')
            ->distinct()
            ->orderBy('journal')
            ->pluck('journal');

        return view('public.publications.index', compact('publications', 'years', 'journals'));
    }

    /**
     * Display the specified publication.
     */
    public function show(Publication $publication): View
    {
        $publication->load('user');

        return view('public.publications.show', compact('publication'));
    }

    /**
     * Download publication file.
     */
    public function download(Publication $publication)
    {
        if (!$publication->publication_file_path || !file_exists(storage_path('app/' . $publication->publication_file_path))) {
            abort(404, 'Publication file not found.');
        }

        $filePath = storage_path('app/' . $publication->publication_file_path);
        $fileName = str_replace(' ', '_', $publication->title) . '.pdf';

        return response()->download($filePath, $fileName);
    }
}