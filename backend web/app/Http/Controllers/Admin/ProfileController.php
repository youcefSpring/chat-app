<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit(): View
    {
        $user = Auth::user();
        return view('admin.profile.edit', compact('user'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture_path) {
                Storage::disk('local')->delete($user->profile_picture_path);
            }

            $data['profile_picture_path'] = $request->file('profile_picture')
                ->store('images/profile', 'local');
        }

        $user->update($data);

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    public function uploadCV(Request $request): RedirectResponse
    {
        $request->validate([
            'cv_file' => 'required|file|mimes:pdf|max:10240'
        ]);

        $user = Auth::user();

        if ($user->cv_file_path) {
            Storage::disk('local')->delete($user->cv_file_path);
        }

        $cvPath = $request->file('cv_file')->store('documents/cv', 'local');

        $user->update(['cv_file_path' => $cvPath]);

        return redirect()->route('admin.profile.edit')
            ->with('success', 'CV uploaded successfully.');
    }

    public function deleteCV(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->cv_file_path) {
            Storage::disk('local')->delete($user->cv_file_path);
            $user->update(['cv_file_path' => null]);

            return redirect()->route('admin.profile.edit')
                ->with('success', 'CV deleted successfully.');
        }

        return redirect()->route('admin.profile.edit')
            ->with('error', 'No CV file found to delete.');
    }
}