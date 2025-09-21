<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the homepage.
     */
    public function index(): View
    {
        // Get the main teacher/owner user
        $teacher = User::where('role', 'teacher')->first();

        // Get featured projects
        $featuredProjects = Project::featured()
            ->with('tags')
            ->latest()
            ->limit(3)
            ->get();

        // Get latest blog posts
        $latestPosts = BlogPost::published()
            ->with('user')
            ->latest()
            ->limit(3)
            ->get();

        return view('public.home', compact(
            'teacher',
            'featuredProjects',
            'latestPosts'
        ));
    }

    /**
     * Display the about page.
     */
    public function about(): View
    {
        $teacher = User::where('role', 'teacher')
            ->with(['credentials' => function ($query) {
                $query->valid()->latest();
            }])
            ->first();

        return view('public.about', compact('teacher'));
    }

    /**
     * Download CV file.
     */
    public function downloadCV()
    {
        $teacher = User::where('role', 'teacher')->first();

        if (!$teacher || !$teacher->cv_file_path || !file_exists(storage_path('app/' . $teacher->cv_file_path))) {
            abort(404, 'CV not found.');
        }

        $filePath = storage_path('app/' . $teacher->cv_file_path);
        $fileName = $teacher->name . '_CV.pdf';

        return response()->download($filePath, $fileName);
    }
}