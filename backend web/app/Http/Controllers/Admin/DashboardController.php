<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\Project;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the admin dashboard.
     */
    public function index(): View
    {
        // Basic statistics
        $stats = [
            'courses' => Course::count(),
            'projects' => Project::count(),
            'publications' => Publication::count(),
            'blog_posts' => BlogPost::count(),
            'contact_messages' => ContactMessage::count(),
            'unread_messages' => ContactMessage::unread()->count(),
        ];

        // Recent activities
        $recentCourses = Course::latest()->limit(5)->get();
        $recentProjects = Project::latest()->limit(5)->get();
        $recentPosts = BlogPost::latest()->limit(5)->get();
        $recentMessages = ContactMessage::latest()->limit(5)->get();

        // Content status breakdown
        $contentStatus = [
            'published_posts' => BlogPost::published()->count(),
            'draft_posts' => BlogPost::draft()->count(),
            'featured_projects' => Project::featured()->count(),
            'active_courses' => Course::active()->count(),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'recentCourses',
            'recentProjects',
            'recentPosts',
            'recentMessages',
            'contentStatus'
        ));
    }
}