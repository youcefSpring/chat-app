<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\AuthService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private AuditService $auditService
    ) {}

    public function profile(Request $request): View
    {
        $user = $request->user();

        return view('settings.profile', compact('user'));
    }

    public function updateProfile(UpdateUserRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $user->update($request->validated());

            return redirect()
                ->route('settings.profile')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update profile']);
        }
    }

    public function security(Request $request): View
    {
        $user = $request->user();

        return view('settings.security', compact('user'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $user->update(['password_hash' => bcrypt($request->password)]);

            $this->auditService->logPasswordChanged($user);

            return redirect()
                ->route('settings.security')
                ->with('success', 'Password updated successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to update password']);
        }
    }

    public function notifications(Request $request): View
    {
        $user = $request->user();

        return view('settings.notifications', compact('user'));
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->validate([
            'email_notifications' => ['sometimes', 'boolean'],
            'push_notifications' => ['sometimes', 'boolean'],
            'mention_notifications' => ['sometimes', 'boolean'],
            'message_notifications' => ['sometimes', 'boolean'],
        ]);

        try {
            $user = $request->user();
            $settings = $user->settings ?? [];

            $settings['notifications'] = [
                'email_notifications' => $request->boolean('email_notifications'),
                'push_notifications' => $request->boolean('push_notifications'),
                'mention_notifications' => $request->boolean('mention_notifications'),
                'message_notifications' => $request->boolean('message_notifications'),
            ];

            $user->update(['settings' => $settings]);

            return redirect()
                ->route('settings.notifications')
                ->with('success', 'Notification preferences updated successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to update notification preferences']);
        }
    }

    public function organization(Request $request): View
    {
        $user = $request->user();
        $organization = $user->organization;

        if ($user->role !== 'admin') {
            abort(403, 'Access denied');
        }

        return view('settings.organization', compact('user', 'organization'));
    }

    public function updateOrganization(UpdateOrganizationRequest $request): RedirectResponse
    {
        try {
            $organization = $request->user()->organization;
            $organization->update($request->validated());

            $this->auditService->logUserAction(
                $request->user(),
                'organization.settings_updated',
                $organization
            );

            return redirect()
                ->route('settings.organization')
                ->with('success', 'Organization settings updated successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update organization settings']);
        }
    }

    public function twoFactor(Request $request): View
    {
        $user = $request->user();

        return view('settings.two-factor', compact('user'));
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $secret = $this->authService->setup2FA($user);
            $recoveryCodes = $this->authService->generateRecoveryCodes($user);

            $this->auditService->logTwoFactorEnabled($user);

            return redirect()
                ->route('settings.two-factor')
                ->with('success', 'Two-factor authentication enabled successfully!')
                ->with('recovery_codes', $recoveryCodes);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to enable two-factor authentication']);
        }
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $user->update([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ]);

            $this->auditService->logTwoFactorDisabled($user);

            return redirect()
                ->route('settings.two-factor')
                ->with('success', 'Two-factor authentication disabled successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to disable two-factor authentication']);
        }
    }

    public function admin(Request $request): View
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            abort(403, 'Access denied');
        }

        $organization = $user->organization;

        // Get organization statistics
        $stats = [
            'total_users' => $organization->users()->count(),
            'total_channels' => $organization->channels()->count(),
            'active_users_today' => $organization->users()
                ->where('last_seen_at', '>', now()->subDay())
                ->count(),
            'total_messages_today' => $organization->channels()
                ->withCount(['messages' => function ($query) {
                    $query->where('created_at', '>', now()->subDay());
                }])
                ->get()
                ->sum('messages_count'),
        ];

        // Get recent audit logs
        $auditLogs = $this->auditService->getOrganizationAuditLogs($organization, ['limit' => 20]);

        return view('settings.admin', compact('user', 'organization', 'stats', 'auditLogs'));
    }
}