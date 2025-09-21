<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(): View
    {
        $courses = Course::active()
            ->with('user')
            ->orderBy('start_date', 'desc')
            ->paginate(12);

        return view('public.courses.index', compact('courses'));
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course): View
    {
        // Ensure course is active or return 404
        if ($course->status !== 'active') {
            abort(404);
        }

        return view('public.courses.show', compact('course'));
    }

    /**
     * Download syllabus file.
     */
    public function downloadSyllabus(Course $course)
    {
        if (!$course->syllabus_file_path || !file_exists(storage_path('app/' . $course->syllabus_file_path))) {
            abort(404, 'Syllabus not found.');
        }

        $filePath = storage_path('app/' . $course->syllabus_file_path);
        $fileName = $course->title . '_Syllabus.pdf';

        return response()->download($filePath, $fileName);
    }
}